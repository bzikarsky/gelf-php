<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gelf\Test\Transport;

use Gelf\Encoder\EncoderInterface;
use Gelf\MessageInterface;
use Gelf\Transport\Stream\StreamSocketClient;
use Gelf\Transport\UdpTransport;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UdpTransportTest extends TestCase
{
    /**
     * @var MockObject|StreamSocketClient
     */
    protected $socketClient;

    /**
     * @var MockObject|MessageInterface
     */
    protected $message;

    /**
     * @var MockObject|EncoderInterface
     */
    protected $encoder;

    /**
     * @var UdpTransport
     */
    protected $transport;

    /**
     * @var string
     */
    protected $testMessage;

    protected function setUp(): void
    {
        $this->testMessage = \str_repeat('0123456789', 30); // 300 char string

        $this->socketClient = $this->getMockBuilder(StreamSocketClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->message = $this->getMockBuilder(MessageInterface::class)->getMock();

        // create an encoder always return $testMessage
        $this->encoder = $this->getMockBuilder(EncoderInterface::class)->getMock();
        $this->encoder->expects($this->any())->method('encode')->will(
            $this->returnValue($this->testMessage)
        );

        $this->transport = $this->getTransport(0);
    }

    protected function getTransport($chunkSize)
    {
        // initialize transport with an unlimited packet-size
        // and the mocked message encoder
        $transport = new UdpTransport(null, null, $chunkSize);
        $transport->setMessageEncoder($this->encoder);

        // replace internal stream socket client with our mock
        $reflectedTransport = new \ReflectionObject($transport);
        $reflectedClient = $reflectedTransport->getProperty('socketClient');
        $reflectedClient->setAccessible(true);
        $reflectedClient->setValue($transport, $this->socketClient);

        return $transport;
    }

    public function testSetEncoder(): void
    {
        /** @var EncoderInterface|MockObject $encoder */
        $encoder = $this->getMockBuilder(EncoderInterface::class)->getMock();
        $this->transport->setMessageEncoder($encoder);

        $this->assertEquals($encoder, $this->transport->getMessageEncoder());
    }

    public function testGetEncoder(): void
    {
        $transport = new UdpTransport();
        $this->assertInstanceOf(
            EncoderInterface::class,
            $transport->getMessageEncoder()
        );
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
        $chunkSize = 10;
        $transport = $this->getTransport(10);
        $expectedMessageCount =  \strlen($this->testMessage) / $chunkSize;

        $this->socketClient
            ->expects($this->exactly($expectedMessageCount))
            ->method('write');

        $transport->send($this->message);
    }

    public function testInvalidChunkNumber(): void
    {
        $this->expectException(\RuntimeException::class);

        $transport = $this->getTransport(1);
        $transport->send($this->message);
    }
}
