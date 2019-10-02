<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf\Encoder;

use Gelf\MessageInterface;

/**
 * The JsonEncoder allows the encoding of GELF messages as described
 * in http://www.graylog2.org/resources/documentation/sending/gelfhttp
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class JsonEncoder implements NoNullByteEncoderInterface
{
    /**
     * Encodes a given message
     *
     * @param  MessageInterface $message
     * @return string
     */
    public function encode(MessageInterface $message)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return json_encode($message->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return json_encode($message->toArray());
    }
}
