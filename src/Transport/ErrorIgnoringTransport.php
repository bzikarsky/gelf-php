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

/**
 * A wrapper for any TransportInterface to ignore all transport-related exceptions
 */
class ErrorIgnoringTransport implements TransportInterface
{
    /**
     * @var TransportInterface
     */
    private $transport;

    /**
     * @var TransportException|null
     */
    private $lastException = null;

    /**
     * IgnoreErrorTransportWrapper constructor.
     *
     * @param TransportInterface $transport
     */
    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /** @inheritdoc */
    public function send(array $data): void
    {
        try {
            $this->transport->send($data);
        } catch (TransportException $e) {
            $this->lastException = $e;
        }
    }

    /**
     * Returns the last error
     *
     * @return TransportException|null
     */
    public function getLastException(): ?TransportException
    {
        return $this->lastException;
    }
}
