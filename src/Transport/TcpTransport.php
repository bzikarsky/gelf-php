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

namespace Gelf\Transport;

use Gelf\Transport\Encoder\EncoderInterface;
use Gelf\Transport\Encoder\JsonEncoder;
use Gelf\Transport\Stream\SslOptions;
use Gelf\Transport\Stream\StreamSocketClient;

/**
 * TcpTransport allows the transfer of GELF-messages (with SSL/TLS support)
 * to a compatible GELF-TCP-backend as described in
 * https://github.com/Graylog2/graylog2-docs/wiki/GELF
 *
 * It can also act as a direct publisher
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 * @author Ahmed Trabelsi <ahmed.trabelsi@proximedia.fr>
 */
class TcpTransport implements TransportInterface
{
    public const DEFAULT_HOST = '127.0.0.1';

    public const DEFAULT_PORT = 12201;

    public const AUTO_SSL_PORT = 12202;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var StreamSocketClient
     */
    private $socketClient;

    /**
     * @var SslOptions|null
     */
    private $sslOptions = null;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * Class constructor
     *
     * @param string     $host
     * @param int        $port
     * @param SslOptions $sslOptions
     */
    public function __construct(
        string $host = self::DEFAULT_HOST,
        int $port = self::DEFAULT_PORT,
        SslOptions $sslOptions = null
    ) {
        $this->host = $host;
        $this->port = $port;

        if (self::AUTO_SSL_PORT === $port && null === $sslOptions) {
            $sslOptions = new SslOptions();
        }

        $this->sslOptions = $sslOptions;

        $this->encoder = new JsonEncoder();
        $this->socketClient = new StreamSocketClient(
            $this->getScheme(),
            $this->host,
            $this->port,
            $this->getContext()
        );
    }

    /** @inheritdoc */
    public function send(array $data): void
    {
        $rawMessage = $this->encoder->encode($data) . "\0";

        // send message in one packet
        $this->socketClient->write($rawMessage);
    }

    private function getScheme(): string
    {
        return null === $this->sslOptions ? 'tcp' : 'ssl';
    }

    private function getContext(): array
    {
        if (null === $this->sslOptions) {
            return [];
        }

        return $this->sslOptions->toStreamContext($this->host);
    }

    /**
     * Set the connect-timeout (seconds)
     *
     * @param int $timeout
     * @return self
     */
    public function setConnectTimeout(int $timeout): self
    {
        $this->socketClient->setConnectTimeout($timeout);

        return $this;
    }

    /**
     * Return the connect-timeout (seconds)
     *
     * @return int
     */
    public function getConnectTimeout(): int
    {
        return $this->socketClient->getConnectTimeout();
    }
}
