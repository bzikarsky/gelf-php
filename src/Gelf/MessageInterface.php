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

use DateTimeImmutable;
use DateTimeInterface;

/**
 * This interface defines the minimum amount of method any Message
 * implementation must provide to be used by the publisher or transports.
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
interface MessageInterface
{
    /**
     * Returns the GELF version of the message
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Returns the host of the message
     *
     * @return string
     */
    public function getHost(): string;

    /**
     * Returns the short text of the message
     *
     * @return string
     */
    public function getShortMessage(): string;

    /**
     * Returns the full text of the message
     *
     * @return null|string
     */
    public function getFullMessage(): ?string;

    /**
     * Returns the timestamp of the message
     *
     * @return DateTimeImmutable
     */
    public function getTimestamp(): DateTimeInterface;

    /**
     * Returns the log level of the message as a Psr\Log\Level-constant
     *
     * @return string
     */
    public function getLevel(): string;

    /**
     * Returns the log level of the message as a numeric syslog level
     *
     * @return int
     */
    public function getSyslogLevel(): int;

    /**
     * Returns the facility of the message
     *
     * @return null|string
     */
    public function getFacility(): ?string;

    /**
     * Returns the file of the message
     *
     * @return null|string
     */
    public function getFile(): ?string;

    /**
     * Returns the the line of the message
     *
     * @return null|int
     */
    public function getLine(): ?int;

    /**
     * Returns the value of the additional field of the message
     *
     * @param  string $key
     * @return mixed
     */
    public function getAdditional(string $key);

    /**
     * Checks if a additional fields is set
     *
     * @param  string $key
     * @return bool
     */
    public function hasAdditional(string $key): bool;

    /**
     * Returns all additional fields as an array
     *
     * @return array
     */
    public function getAllAdditionals(): array;

    /**
     * Converts the message to an array
     *
     * @return array
     */
    public function toArray(): array;
}
