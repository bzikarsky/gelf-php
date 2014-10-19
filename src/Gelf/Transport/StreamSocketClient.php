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
     * @var resource
     */
    protected $socket;

    /**
     * @param string  $scheme
     * @param string  $host
     * @param integer $port
     */
    public function __construct($scheme, $host, $port)
    {
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
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
     * @param string  $scheme like "udp" or "tcp"
     * @param string  $host
     * @param integer $port
     *
     * @return resource
     *
     * @throws RuntimeException on connection-failure
     */
    protected static function initSocket($scheme, $host, $port)
    {
        $socketDescriptor = sprintf("%s://%s:%d", $scheme, $host, $port);
        $socket = @stream_socket_client(
            $socketDescriptor,
            $errNo,
            $errStr,
            static::SOCKET_TIMEOUT
        );

        if ($socket === false) {
            throw new RuntimeException(
                sprintf(
                    "Failed to create socket-client for %si: %s (%s)",
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
     * Returns raw-socket-resource
     *
     * @return resource
     */
    public function getSocket()
    {
        // lazy initializing of socket-descriptor
        if (!$this->socket) {
            $this->socket = self::initSocket(
                $this->scheme,
                $this->host,
                $this->port
            );
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
        $socket = $this->getSocket();
        $byteCount = @fwrite($socket, $buffer);

        if ($byteCount === false) {
            throw new \RuntimeException("Failed to write to socket");
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
}
