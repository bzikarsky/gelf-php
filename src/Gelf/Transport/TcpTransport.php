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

use Gelf\MessageInterface as Message;
use Gelf\Encoder\JsonEncoder as DefaultEncoder;
use RuntimeException;

/**
 * TcpTransport allows the transfer of GELF-messages to an compatible
 * GELF-TCP-backend as described in
 * https://github.com/Graylog2/graylog2-docs/wiki/GELF
 *
 * It can also act as a direct publisher
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 * @author Ahmed Trabelsi <ahmed.trabelsi@proximedia.fr>
 */
class TcpTransport extends AbstractTransport
{
    const DEFAULT_HOST = "127.0.0.1";
    const DEFAULT_PORT = 12201;

    /**
     * @var StreamSocketClient
     */
    protected $socketClient;

    /**
     * Class constructor
     *
     * @param string $host      when NULL or empty DEFAULT_HOST is used
     * @param int    $port      when NULL or empty DEFAULT_PORT is used
     */
    public function __construct($host = self::DEFAULT_HOST, $port = self::DEFAULT_PORT)
    {
        // allow NULL-like values for fallback on default
        $host = $host ?: self::DEFAULT_HOST;
        $port = $port ?: self::DEFAULT_PORT;

        $this->socketClient = new StreamSocketClient('tcp', $host, $port);
        $this->messageEncoder = new DefaultEncoder();
    }

    /**
     * Sends a Message over this transport
     *
     * @param Message $message
     *
     * @return int the number of TCP packets sent
     */
    public function send(Message $message)
    {
        $rawMessage = $this->getMessageEncoder()->encode($message) . "\0";
        
        // send message in one packet
        $this->socketClient->write($rawMessage);

        return 1;
    }

    /**
     * Sets the connect-timeout
     *
     * @param int $timeout
     */
    public function setConnectTimeout($timeout)
    {
        $this->socketClient->setConnectTimeout($timeout);
    }

    /**
     * Returns the connect-timeout
     *
     * @return int
     */
    public function getConnectTimeout()
    {
        return $this->socketClient->getConnectTimeout();
    }
}
