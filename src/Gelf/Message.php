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
    private ?string $version;
    private ?string $host;
    private ?float $timestamp;
    private ?int $level;

    private ?string $shortMessage = null;
    private ?string $fullMessage = null;
    private ?string $facility = null;
    private ?string $file = null;
    private ?int $line = null;
    private array $additionals = [];

    /**
     * A list of the PSR LogLevel constants which is also a mapping of
     * syslog code to psr-value
     *
     * @var array
     */
    private static array $psrLevels = [
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
     * Creates a new message
     *
     * Populates timestamp and host with sane default values
     */
    public function __construct()
    {
        $this->timestamp = microtime(true);
        $this->host = gethostname();
        $this->level = 1; //ALERT
        $this->version = "1.0";
    }

    /**
     * Trys to convert a given log-level (psr or syslog) to
     * the psr representation
     */
    final public static function logLevelToPsr(int|string $level): string
    {
        $origLevel = $level;

        if (is_numeric($level)) {
            $level = intval($level);
            if (isset(self::$psrLevels[$level])) {
                return self::$psrLevels[$level];
            }
        } elseif (is_string($level)) {
            $level = strtolower($level);
            if (in_array($level, self::$psrLevels)) {
                return $level;
            }
        }

        throw new RuntimeException(
            sprintf("Cannot convert log-level '%s' to psr-style", $origLevel)
        );
    }

    /**
     * Trys to convert a given log-level (psr or syslog) to
     * the syslog representation
     */
    final public static function logLevelToSyslog(int|string $level): int
    {
        $origLevel = $level;

        if (is_numeric($level)) {
            $level = intval($level);
            if ($level < 8 && $level > -1) {
                return $level;
            }
        } elseif (is_string($level)) {
            $level = strtolower($level);
            $syslogLevel = array_search($level, self::$psrLevels);
            if (false !== $syslogLevel) {
                return $syslogLevel;
            }
        }

        throw new RuntimeException(
            sprintf("Cannot convert log-level '%s' to syslog-style", $origLevel)
        );
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(?string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getShortMessage(): ?string
    {
        return $this->shortMessage;
    }

    public function setShortMessage(?string $shortMessage): self
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

    public function getTimestamp(): ?float
    {
        return (float) $this->timestamp;
    }

    public function setTimestamp(float|int|DateTimeInterface $timestamp): self
    {
        if ($timestamp instanceof DateTimeInterface) {
            $timestamp = $timestamp->format("U.u");
        }

        $this->timestamp = (float)$timestamp;

        return $this;
    }

    public function getLevel(): ?string
    {
        return self::logLevelToPsr($this->level);
    }

    public function getSyslogLevel(): ?int
    {
        return self::logLevelToSyslog($this->level);
    }

    public function setLevel(string|int $level): self
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

    public function getAdditional(string $key): mixed
    {
        if (!isset($this->additionals[$key])) {
            throw new RuntimeException(
                sprintf("Additional key '%s' is not defined", $key)
            );
        }

        return $this->additionals[$key];
    }

    public function hasAdditional(string $key): bool
    {
        return isset($this->additionals[$key]);
    }

    public function setAdditional(string $key, mixed $value): self
    {
        if ($key === '') {
            throw new RuntimeException("Additional field key cannot be empty");
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
            'timestamp'     => $this->getTimestamp(),
            'facility'      => $this->getFacility(),
            'file'          => $this->getFile(),
            'line'          => $this->getLine()
        ];

        // Transform 1.1 deprecated fields to additionals
        // Will be refactored for 2.0, see #23
        if ($this->getVersion() == "1.1") {
            foreach (['line', 'facility', 'file'] as $idx) {
                $message["_" . $idx] = $message[$idx];
                unset($message[$idx]);
            }
        }

        // add additionals
        foreach ($this->getAllAdditionals() as $key => $value) {
            $message["_" . $key] = $value;
        }

        // return after filtering empty strings and null values
        return array_filter($message, function ($message) {
            return is_bool($message)
                || (is_string($message) && strlen($message))
                || is_int($message)
                || !empty($message);
        });
    }
}
