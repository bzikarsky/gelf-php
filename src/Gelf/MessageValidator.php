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

/**
 * Validates a given message according to the GELF standard
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 * @author Joe Green
 */
class MessageValidator implements MessageValidatorInterface
{
    public function validate(MessageInterface $message)
    {
        switch ($message->getVersion()) {
            case "1.0":
                return $this->validate0100($message);
        }

        throw new RuntimeException(
            sprintf(
                "No validator for message version '%s'", 
                $message->getVersion()
            )
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
        if (self::isEmpty($message->getHost())) {
            return false;
        }

        if (self::isEmpty($message->getShortMessage())) {
            return false;
        }

        if (self::isEmpty($message->getVersion())) {
            return false;
        }

        return true;
    }

    /**
     * Checks that a given scalar will later translate
     * to a non-empty message element
     *
     * Fails on null, false and empty strings
     *
     * @param string $string
     * @return bool
     */
    public static function isEmpty($scalar)
    {
        return strlen($scalar) < 1;
    }
}
