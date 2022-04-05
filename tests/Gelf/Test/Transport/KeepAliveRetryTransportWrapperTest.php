<?php
declare(strict_types=1);

namespace Gelf\Test\Transport;

use Gelf\Message;
use Gelf\Transport\HttpTransport;
use Gelf\Transport\KeepAliveRetryTransportWrapper;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

class KeepAliveRetryTransportWrapperTest extends TestCase
{
    private const FAILURE_MESSAGE
        = "Graylog-Server didn't answer properly, expected 'HTTP/1.x 202 Accepted', response is ''";

    private Message $message;
    private HttpTransport|MockObject $transport;
    private KeepAliveRetryTransportWrapper $wrapper;

    public function setUp(): void
    {
        $this->message = new Message();
        $this->transport = $this->createMock(HttpTransport::class);
        $this->wrapper   = new KeepAliveRetryTransportWrapper($this->transport);
    }

    public function testSendSuccess(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->message)
            ->will($this->returnValue(42));

        $bytes = $this->wrapper->send($this->message);

        self::assertEquals(42, $bytes);
    }

    public function testSendSuccessAfterRetry(): void
    {
        $expectedException = new RuntimeException(self::FAILURE_MESSAGE);

        $this->transport->expects($this->exactly(2))
            ->method('send')
            ->with($this->message)
            ->will($this->onConsecutiveCalls(
                $this->throwException($expectedException),
                42
            ));

        $bytes = $this->wrapper->send($this->message);

        self::assertEquals(42, $bytes);
    }

    public function testSendFailTwiceWithoutResponse(): void
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage("response is ''");

        $expectedException1 = new RuntimeException(self::FAILURE_MESSAGE);
        $expectedException2 = new RuntimeException(self::FAILURE_MESSAGE);

        $this->transport->expects($this->exactly(2))
            ->method('send')
            ->with($this->message)
            ->will($this->onConsecutiveCalls(
                $this->throwException($expectedException1),
                $this->throwException($expectedException2)
            ));

        $this->wrapper->send($this->message);
    }

    public function testSendFailWithUnmanagedException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("foo");

        $expectedException = new RuntimeException('foo');

        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->message)
            ->willThrowException($expectedException);

        $this->wrapper->send($this->message);
    }
}
