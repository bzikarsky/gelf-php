<?php

namespace Gelf\Transport;

use Gelf\MessageInterface as Message;
use RuntimeException;

class RetryTransportWrapper extends AbstractTransport
{
    /**
     * @const string
     */
    const NO_RESPONSE = "Graylog-Server didn't answer properly, expected 'HTTP/1.x 202 Accepted', response is ''";

    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * @var int
     */
    protected $maxRetries;

    /**
     * @var callable|null
     */
    protected $exceptionMatcher;

    /**
     * KeepAliveRetryTransportWrapper constructor.
     *
     * @param TransportInterface $transport
     * @param int $maxRetries
     * @param callable(\Throwable):bool $exceptionMatcher
     */
    public function __construct(TransportInterface $transport, $maxRetries, $exceptionMatcher = null)
    {
        $this->transport = $transport;
        $this->maxRetries = $maxRetries;
        $this->exceptionMatcher = $exceptionMatcher;
    }

    /**
     * @return TransportInterface
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * Sends a Message over this transport.
     *
     * @param Message $message
     *
     * @return int calls function to send message
     */
    public function send(Message $message)
    {
        $tries = 0;

        while (true) {
            try {
                $tries++;
                return $this->transport->send($message);
            } catch (\Exception $e) {
                if ($this->maxRetries !== 0 && $tries > $this->maxRetries) {
                    throw $e;
                }

                if ($this->exceptionMatcher && !call_user_func($this->exceptionMatcher, $e)) {
                    throw $e;
                }
            }
        }
    }
}
