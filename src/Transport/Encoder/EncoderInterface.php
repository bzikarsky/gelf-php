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

namespace Gelf\Transport\Encoder;

/**
 * An Encoder's responsibility is the transformation of gelf-data (array) to a byte-stream
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 * @internal
 */
interface EncoderInterface
{
    /**
     * Encodes a given message
     *
     * @param array $data
     * @return string
     */
    public function encode(array $data): string;
}
