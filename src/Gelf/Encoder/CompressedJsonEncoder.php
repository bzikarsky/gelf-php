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
 * The CompressedJsonEncoder allows the encoding of GELF messages as described
 * in http://www.graylog2.org/resources/documentation/sending/gelfhttp
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class CompressedJsonEncoder implements EncoderInterface
{
    const DEFAULT_COMPRESSION_LEVEL = -1;

    /**
     * @var int
     */
    protected $compressionLevel;

    /** @var JsonEncoder */
    private $jsonEncoder;

    /**
     * Class constructor
     *
     * Allows the specification of the gzip compression-level
     *
     * @param int $compressionLevel
     */
    public function __construct(
        $compressionLevel = self::DEFAULT_COMPRESSION_LEVEL
    ) {
        $this->compressionLevel = $compressionLevel;
        $this->jsonEncoder = new JsonEncoder();
    }

    /**
     * Encodes a given message
     *
     * @param  MessageInterface $message
     * @return string
     */
    public function encode(MessageInterface $message)
    {
        $json = $this->jsonEncoder->encode($message);

        return gzcompress($json, $this->compressionLevel);
    }
}
