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

namespace  Gelf\Util;

use InvalidArgumentException;
use Psr\Log\LogLevel as PsrLevel;

class LogLevel
{
    public const EMERGENCY = 0;

    public const ALERT     = 1;

    public const CRITICAL  = 2;

    public const ERROR     = 3;

    public const WARNING   = 4;

    public const NOTICE    = 5;

    public const INFO      = 6;

    public const DEBUG     = 7;

    private static $levelMapping = [
        self::EMERGENCY => PsrLevel::EMERGENCY,
        self::ALERT     => PsrLevel::ALERT,
        self::CRITICAL  => PsrLevel::CRITICAL,
        self::ERROR     => PsrLevel::ERROR,
        self::WARNING   => PsrLevel::WARNING,
        self::NOTICE    => PsrLevel::NOTICE,
        self::INFO      => PsrLevel::INFO,
        self::DEBUG     => PsrLevel::DEBUG
    ];

    private static $reverseMapping = null;

    public static function psrToSyslog(string $level): int
    {
        if (null === self::$reverseMapping) {
            self::$reverseMapping = \array_flip(self::$levelMapping);
        }

        if (!isset(self::$reverseMapping[$level])) {
            throw new InvalidArgumentException("'$level' is not a valid PSR log-level");
        }

        return self::$reverseMapping[$level];
    }

    public static function syslogToPsr(int $level): string
    {
        if (!isset(self::$levelMapping[$level])) {
            throw new InvalidArgumentException("'$level' is not a valid Syslog log-level");
        }

        return self::$levelMapping[$level];
    }
}
