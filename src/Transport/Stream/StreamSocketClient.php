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

namespace Gelf\Transport\Stream;

use Gelf\Transport\TransportException;

/**
 * StreamSocketClient is a very simple OO-Wrapper around the PHP
 * stream_socket-library and some specific stream-functions like
 * fwrite, etc.
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 * @internal
 */
class StreamSocketClient
{
    /**
     * @deprecated deprecated since v1.4.0
     */
    public const SOCKET_TIMEOUT = 30;

    /**
     * @var string
     */
    private $host;

    /**
     * @var integer
     */
    private $port;

    /**
     * @var string
     */
    private $scheme;

    /**
     * @var array
     */
    private $context;

    /**
     * @var resource
     */
    private $socket;

    /**
     * @var int
     */
    private $connectTimeout = self::SOCKET_TIMEOUT;

    /**
     * Create a StreamSocketClient
     *
     * @param string  $scheme
     * @param string  $host
     * @param int     $port
     * @param array   $context
     */
    public function __construct(string $scheme, string $host, int $port, array $context = [])
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
     * Initialize a stream-socket according to the current configuration
     *
     * @return resource
     *
     * @throws TransportException on connection-failure
     */
    private function buildSocket()
    {
        $socketDescriptor = \sprintf('%s://%s:%d', $this->scheme, $this->host, $this->port);

        $socket = @\stream_socket_client(
            $socketDescriptor,
            $errNo,
            $errStr,
            $this->connectTimeout,
            \STREAM_CLIENT_CONNECT,
            \stream_context_create($this->context)
        );

        if (false === $socket) {
            throw new TransportException(
                \sprintf(
                    'Failed to create socket-client for %s: %s (%s)',
                    $socketDescriptor,
                    $errStr,
                    $errNo
                )
            );
        }

        // set non-blocking for UDP
        if (0 === \strcasecmp('udp', $this->scheme)) {
            \stream_set_blocking($socket, false);
        }

        return $socket;
    }

    /**
     * Return raw-socket-resource
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
     * Write a given string to the socket and returns the
     * number of written bytes
     *
     * @param string $buffer
     *
     * @return int
     *
     * @throws TransportException on write-failure
     */
    public function write($buffer): int
    {
        $buffer = (string) $buffer;
        $bufLen = \strlen($buffer);

        $socket = $this->getSocket();
        $written = 0;

        while ($written < $bufLen) {
            // PHP's fwrite does not behave nice in regards to errors, so we wrap
            // it with a temporary error handler and treat every warning/notice as
            // a error
            $failed = false;
            $errorMessage = 'Failed to write to socket';
            \set_error_handler(function ($errno, $errstr) use (&$failed, &$errorMessage): void {
                $failed = true;
                $errorMessage .= ": $errstr ($errno)";
            });
            $byteCount = \fwrite($socket, \substr($buffer, $written));
            \restore_error_handler();

            if (0 === $byteCount && \defined('HHVM_VERSION')) {
                $failed = true;
            }

            if ($failed || false === $byteCount) {
                throw new TransportException($errorMessage);
            }

            $written += $byteCount;
        }


        return $written;
    }

    /**
     * Read a given number of bytes from the socket
     *
     * @param int $byteCount
     *
     * @return string
     */
    public function read(int $byteCount): string
    {
        return \fread($this->getSocket(), $byteCount);
    }

    /**
     * Closes underlying socket explicitly
     */
    public function close(): void
    {
        if (!\is_resource($this->socket)) {
            return;
        }

        \fclose($this->socket);
        $this->socket = null;
    }

    /**
     * Check if the socket is closed
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return null === $this->socket;
    }

    /**
     * Return the current connect-timeout (seconds)
     *
     * @return int
     */
    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    /**
     * Set the connect-timeout (seconds)
     *
     * @param int $timeout
     * @return self
     */
    public function setConnectTimeout(int $timeout): self
    {
        if (!$this->isClosed()) {
            throw new TransportException('Cannot change socket properties with an open connection');
        }

        $this->connectTimeout = $timeout;

        return $this;
    }

    /**
     * Return the stream context
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set the stream context
     *
     * @param array $context
     * @return self
     */
    public function setContext(array $context): self
    {
        if (!$this->isClosed()) {
            throw new \LogicException('Cannot change socket properties with an open connection');
        }

        $this->context = $context;

        return $this;
    }
}
