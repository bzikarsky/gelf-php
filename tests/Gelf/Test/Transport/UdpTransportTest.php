<?php
declare(strict_types=1);

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf\Test\Transport;

use Gelf\Encoder\EncoderInterface;
use Gelf\MessageInterface;
use Gelf\Transport\StreamSocketClient;
use Gelf\Transport\UdpTransport;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use RuntimeException;

class UdpTransportTest extends TestCase
{
    private const CHUNK_HEADER_LENGTH = 12;

    private MockObject|StreamSocketClient $socketClient;
    private MockObject|MessageInterface $message;
    private MockObject|EncoderInterface $encoder;
    private UdpTransport $transport;
    private string $testMessage;

    public function setUp(): void
    {
        $this->testMessage = str_repeat("0123456789", 30); // 300 char string

        $this->socketClient = $this->createMock(StreamSocketClient::class);
        $this->message = $this->createMock(MessageInterface::class);

        // create an encoder always return $testMessage
        $this->encoder = $this->createMock(EncoderInterface::class);
        $this->encoder->expects($this->any())->method('encode')->will(
            $this->returnValue($this->testMessage)
        );

        $this->transport = $this->getTransport(0);
    }

    private function getTransport(int $chunkSize): UdpTransport
    {
        // initialize transport with an unlimited packet-size
        // and the mocked message encoder
        $transport = new UdpTransport("", 0, $chunkSize);
        $transport->setMessageEncoder($this->encoder);

        // replace internal stream socket client with our mock
        $reflectedTransport = new ReflectionObject($transport);
        $reflectedClient = $reflectedTransport->getProperty('socketClient');
        $reflectedClient->setAccessible(true);
        $reflectedClient->setValue($transport, $this->socketClient);

        return $transport;
    }

    public function testSetEncoder(): void
    {
        $encoder = $this->createMock(EncoderInterface::class);
        $this->transport->setMessageEncoder($encoder);

        self::assertEquals($encoder, $this->transport->getMessageEncoder());
    }

    public function testSendUnchunked(): void
    {
        $this->socketClient
            ->expects($this->once())
            ->method('write')
            ->with($this->testMessage);

        $this->transport->send($this->message);
    }

    public function testSendChunked(): void
    {
        $chunkSize = 20 + self::CHUNK_HEADER_LENGTH;
        $transport = $this->getTransport($chunkSize);
        $expectedMessageCount =  strlen($this->testMessage) / ($chunkSize - self::CHUNK_HEADER_LENGTH);

        $test = $this;
        $this->socketClient
            ->expects($this->exactly($expectedMessageCount))
            ->method('write')
            ->willReturnCallback(function ($data) use ($chunkSize, $test) {
                $test->assertLessThanOrEqual($chunkSize, strlen($data));
                return 1;
            });

        $transport->send($this->message);
    }

    public function testInvalidChunkNumber()
    {
        self::expectException(RuntimeException::class);

        $transport = $this->getTransport(self::CHUNK_HEADER_LENGTH + 1);
        $transport->send($this->message);
    }
}
