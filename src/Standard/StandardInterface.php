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

/**
 * A StandardInterface is a representation of a specific version of the GELF standard
 *
 * It can be used to either serialize a Message to the standard-conforming data-
 * representation (array) or to validate the message for standard-conformity.
 *
 * @package Gelf\Standard
 */
interface StandardInterface
{
    /**
     * Serialize a Gelf\MessageInterface to a data-representation (array)
     *
     * @param MessageInterface $message
     * @throws ValidationException
     * @return array
     */
    public function serialize(MessageInterface $message): array;

    /**
     * Validate a Gelf\MessageInterface for standard-conformity
     *
     * @param MessageInterface $message
     * @throws ValidationException
     */
    public function validate(MessageInterface $message): void;
}
