<?php

namespace Gelf\Test\Transport;

use Gelf\Message;
use Gelf\TestCase;
use Gelf\Transport\AbstractTransport;
use Gelf\Transport\RetryTransportWrapper;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

class RetryTransportWrapperTest extends TestCase
{
    /**
     * @var Message
     */
    private $message;

    /**
     * @var AbstractTransport|MockObject
     */
    private $transport;

    public function setUp()
    {
        $this->message = new Message();
        $this->transport = $this->buildTransport();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage response is ''
     */
    public function testWithoutMatcher()
    {
        $wrapper = new RetryTransportWrapper($this->transport, 1, null);

        $expectedException1 = new RuntimeException('foo');
        $expectedException2 = new RuntimeException('bar');

        $this->transport->expects($this->exactly(2))
            ->method('send')
            ->with($this->message)
            ->will($this->onConsecutiveCalls(
                $this->throwException($expectedException1),
                $this->throwException($expectedException2)
            ));

        $bytes = $wrapper->send($this->message);

        $this->assertEquals('', $bytes);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage response is ''
     */
    public function testWithMatcher()
    {
        $wrapper = new RetryTransportWrapper($this->transport, 1, function (RuntimeException $e) {
            return true;
        });

        $expectedException1 = new RuntimeException('foo');
        $expectedException2 = new RuntimeException('bar');

        $this->transport->expects($this->exactly(2))
            ->method('send')
            ->with($this->message)
            ->will($this->onConsecutiveCalls(
                $this->throwException($expectedException1),
                $this->throwException($expectedException2)
            ));

        $bytes = $wrapper->send($this->message);

        $this->assertEquals('', $bytes);
    }
    
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage response is ''
     */
    public function testWithFalseMatcher()
    {
        $wrapper = new RetryTransportWrapper($this->transport, 1, function (RuntimeException $e) {
            return false;
        });

        $expectedException1 = new RuntimeException('foo');

        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->message)
            ->willThrowException($expectedException1);

        $bytes = $wrapper->send($this->message);

        $this->assertEquals('', $bytes);
    }

    /**
     * @return MockObject|AbstractTransport
     */
    private function buildTransport()
    {
        return $this->createMock("\\Gelf\\Transport\\AbstractTransport");
    }
}
