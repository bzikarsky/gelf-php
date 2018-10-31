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
use Gelf\Transport\SslOptions;
use Gelf\Transport\StreamSocketClient;
use Gelf\Transport\TcpTransport;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TcpTransportTest extends TestCase
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
     * @var TcpTransport
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

        $this->transport = $this->getTransport();
    }

    protected function getTransport()
    {
        // initialize transport with an unlimited packet-size
        // and the mocked message encoder
        $transport = new TcpTransport(null, null);
        $transport->setMessageEncoder($this->encoder);

        // replace internal stream socket client with our mock
        $reflectedTransport = new \ReflectionObject($transport);
        $reflectedClient = $reflectedTransport->getProperty('socketClient');
        $reflectedClient->setAccessible(true);
        $reflectedClient->setValue($transport, $this->socketClient);

        return $transport;
    }

    public function testConstructor(): void
    {
        $transport = new TcpTransport();
        $this->validateTransport($transport, '127.0.0.1', 12201);

        $transport = new TcpTransport('test.local', 2202);
        $this->validateTransport($transport, 'test.local', 2202);

        // test defaults:
        //   port 12202 without explicit SSL options       => sslOptions: default
        $transport = new TcpTransport('localhost', 12202);
        $this->validateTransport($transport, 'localhost', 12202, new SslOptions());
    }

    public function validateTransport(
        TcpTransport $transport,
        $host,
        $port,
        $sslOptions = null
    ): void {
        $r = new \ReflectionObject($transport);

        foreach (['host', 'port', 'sslOptions'] as $test) {
            $p = $r->getProperty($test);
            $p->setAccessible(true);
            $this->assertEquals(${$test}, $p->getValue($transport));
        }
    }

    public function testSslOptionsAreUsed(): void
    {
        /** @var SslOptions|MockObject $sslOptions */
        $sslOptions = $this->getMockBuilder(SslOptions::class)->getMock();
        $sslOptions->expects($this->exactly(2))
            ->method('toStreamContext')
            ->will($this->returnValue(['ssl' => null]));

        $transport = new TcpTransport('localhost', '12202', $sslOptions);

        $reflectedTransport = new \ReflectionObject($transport);
        $reflectedGetContext = $reflectedTransport->getMethod('getContext');
        $reflectedGetContext->setAccessible(true);
        $context = $reflectedGetContext->invoke($transport);

        $this->assertEquals(['ssl' => null], $context);
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
        $transport = new TcpTransport();
        $this->assertInstanceOf(
            EncoderInterface::class,
            $transport->getMessageEncoder()
        );
    }

    public function testSend(): void
    {
        $this->socketClient
            ->expects($this->once())
            ->method('write')
            // TCP protocol requires every message to be
            // terminated with \0
            ->with($this->testMessage . "\0");

        $this->transport->send($this->message);
    }

    public function testConnectTimeout(): void
    {
        $this->socketClient
            ->expects($this->once())
            ->method('getConnectTimeout')
            ->will($this->returnValue(123));

        $this->assertEquals(123, $this->transport->getConnectTimeout());

        $this->socketClient
            ->expects($this->once())
            ->method('setConnectTimeout')
            ->with(123);

        $this->transport->setConnectTimeout(123);
    }
}
