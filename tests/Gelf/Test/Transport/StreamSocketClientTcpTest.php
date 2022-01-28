<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf\Test\Transport;

use Gelf\Transport\StreamSocketClient;
use Gelf\TestCase;

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

    protected $host = "127.0.0.1";
    protected $port;

    public function setUp(): void
    {
        $host = $this->host;
        $this->serverSocket = stream_socket_server(
            "tcp://$host:0",
            $errNo,
            $errMsg
        );

        if (!$this->serverSocket) {
            throw new \RuntimeException("Failed to create test-server-socket");
        }

        // get random port
        $socketName = stream_socket_get_name(
            $this->serverSocket,
            $peerName = false
        );
        list(, $port) = explode(":", $socketName);

        $this->socketClient = new StreamSocketClient('tcp', $host, $port);
        $this->port = $port;
    }

    public function tearDown(): void
    {
        unset($this->socketClient);
        if ($this->serverSocket !== null) {
            fclose($this->serverSocket);
            $this->serverSocket = null;
        }
    }

    public function testGetSocket()
    {
        $this->assertIsResource($this->socketClient->getSocket());
    }

    public function testWrite()
    {
        $testData = "Hello World!";
        $numBytes = $this->socketClient->write($testData);

        $this->assertEquals(strlen($testData), $numBytes);

        // check that message is sent to server
        $connection = stream_socket_accept($this->serverSocket);
        $readData = fread($connection, $numBytes);

        $this->assertEquals($testData, $readData);
    }

    public function testBadWrite()
    {
        $this->expectException(\RuntimeException::class);
        $this->socketClient->write("Hello ");
        fclose($this->serverSocket);
        $this->serverSocket = null;
        $this->socketClient->write("world!");
    }

    public function testMultiWrite()
    {
        // lower timeout for server-socket
        stream_set_timeout($this->serverSocket, 0, 100);

        // -- first write

        $testData = "First thing in the morning should be to check,";

        $numBytes = $this->socketClient->write($testData);
        $this->assertEquals(strlen($testData), $numBytes);

        // open connection on server-socket
        $serverConnection = stream_socket_accept($this->serverSocket);

        $readData = fread($serverConnection, $numBytes);
        $this->assertEquals($testData, $readData);

        // -- second write

        $testData = "if we can write multiple times on the same socket";

        $numBytes = $this->socketClient->write($testData);
        $this->assertEquals(strlen($testData), $numBytes);

        $readData = fread($serverConnection, $numBytes);
        $this->assertEquals($testData, $readData);

        fclose($serverConnection);
    }

    public function testRead()
    {
        $testData = "Hello Reader :)";

        $numBytes = $this->socketClient->write($testData);
        $this->assertEquals(strlen($testData), $numBytes);

        // lower timeout for server-socket
        stream_set_timeout($this->serverSocket, 0, 100);

        $connection = stream_socket_accept($this->serverSocket);

        // return input as output
        stream_copy_to_stream($connection, $connection, strlen($testData));

        fclose($connection);
        $readData = $this->socketClient->read($numBytes);

        $this->assertEquals($testData, $readData);
    }

    public function testReadContents()
    {
        $testData = str_repeat("0123456789", mt_rand(1, 10));

        $numBytes = $this->socketClient->write($testData);
        $this->assertEquals(strlen($testData), $numBytes);

        // lower timeout for server-socket
        stream_set_timeout($this->serverSocket, 0, 100);

        $connection = stream_socket_accept($this->serverSocket);

        // return input as output
        stream_copy_to_stream($connection, $connection, strlen($testData));

        fclose($connection);

        $readData = $this->socketClient->read(1024);

        $this->assertEquals($testData, $readData);
    }

    public function testCloseWithoutConnectionWrite()
    {
        // close unopened stream
        $this->socketClient->close();
        $this->assertTrue($this->socketClient->isClosed());

        $this->socketClient->write("abcd");
        $this->assertFalse($this->socketClient->isClosed());
        $client = stream_socket_accept($this->serverSocket);
        $this->assertEquals("abcd", fread($client, 4));
    }

    public function testCloseWrite()
    {
        $this->socketClient->write("abcd");
        $this->assertFalse($this->socketClient->isClosed());
        $client = stream_socket_accept($this->serverSocket);
        $this->assertEquals("abcd", fread($client, 4));

        $this->socketClient->close();
        $this->assertTrue($this->socketClient->isClosed());

        $this->socketClient->write("efgh");
        $client2 = stream_socket_accept($this->serverSocket);
        $this->assertEquals("efgh", fread($client2, 4));
    }

    /**
     * @group hhvm-failures
     */
    public function testStreamContext()
    {
        $this->failsOnHHVM();

        $testName = '127.0.0.1:12345';
        $context = array(
            'socket' => array(
                'bindto' => $testName
            )
        );

        $client = new StreamSocketClient("tcp", $this->host, $this->port, $context);
        $this->assertEquals($context, $client->getContext());

        $this->assertEquals($testName, stream_socket_get_name($client->getSocket(), false));
        $this->assertNotEquals($testName, stream_socket_get_name($this->socketClient->getSocket(), false));
    }

    /**
     * @group hhvm-failures
     */
    public function testUpdateStreamContext()
    {
        $this->failsOnHHVM();

        $testName = '127.0.0.1:12345';
        $context = array(
            'socket' => array(
                'bindto' => $testName
            )
        );

        $this->assertEquals(array(), $this->socketClient->getContext());
        $this->assertNotEquals($testName, stream_socket_get_name($this->socketClient->getSocket(), false));
        $this->socketClient->close();

        $this->socketClient->setContext($context);
        $this->assertEquals($context, $this->socketClient->getContext());

        $this->assertEquals($testName, stream_socket_get_name($this->socketClient->getSocket(), false));
    }

    public function testSetContextFailsAfterConnect()
    {
        $this->expectException(\LogicException::class);
        // enforce connect
        $this->socketClient->getSocket();

        $this->socketClient->setContext(array("foo" => "bar"));
    }

    public function testSetConnectTimeoutFailsAfterConnect()
    {
        $this->expectException(\LogicException::class);
        // enforce connect
        $this->socketClient->getSocket();

        $this->socketClient->setConnectTimeout(1);
    }

    public function testConnectTimeout()
    {
        $this->assertEquals(StreamSocketClient::SOCKET_TIMEOUT, $this->socketClient->getConnectTimeout());
        $this->socketClient->setConnectTimeout(1);
        $this->assertEquals(1, $this->socketClient->getConnectTimeout());
    }
}
