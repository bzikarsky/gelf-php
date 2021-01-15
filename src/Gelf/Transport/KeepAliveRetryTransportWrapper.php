<?php

namespace Gelf\Transport;

use RuntimeException;

class KeepAliveRetryTransportWrapper extends RetryTransportWrapper
{
    /**
     * @const string
     */
    const NO_RESPONSE = "Graylog-Server didn't answer properly, expected 'HTTP/1.x 202 Accepted', response is ''";

    /**
     * @param HttpTransport $transport
     */
    public function __construct(HttpTransport $transport)
    {
        parent::__construct($transport, 1, function (RuntimeException $e) {
            return $e->getMessage() === self::NO_RESPONSE;
        });
    }
}
