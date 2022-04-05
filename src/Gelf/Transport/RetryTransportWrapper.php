<?php
declare(strict_types=1);

namespace Gelf\Transport;

use Closure;
use Gelf\MessageInterface as Message;
use Throwable;

class RetryTransportWrapper implements TransportInterface
{
    protected Closure $exceptionMatcher;

    /**
     * KeepAliveRetryTransportWrapper constructor.
     *
     * @param null|callable(Throwable):bool $exceptionMatcher
     */
    public function __construct(
        private TransportInterface $transport,
        private int $maxRetries,
        ?callable $exceptionMatcher = null
    ) {
        $this->exceptionMatcher = Closure::fromCallable($exceptionMatcher ?? fn (Throwable $_) => true);
    }

    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): int
    {
        $tries = 0;

        while (true) {
            try {
                $tries++;
                return $this->transport->send($message);
            } catch (Throwable $e) {
                if ($this->maxRetries !== 0 && $tries > $this->maxRetries) {
                    throw $e;
                }

                if (!call_user_func($this->exceptionMatcher, $e)) {
                    throw $e;
                }
            }
        }
    }
}
