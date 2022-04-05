<?php
declare(strict_types=1);

namespace Gelf\Transport;

use RuntimeException;

class KeepAliveRetryTransportWrapper extends RetryTransportWrapper
{
    private const NO_RESPONSE
        = "Graylog-Server didn't answer properly, expected 'HTTP/1.x 202 Accepted', response is ''";

    public function __construct(HttpTransport $transport)
    {
        parent::__construct($transport, 1, function (RuntimeException $e) {
            return $e->getMessage() === self::NO_RESPONSE;
        });
    }
}
