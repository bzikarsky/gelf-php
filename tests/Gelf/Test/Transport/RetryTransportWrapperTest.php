<?php
declare(strict_types=1);

namespace Gelf\Test\Transport;

use Gelf\Message;
use Gelf\Transport\RetryTransportWrapper;
use Gelf\Transport\TransportInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RetryTransportWrapperTest extends TestCase
{
    private Message $message;
    private TransportInterface|MockObject $transport;

    public function setUp(): void
    {
        $this->message = new Message();
        $this->transport = $this->createMock(TransportInterface::class);
    }

    public function testGetTransport(): void
    {
        $wrapper = new RetryTransportWrapper($this->transport, 1, null);
        self::assertEquals($this->transport, $wrapper->getTransport());
    }

    public function testWithoutMatcher(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("bar");

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

        self::assertEquals('', $bytes);
    }

    public function testWithMatcher(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("bar");

        $wrapper = new RetryTransportWrapper($this->transport, 1);

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

        self::assertEquals('', $bytes);
    }

    public function testWithFalseMatcher(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("foo");
        $wrapper = new RetryTransportWrapper($this->transport, 1, fn () => false);

        $expectedException1 = new RuntimeException('foo');

        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->message)
            ->willThrowException($expectedException1);

        $bytes = $wrapper->send($this->message);

        self::assertEquals('', $bytes);
    }
}
