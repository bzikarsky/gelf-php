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

use Gelf\TestCase;
use Gelf\Transport\StreamSocketClient;

class StreamSocketClientTcpTest extends TestCase
{
    /**
     * @var StreamSocketClient
     */
    protected $socketClient;

    /**
     * @var resource
     */
    protected $serverSocket;

    protected $host = '127.0.0.1';

    protected $port;

    protected function setUp(): void
    {
        $host = $this->host;
        $this->serverSocket = \stream_socket_server(
            "tcp://$host:0",
            $errNo,
            $errMsg
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

        $this->socketClient = new StreamSocketClient('tcp', $host, $port);
        $this->port = $port;
    }

    protected function tearDown(): void
    {
        unset($this->socketClient);
        if (null !== $this->serverSocket) {
            \fclose($this->serverSocket);
            $this->serverSocket = null;
        }
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
        $connection = \stream_socket_accept($this->serverSocket);
        $readData = \fread($connection, $numBytes);

        $this->assertEquals($testData, $readData);
    }

    public function testBadWrite(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->socketClient->write('Hello ');
        \fclose($this->serverSocket);
        $this->serverSocket = null;
        $this->socketClient->write('world!');
    }

    public function testMultiWrite(): void
    {
        // lower timeout for server-socket
        \stream_set_timeout($this->serverSocket, 0, 100);

        // -- first write

        $testData = 'First thing in the morning should be to check,';

        $numBytes = $this->socketClient->write($testData);
        $this->assertEquals(\strlen($testData), $numBytes);

        // open connection on server-socket
        $serverConnection = \stream_socket_accept($this->serverSocket);

        $readData = \fread($serverConnection, $numBytes);
        $this->assertEquals($testData, $readData);

        // -- second write

        $testData = 'if we can write multiple times on the same socket';

        $numBytes = $this->socketClient->write($testData);
        $this->assertEquals(\strlen($testData), $numBytes);

        $readData = \fread($serverConnection, $numBytes);
        $this->assertEquals($testData, $readData);

        \fclose($serverConnection);
    }

    public function testRead(): void
    {
        $testData = 'Hello Reader :)';

        $numBytes = $this->socketClient->write($testData);
        $this->assertEquals(\strlen($testData), $numBytes);

        // lower timeout for server-socket
        \stream_set_timeout($this->serverSocket, 0, 100);

        $connection = \stream_socket_accept($this->serverSocket);

        // return input as output
        \stream_copy_to_stream($connection, $connection, \strlen($testData));

        \fclose($connection);
        $readData = $this->socketClient->read($numBytes);

        $this->assertEquals($testData, $readData);
    }

    public function testReadContents(): void
    {
        $testData = \str_repeat('0123456789', \random_int(1, 10));

        $numBytes = $this->socketClient->write($testData);
        $this->assertEquals(\strlen($testData), $numBytes);

        // lower timeout for server-socket
        \stream_set_timeout($this->serverSocket, 0, 100);

        $connection = \stream_socket_accept($this->serverSocket);

        // return input as output
        \stream_copy_to_stream($connection, $connection, \strlen($testData));

        \fclose($connection);

        $readData = $this->socketClient->read(1024);

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

    public function testCloseWithoutConnectionWrite(): void
    {
        // close unopened stream
        $this->socketClient->close();
        $this->assertTrue($this->socketClient->isClosed());

        $this->socketClient->write('abcd');
        $this->assertFalse($this->socketClient->isClosed());
        $client = \stream_socket_accept($this->serverSocket);
        $this->assertEquals('abcd', \fread($client, 4));
    }

    public function testCloseWrite(): void
    {
        $this->socketClient->write('abcd');
        $this->assertFalse($this->socketClient->isClosed());
        $client = \stream_socket_accept($this->serverSocket);
        $this->assertEquals('abcd', \fread($client, 4));

        $this->socketClient->close();
        $this->assertTrue($this->socketClient->isClosed());

        $this->socketClient->write('efgh');
        $client2 = \stream_socket_accept($this->serverSocket);
        $this->assertEquals('efgh', \fread($client2, 4));
    }

    /**
     * @group hhvm-failures
     */
    public function testStreamContext(): void
    {
        $this->failsOnHHVM();

        $testName = '127.0.0.1:12345';
        $context = [
            'socket' => [
                'bindto' => $testName
            ]
        ];

        $client = new StreamSocketClient('tcp', $this->host, $this->port, $context);
        $this->assertEquals($context, $client->getContext());

        $this->assertEquals($testName, \stream_socket_get_name($client->getSocket(), false));
        $this->assertNotEquals($testName, \stream_socket_get_name($this->socketClient->getSocket(), false));
    }

    /**
     * @group hhvm-failures
     */
    public function testUpdateStreamContext(): void
    {
        $this->failsOnHHVM();

        $testName = '127.0.0.1:12345';
        $context = [
            'socket' => [
                'bindto' => $testName
            ]
        ];

        $this->assertEquals([], $this->socketClient->getContext());
        $this->assertNotEquals($testName, \stream_socket_get_name($this->socketClient->getSocket(), false));
        $this->socketClient->close();

        $this->socketClient->setContext($context);
        $this->assertEquals($context, $this->socketClient->getContext());

        $this->assertEquals($testName, \stream_socket_get_name($this->socketClient->getSocket(), false));
    }

    public function testSetContextFailsAfterConnect(): void
    {
        $this->expectException(\LogicException::class);

        // enforce connect
        $this->socketClient->getSocket();

        $this->socketClient->setContext(['foo' => 'bar']);
    }

    public function testSetConnectTimeoutFailsAfterConnect(): void
    {
        $this->expectException(\LogicException::class);

        // enforce connect
        $this->socketClient->getSocket();

        $this->socketClient->setConnectTimeout(1);
    }

    public function testConnectTimeout(): void
    {
        $this->assertEquals(StreamSocketClient::SOCKET_TIMEOUT, $this->socketClient->getConnectTimeout());
        $this->socketClient->setConnectTimeout(1);
        $this->assertEquals(1, $this->socketClient->getConnectTimeout());
    }
}
