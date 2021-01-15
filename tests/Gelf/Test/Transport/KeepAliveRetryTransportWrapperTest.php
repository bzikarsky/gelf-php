<?php

namespace Gelf\Test\Transport;

use Gelf\Message;
use Gelf\TestCase;
use Gelf\Transport\HttpTransport;
use Gelf\Transport\KeepAliveRetryTransportWrapper;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use RuntimeException;

/**
 * @covers \Gelf\Transport\KeepAliveRetryTransportWrapper
 */
class KeepAliveRetryTransportWrapperTest extends TestCase
{
    /**
     * @const string
     */
    const SUCCESS_VALUE = "HTTP/1.1 202 Accepted\r\n\r\n";

    /**
     * @var Message
     */
    private $message;

    /**
     * @var HttpTransport|MockObject
     */
    private $transport;

    /**
     * @var KeepAliveRetryTransportWrapper
     */
    private $wrapper;

    public function setUp()
    {
        $this->message = new Message();
        $this->transport = $this->buildTransport();
        $this->wrapper   = new KeepAliveRetryTransportWrapper($this->transport);
    }

    public function testSendSuccess()
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->message)
            ->will($this->returnValue(self::SUCCESS_VALUE));

        $bytes = $this->wrapper->send($this->message);

        $this->assertEquals(self::SUCCESS_VALUE, $bytes);
    }

    public function testSendSuccessAfterRetry()
    {
        $expectedException = new RuntimeException(KeepAliveRetryTransportWrapper::NO_RESPONSE);

        $this->transport->expects($this->exactly(2))
            ->method('send')
            ->with($this->message)
            ->will($this->onConsecutiveCalls(
                $this->throwException($expectedException),
                $this->returnValue(self::SUCCESS_VALUE)
            ));

        $bytes = $this->wrapper->send($this->message);

        $this->assertEquals(self::SUCCESS_VALUE, $bytes);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage response is ''
     */
    public function testSendFailTwiceWithoutResponse()
    {
        $expectedException1 = new RuntimeException(KeepAliveRetryTransportWrapper::NO_RESPONSE);
        $expectedException2 = new RuntimeException(KeepAliveRetryTransportWrapper::NO_RESPONSE);

        $this->transport->expects($this->exactly(2))
            ->method('send')
            ->with($this->message)
            ->will($this->onConsecutiveCalls(
                $this->throwException($expectedException1),
                $this->throwException($expectedException2)
            ));

        $this->wrapper->send($this->message);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage foo
     */
    public function testSendFailWithUnmanagedException()
    {
        $expectedException = new RuntimeException('foo');

        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->message)
            ->willThrowException($expectedException);

        $this->wrapper->send($this->message);
    }

    /**
     * @return MockObject|HttpTransport
     */
    private function buildTransport()
    {
        return $this->createMock("\\Gelf\\Transport\\HttpTransport");
    }
}
