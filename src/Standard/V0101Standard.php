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
use Gelf\Util\MessageInterpolation;
use Gelf\Util\MessageSerialization;

class V0101Standard implements StandardInterface
{
    private const VERSION_STRING = '1.1';

    private const TIMESTAMP_FORMAT = 'U.u';

    private $v0100Standard;

    private $strict;

    public function __construct(bool $strict = false)
    {
        $this->v0100Standard = new V0100Standard();
        $this->strict = $strict;
    }

    /** @inheritdoc */
    public function serialize(MessageInterface $message): array
    {
        $data = [
            'version'       => self::VERSION_STRING,
            'host'          => $message->getHost(),
            'short_message' => $message->getShortMessage(),
            'full_message'  => $message->getFullMessage(),
            'level'         => $message->getLevel(),
            'timestamp'     => $message->getTimestamp()->format(self::TIMESTAMP_FORMAT)
        ];

        // add additionals
        foreach ($message->getFullContext() as $key => $value) {
            $data['_' . $key] = \is_numeric($value) ? $value : MessageInterpolation::stringify($value);
        }

        return MessageSerialization::filterEmptyFields($data);
    }

    /** @inheritdoc */
    public function validate(MessageInterface $message): void
    {
        $this->v0100Standard->validate($message);

        foreach ($message->getFullContext() as $key => $value) {
            if (!\preg_match('#^[\w\.\-]*$#', $key)) {
                throw new ValidationException(
                    \sprintf("Context key '%s' contains invalid characters", $key)
                );
            }

            if ($this->strict && !\is_string($value) && !\is_numeric($value)) {
                throw new ValidationException(\sprintf(
                    "Context key '%s' contains is of type %s which is neither numeric nor a string",
                    $key,
                    \gettype($value)
                ));
            }
        }
    }
}
