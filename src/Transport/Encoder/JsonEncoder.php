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
 * JsonEncoder encodes the data as a simple JSON structure
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 * @internal
 */
class JsonEncoder implements EncoderInterface
{
    /** @inheritdoc */
    public function encode(array $data): string
    {
        return \json_encode($data);
    }
}
