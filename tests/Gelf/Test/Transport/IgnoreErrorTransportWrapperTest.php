<?php
declare(strict_types=1);

namespace Gelf\Test\Transport;

use Gelf\MessageInterface;
use Gelf\Transport\IgnoreErrorTransportWrapper;
use Gelf\Transport\TransportInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class IgnoreErrorTransportWrapperTest extends TestCase
{
    public function testSend(): void
    {
        $expectedMessage   = $this->buildMessage();
        $expectedException = new RuntimeException();

        $transport = $this->buildTransport();
        $wrapper   = new IgnoreErrorTransportWrapper($transport);

        $transport->expects($this->once())
                  ->method('send')
                  ->with($expectedMessage)
                  ->willThrowException($expectedException);

        $bytes = $wrapper->send($expectedMessage);
        $lastError = $wrapper->getLastError();

        self::assertEquals(0, $bytes);
        self::assertSame($expectedException, $lastError);
    }

    private function buildTransport(): MockObject|TransportInterface
    {
        return $this->createMock(TransportInterface::class);
    }

    private function buildMessage(): MockObject|MessageInterface
    {
        return $this->createMock(MessageInterface::class);
    }
}
