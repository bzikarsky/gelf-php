<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf;

use Gelf\MessageInterface;
use RuntimeException;

class MessageValidator implements MessageValidatorInterface
{
    public function validate(MessageInterface $message)
    {
        switch ($message->getVersion()) {
            case "1.0":
                return $this->validate0100($message);
        }

        throw new RuntimeException(
            sprintf("No validator for message version '%s'", $message->getVersion())
        );
    }

    /**
     * Validates a message according to 1.0 standard
     *
     * @param MessageInterface  $message
     * @return bool
     */
    public function validate0100(MessageInterface $message)
    {
        if (!$message->getHost()) {
            return false;
        }

        if (!$message->getVersion()) {
            return false;
        }

        if (!$message->getShortMessage()) {
            return false;
        }

        return true;
    }
}
