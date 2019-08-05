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

namespace Gelf\Standard;

use Gelf\MessageInterface;
use Gelf\Util\MessageSerialization;

class V0100Standard implements StandardInterface
{
    private const VERSION_STRING = '1.0';

    private const TIMESTAMP_FORMAT = 'U.u';

    /** @inheritdoc */
    public function serialize(MessageInterface $message): array
    {
        $this->validate($message);

        $data = [
            'version'       => self::VERSION_STRING,
            'host'          => $message->getHost(),
            'short_message' => $message->getShortMessage(),
            'full_message'  => $message->getFullMessage(),
            'level'         => $message->getLevel(),
            'timestamp'     => $message->getTimestamp()->format(self::TIMESTAMP_FORMAT),
            'facility'      => $message->getContext('facility'),
            'file'          => $message->getContext('file'),
            'line'          => $message->getContext('line')
        ];

        // add additionals
        foreach ($message->getFullContext() as $key => $value) {
            $data['_' . $key] = $value;
        }

        // return after filtering empty strings and null values
        return MessageSerialization::filterEmptyFields($data);
    }

    /** @inheritdoc */
    public function validate(MessageInterface $message): void
    {
        if ('' === $message->getHost()) {
            throw new ValidationException("'host' is an empty string");
        }

        if ('' === $message->getShortMessage()) {
            throw new ValidationException("'short-message' is an empty string");
        }

        if ($message->hasContext('id')) {
            throw new ValidationException("'id' is an illegal additional field name");
        }
    }
}
