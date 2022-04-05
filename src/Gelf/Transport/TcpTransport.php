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

use Gelf\Encoder\EncoderInterface;
use Gelf\Encoder\NoNullByteEncoderInterface;
use Gelf\MessageInterface as Message;
use Gelf\Encoder\JsonEncoder as DefaultEncoder;
use InvalidArgumentException;

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
class TcpTransport extends AbstractTransport
{
    private const DEFAULT_HOST = "127.0.0.1";
    private const DEFAULT_PORT = 12201;
    private const AUTO_SSL_PORT = 12202;

    private StreamSocketClient $socketClient;

    public function __construct(
        private string $host = self::DEFAULT_HOST,
        private int $port = self::DEFAULT_PORT,
        private ?SslOptions $sslOptions = null
    ) {
        parent::__construct();

        if ($port == self::AUTO_SSL_PORT && $this->sslOptions == null) {
            $this->sslOptions = new SslOptions();
        }

        $this->socketClient = new StreamSocketClient(
            $this->getScheme(),
            $this->host,
            $this->port,
            $this->getContext()
        );
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): int
    {
        $rawMessage = $this->getMessageEncoder()->encode($message) . "\0";

        // send message in one packet
        return $this->socketClient->write($rawMessage);
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
     * Sets the connect-timeout
     */
    public function setConnectTimeout(int $timeout): void
    {
        $this->socketClient->setConnectTimeout($timeout);
    }

    /**
     * Returns the connect-timeout
     */
    public function getConnectTimeout(): int
    {
        return $this->socketClient->getConnectTimeout();
    }

    public function setMessageEncoder(EncoderInterface $encoder): static
    {
        if (!$encoder instanceof NoNullByteEncoderInterface) {
            throw new InvalidArgumentException(
                "TcpTransport only works with NoNullByteEncoderInterface encoders"
            );
        }

        parent::setMessageEncoder($encoder);
        return $this;
    }
}
