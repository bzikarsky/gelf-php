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

use Exception;
use Gelf\MessageInterface as Message;
use Throwable;

/**
 * A wrapper for any AbstractTransport to ignore any kind of errors
 * @package Gelf\Transport
 */
class IgnoreErrorTransportWrapper extends AbstractTransport
{

    /**
     * @var TransportInterface
     */
    private $transport;

    /**
     * @var \Exception|null
     */
    private $lastError = null;

    /**
     * IgnoreErrorTransportWrapper constructor.
     *
     * @param TransportInterface $transport
     */
    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * Sends a Message over this transport.
     *
     * @param Message $message
     *
     * @return int the number of bytes sent
     */
    public function send(Message $message): int
    {
        try {
            return $this->transport->send($message);
        } catch (\Throwable $e) {
            $this->lastError = $e;
            return 0;
        }
    }

    /**
     * Returns the last error
     * @return \Throwable|null
     */
    public function getLastError(): Throwable
    {
        return $this->lastError;
    }
}
