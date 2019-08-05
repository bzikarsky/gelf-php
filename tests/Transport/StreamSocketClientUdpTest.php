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

use Gelf\Transport\Stream\StreamSocketClient;
use PHPUnit\Framework\TestCase;

class StreamSocketClientUdpTest extends TestCase
{
    /**
     * @var StreamSocketClient
     */
    protected $socketClient;

    /**
     * @var resource
     */
    protected $serverSocket;

    protected function setUp(): void
    {
        $host = '127.0.0.1';
        $this->serverSocket = \stream_socket_server(
            "udp://$host:0",
            $errNo,
            $errMsg,
            $flags = STREAM_SERVER_BIND
        );

        if (!$this->serverSocket) {
            throw new \RuntimeException('Failed to create test-server-socket');
        }

        // get random port
        $socketName = \stream_socket_get_name(
            $this->serverSocket,
            $peerName = false
        );
        [, $port] = \explode(':', $socketName);

        $this->socketClient = new StreamSocketClient('udp', $host, $port);
    }

    protected function tearDown(): void
    {
        unset($this->socketClient);
        \fclose($this->serverSocket);
    }

    public function testInvalidConstructorArguments(): void
    {
        $this->expectException(\RuntimeException::class);

        $client = new StreamSocketClient('not-a-scheme', 'not-a-host', -1);
        $client->getSocket();
    }

    public function testGetSocket(): void
    {
        $this->assertInternalType('resource', $this->socketClient->getSocket());
    }

    public function testWrite(): void
    {
        $testData = 'Hello World!';
        $numBytes = $this->socketClient->write($testData);

        $this->assertEquals(\strlen($testData), $numBytes);

        // check that message is sent to server
        $readData = \fread($this->serverSocket, $numBytes);

        $this->assertEquals($testData, $readData);
    }

    public function testDestructorWithoutSocket(): void
    {
        unset($this->socketClient);

        $this->addToAssertionCount(1);
    }

    public function testDestructorWithSocket(): void
    {
        $this->socketClient->getSocket();
        unset($this->socketClient);

        $this->addToAssertionCount(1);
    }
}
