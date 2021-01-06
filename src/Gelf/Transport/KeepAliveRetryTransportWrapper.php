<?php

namespace Gelf\Transport;

use Gelf\MessageInterface as Message;

class KeepAliveRetryTransportWrapper extends AbstractTransport
{
    /**
     * @const string
     */
    const NO_RESPONSE = "Graylog-Server didn't answer properly, expected 'HTTP/1.x 202 Accepted', response is ''";

    /**
     * @var HttpTransport
     */
    protected $transport;

    /**
     * @var int
     */
    protected $maxRetries;

    /**
     * @var int
     */
    protected $incrementedRetries = 0;

    /**
     * KeepAliveRetryTransportWrapper constructor.
     *
     * @param TransportInterface $transport
     */
    public function __construct(TransportInterface $transport, int $maxRetries)
    {
        $this->transport = $transport;
        $this->maxRetries = $maxRetries;

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
        $this->incrementedRetries++;
        try {
            return $this->transport->send($message);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== self::NO_RESPONSE) {
                throw $e;
            }
            if($this->incrementedRetries === $this->maxRetries){
                return $this->transport->send($message);
            }
            return $this->send($message);
        }
    }
}
