<?php
declare(strict_types=1);

namespace Gelf\Transport;

use Gelf\MessageInterface as Message;
use Throwable;

/**
 * A wrapper for any AbstractTransport to ignore any kind of errors
 * @package Gelf\Transport
 */
class IgnoreErrorTransportWrapper implements TransportInterface
{
    private ?Throwable $lastError = null;

    public function __construct(
        private TransportInterface $transport
    ) {
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): int
    {
        try {
            return $this->transport->send($message);
        } catch (Throwable $e) {
            $this->lastError = $e;
            return 0;
        }
    }

    /**
     * Returns the last error
     */
    public function getLastError(): ?Throwable
    {
        return $this->lastError;
    }
}
