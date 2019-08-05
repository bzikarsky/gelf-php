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

class MessageInterpolation
{
    /**
     * Interpolates context values into the message placeholders.
     *
     * Reference implementation
     * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#12-message
     *
     * @param mixed $message
     * @param array $context
     * @return string
     */
    public static function interpolate(string $message, array $context): string
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = self::stringify($val);
        }

        // interpolate replacement values into the message and return
        return \strtr($message, $replace);
    }

    /**
     * Stringify arbitrary values for interpolation
     *
     * @param mixed $value
     * @return string
     */
    public static function stringify($value): string
    {
        switch (\gettype($value)) {
            case 'string':
            case 'integer':
            case 'double':
                return $value;

            case 'array':
            case 'boolean':
                return \json_encode($value);

            case 'object':
                return \method_exists($value, '__toString')
                    ? (string) $value
                    :  '[object (' . \get_class($value) . ')]';

            case 'NULL':
                return 'NULL';

            default:
                return '[' . \gettype($value) . ']';
        }
    }
}
