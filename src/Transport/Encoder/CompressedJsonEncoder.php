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
 * CompressedJsonEncoder first serializes the data to json and then applies gz-compression
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 * @internal
 */
class CompressedJsonEncoder extends JsonEncoder
{
    public const DEFAULT_COMPRESSION_LEVEL = -1;

    /**
     * @var int
     */
    private $compressionLevel;

    /**
     * Class constructor
     *
     * Allows the specification of the gzip compression-level
     *
     * @param int $compressionLevel
     */
    public function __construct(int $compressionLevel = self::DEFAULT_COMPRESSION_LEVEL)
    {
        $this->compressionLevel = $compressionLevel;
    }

    /**
     * Encodes a given message
     *
     * @param  array $data
     * @return string
     */
    public function encode(array $data): string
    {
        $json = parent::encode($data);

        return \gzcompress($json, $this->compressionLevel);
    }
}
