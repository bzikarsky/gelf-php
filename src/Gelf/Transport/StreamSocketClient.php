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
    private const DEFAULT_SOCKET_TIMEOUT = 30;

    private mixed $socket = null;
    private int $connectTimeout = self::DEFAULT_SOCKET_TIMEOUT;

    public function __construct(
        private string $scheme,
        private string $host,
        private int $port,
        private array $context = []
    ) {
    }

    /**
     * Destructor, closes socket if possible
     */
    public function __destruct()
    {
        $this->close();
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
    private function buildSocket(): mixed
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
            stream_set_blocking($socket, true);
        }

        return $socket;
    }

    /**
     * Returns raw-socket-resource
     *
     * @return resource
     */
    public function getSocket(): mixed
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
     */
    public function write(string $buffer): int
    {
        $bufLen = strlen($buffer);

        $socket = $this->getSocket();
        $written = 0;

        while ($written < $bufLen) {
            // PHP's fwrite does not behave nice in regard to errors, so we wrap
            // it with a temporary error handler and treat every warning/notice as
            // an error
            $failed = false;
            $errorMessage = "Failed to write to socket";
            /** @psalm-suppress InvalidArgument */
            set_error_handler(function ($errno, $errstr) use (&$failed, &$errorMessage) {
                $failed = true;
                $errorMessage .= ": $errstr ($errno)";
            });
            $byteCount = fwrite($socket, substr($buffer, $written));
            restore_error_handler();

            if ($byteCount === 0 && defined('HHVM_VERSION')) {
                $failed = true;
            }

            if ($failed || $byteCount === false) {
                throw new RuntimeException($errorMessage);
            }

            $written += $byteCount;
        }


        return $written;
    }

    /**
     * Reads a given number of bytes from the socket
     */
    public function read(int $byteCount): string
    {
        return fread($this->getSocket(), $byteCount);
    }

    /**
     * Closes underlying socket explicitly
     */
    public function close(): void
    {
        if (!is_resource($this->socket)) {
            return;
        }

        fclose($this->socket);
        $this->socket = null;
    }

    /**
     * Checks if the socket is closed
     */
    public function isClosed(): bool
    {
        return $this->socket === null;
    }

    /**
     * Returns the current connect-timeout
     */
    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    /**
     * Sets the connect-timeout
     */
    public function setConnectTimeout(int $timeout): void
    {
        if (!$this->isClosed()) {
            throw new \LogicException("Cannot change socket properties with an open connection");
        }

        $this->connectTimeout = $timeout;
    }

    /**
     * Returns the stream context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Sets the stream context
     */
    public function setContext(array $context): void
    {
        if (!$this->isClosed()) {
            throw new \LogicException("Cannot change socket properties with an open connection");
        }

        $this->context = $context;
    }
}
