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

    public function setUp()
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

    public function tearDown()
    {
        unset($this->socketClient);
        fclose($this->serverSocket);
    }

    public function testGetSocket()
    {
        $this->assertTrue(is_resource($this->socketClient->getSocket()));
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

    public function testDestructorWithoutSocket()
    {
        unset($this->socketClient);
    }

    public function testDestructorWithSocket()
    {
        $this->socketClient->getSocket();
        unset($this->socketClient);
    }

    public function testCloseWithoutConnectionWrite()
    {
        // close unopened stream
        $this->socketClient->close();
        $this->socketClient->write("abcd");
        $client = stream_socket_accept($this->serverSocket);
        $this->assertEquals("abcd", fread($client, 4));
    }

    public function testCloseWrite()
    {
        $this->socketClient->write("abcd");
        $client = stream_socket_accept($this->serverSocket);
        $this->assertEquals("abcd", fread($client, 4));

        $this->socketClient->close();

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
        $this->assertEquals($testName, stream_socket_get_name($client->getSocket(), false));
        $this->assertNotEquals($testName, stream_socket_get_name($this->socketClient->getSocket(), false));
    }
}
