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

namespace Gelf\Util;

/**
 * Class MessageSerialization
 *
 * @package Gelf\Util
 * @internal
 */
class MessageSerialization
{
    public static function filterEmptyFields(array $data): array
    {
        return \array_filter($data, function ($value) {
            // Filter NULL and empty string values
            return null !== $value && '' !== $value;
        });
    }
}
