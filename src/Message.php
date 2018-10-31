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

namespace Gelf;

use DateTimeInterface;
use Psr\Log\LogLevel;
use RuntimeException;

/**
 * A message complying to the GELF standard
 * <https://github.com/Graylog2/graylog2-docs/wiki/GELF>
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class Message implements MessageInterface
{
    private const DEFAULT_LEVEL = 1; // Alert;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $shortMessage;

    /**
     * @var DateTimeInterface
     */
    private $timestamp;

    /**
     * @var int
     */
    private $level;

    /**
     * @var string
     */
    private $version;

    /**
     * @var null|string
     */
    private $fullMessage = null;

    /**
     * @var null|string
     */
    private $facility = null;

    /**
     * @var null|string
     */
    private $file = null;

    /**
     * @var null|string
     */
    private $line = null;

    /**
     * @var array
     */
    private $additionals = [];

    /**
     * A list of the PSR LogLevel constants which is also a mapping of
     * syslog code to psr-value
     *
     * @var array
     */
    private static $psrLevels = [
        LogLevel::EMERGENCY,    // 0
        LogLevel::ALERT,        // 1
        LogLevel::CRITICAL,     // 2
        LogLevel::ERROR,        // 3
        LogLevel::WARNING,      // 4
        LogLevel::NOTICE,       // 5
        LogLevel::INFO,         // 6
        LogLevel::DEBUG         // 7
    ];

    /**
     * Create a new message
     *
     * Populate timestamp and host with sane default values
     *
     * @param string $shortMessage
     * @param string|int $level
     */
    public function __construct(string $shortMessage, $level = self::DEFAULT_LEVEL)
    {
        $this->timestamp = \date_create_immutable();
        $this->host = \gethostname();
        $this->version = '1.0';

        $this->shortMessage = $shortMessage;
        $this->setLevel($level);
    }

    /**
     * Try to convert a given log-level (psr or syslog) to
     * the psr representation
     *
     * @param  string|int $level
     * @return string
     */
    final public static function logLevelToPsr($level): string
    {
        $origLevel = $level;

        if (\is_numeric($level)) {
            $level = (int) $level;
            if (isset(self::$psrLevels[$level])) {
                return self::$psrLevels[$level];
            }
        } elseif (\is_string($level)) {
            $level = \strtolower($level);
            if (\in_array($level, self::$psrLevels, true)) {
                return $level;
            }
        }

        throw new RuntimeException(\sprintf("Cannot convert log-level '%s' to psr-style", $origLevel));
    }

    /**
     * Try to convert a given log-level (psr or syslog) to
     * the syslog representation
     *
     * @param int|string
     * @param mixed $level
     * @return integer
     */
    final public static function logLevelToSyslog($level): int
    {
        $origLevel = $level;

        if (\is_numeric($level)) {
            $level = (int) $level;
            if (isset(self::$psrLevels[$level])) {
                return $level;
            }
        } elseif (\is_string($level)) {
            $level = \strtolower($level);
            $syslogLevel = \array_search($level, self::$psrLevels, true);
            if (false !== $syslogLevel) {
                return $syslogLevel;
            }
        }

        throw new RuntimeException(\sprintf("Cannot convert log-level '%s' to syslog-style", $origLevel));
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getShortMessage(): string
    {
        return $this->shortMessage;
    }

    public function setShortMessage(string $shortMessage): self
    {
        $this->shortMessage = $shortMessage;

        return $this;
    }

    public function getFullMessage(): ?string
    {
        return $this->fullMessage;
    }

    public function setFullMessage(?string $fullMessage): self
    {
        $this->fullMessage = $fullMessage;

        return $this;
    }

    public function getTimestamp(): DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getLevel(): string
    {
        return self::logLevelToPsr($this->level);
    }

    public function getSyslogLevel(): int
    {
        return self::logLevelToSyslog($this->level);
    }

    public function setLevel($level): self
    {
        $this->level = self::logLevelToSyslog($level);

        return $this;
    }

    public function getFacility(): ?string
    {
        return $this->facility;
    }

    public function setFacility(?string $facility): self
    {
        $this->facility = $facility;

        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(?string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }

    public function setLine(?int $line): self
    {
        $this->line = $line;

        return $this;
    }

    public function getAdditional(string $key)
    {
        if (!isset($this->additionals[$key])) {
            throw new RuntimeException(
                \sprintf("Additional key '%s' is not defined", $key)
            );
        }

        return $this->additionals[$key];
    }

    public function hasAdditional(string $key): bool
    {
        return isset($this->additionals[$key]);
    }

    public function setAdditional(string $key, $value): self
    {
        if (!$key) {
            throw new RuntimeException('Additional field key cannot be empty');
        }

        $this->additionals[$key] = $value;

        return $this;
    }

    public function getAllAdditionals(): array
    {
        return $this->additionals;
    }

    public function toArray(): array
    {
        $message = [
            'version'       => $this->getVersion(),
            'host'          => $this->getHost(),
            'short_message' => $this->getShortMessage(),
            'full_message'  => $this->getFullMessage(),
            'level'         => $this->getSyslogLevel(),
            'timestamp'     => $this->getTimestamp()->format('U.u'),
            'facility'      => $this->getFacility(),
            'file'          => $this->getFile(),
            'line'          => $this->getLine()
        ];

        // Transform 1.1 deprecated fields to additionals
        // Will be refactored for 2.0, see #23
        if ('1.1' === $this->getVersion()) {
            foreach (['line', 'facility', 'file'] as $idx) {
                $message['_' . $idx] = $message[$idx];
                unset($message[$idx]);
            }
        }

        // add additionals
        foreach ($this->getAllAdditionals() as $key => $value) {
            $message['_' . $key] = $value;
        }

        // return after filtering empty strings and null values
        return \array_filter($message, function ($message) {
            return \is_bool($message)
                || null !== $message
                || !empty($message);
        });
    }
}
