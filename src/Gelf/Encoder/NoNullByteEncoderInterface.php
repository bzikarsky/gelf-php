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

/**
 * A no-null-byte-encoder encodes a message without generating any 0x00 bytes.
 * This is usually true for string representations like JSON, but not for
 * for compression (zlib, gzip, etc.) output.
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
interface NoNullByteEncoderInterface extends EncoderInterface
{
}
