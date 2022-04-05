<?php
declare(strict_types=1);

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
 * An Encoder can be used to transform a MessageInterface to
 * a different representation, e.g. some binary string for network-
 * transportation
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
interface EncoderInterface
{

    /**
     * Encodes a given message
     */
    public function encode(MessageInterface $message): string;
}
