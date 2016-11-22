<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf\Transport;

use RuntimeException;

/**
 * StreamSocketClient is a very simple OO-Wrapper around the PHP
 * stream_socket-library and some specific stream-functions like
 * fwrite, etc.
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class StreamSocketClient
{
    /**
     * @deprecated deprecated since v1.4.0
     */
    const SOCKET_TIMEOUT = 30;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var integer
     */
    protected $port;

    /**
     * @var string
     */
    protected $scheme;

    /**
     * @var array
     */
    protected $context;

    /**
     * @var resource
     */
    protected $socket;

    /**
     * @var int
     */
    protected $connectTimeout = self::SOCKET_TIMEOUT;

    /**
     * @param string  $scheme
     * @param string  $host
     * @param integer $port
     * @param array   $context
     */
    public function __construct($scheme, $host, $port, array $context = array())
    {
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->context = $context;
    }

    /**
     * Destructor, closes socket if possible
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Initializes socket-client
     *
     * @deprecated deprecated since v1.4.0
     *
     * @param string  $scheme  like "udp" or "tcp"
     * @param string  $host
     * @param integer $port
     * @param array   $context
     *
     * @return resource
     *
     * @throws RuntimeException on connection-failure
     */
    protected static function initSocket($scheme, $host, $port, array $context)
    {
        $socketDescriptor = sprintf("%s://%s:%d", $scheme, $host, $port);
        $socket = @stream_socket_client(
            $socketDescriptor,
            $errNo,
            $errStr,
            static::SOCKET_TIMEOUT,
            \STREAM_CLIENT_CONNECT,
            stream_context_create($context)
        );

        if ($socket === false) {
            throw new RuntimeException(
                sprintf(
                    "Failed to create socket-client for %s: %s (%s)",
                    $socketDescriptor,
                    $errStr,
                    $errNo
                )
            );
        }

        // set non-blocking for UDP
        if (strcasecmp("udp", $scheme) == 0) {
            stream_set_blocking($socket, 0);
        }

        return $socket;
    }


    /**
     * Internal function mimicking the behaviour of static::initSocket
     * which will get new functionality instead of the deprecated
     * "factory"
     *
     * @return resource
     *
     * @throws RuntimeException on connection-failure
     */
    private function buildSocket()
    {
        $socketDescriptor = sprintf(
            "%s://%s:%d",
            $this->scheme,
            $this->host,
            $this->port
        );

        $socket = @stream_socket_client(
            $socketDescriptor,
            $errNo,
            $errStr,
            $this->connectTimeout,
            \STREAM_CLIENT_CONNECT,
            stream_context_create($this->context)
        );

        if ($socket === false) {
            throw new RuntimeException(
                sprintf(
                    "Failed to create socket-client for %s: %s (%s)",
                    $socketDescriptor,
                    $errStr,
                    $errNo
                )
            );
        }

        // set non-blocking for UDP
        if (strcasecmp("udp", $this->scheme) == 0) {
            stream_set_blocking($socket, 0);
        }

        return $socket;
    }

    /**
     * Returns raw-socket-resource
     *
     * @return resource
     */
    public function getSocket()
    {
        // lazy initializing of socket-descriptor
        if (!$this->socket) {
            $this->socket = $this->buildSocket();
        }

        return $this->socket;
    }

    /**
     * Writes a given string to the socket and returns the
     * number of written bytes
     *
     * @param string $buffer
     *
     * @return int
     *
     * @throws RuntimeException on write-failure
     */
    public function write($buffer)
    {
        $buffer = (string) $buffer;
        $socket = $this->getSocket();
        $byteCount = @fwrite($socket, $buffer);
        $bufLen = strlen(bin2hex($buffer))/2;

        if ($byteCount === false) {
            throw new \RuntimeException("Failed to write to socket");
        }

        if ($byteCount !== $bufLen) {
            throw new \RuntimeException("Incomplete write: Only $byteCount of $bufLen written");
        }

        return $byteCount;
    }

    /**
     * Reads a given number of bytes from the socket
     *
     * @param integer $byteCount
     *
     * @return string
     */
    public function read($byteCount)
    {
        return fread($this->getSocket(), $byteCount);
    }

    /**
     * Closes underlying socket explicitly
     */
    public function close()
    {
        if (!is_resource($this->socket)) {
            return;
        }

        fclose($this->socket);
        $this->socket = null;
    }

    /**
     * Returns the current connect-timeout
     *
     * @return int
     */
    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }

    /**
     * Sets the connect-timeout
     *
     * @param int $timeout
     */
    public function setConnectTimeout($timeout)
    {
        $this->connectTimeout = $timeout;
    }
}
