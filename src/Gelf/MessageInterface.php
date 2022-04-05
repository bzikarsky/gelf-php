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
     */
    public function getVersion(): string;

    /**
     * Returns the host of the message
     */
    public function getHost(): string;

    /**
     * Returns the short text of the message
     */
    public function getShortMessage(): ?string;

    /**
     * Returns the full text of the message
     */
    public function getFullMessage(): ?string;

    /**
     * Returns the timestamp of the message
     */
    public function getTimestamp(): float;

    /**
     * Returns the log level of the message as a Psr\Log\Level-constant
     */
    public function getLevel(): string;

    /**
     * Returns the log level of the message as a numeric syslog level
     */
    public function getSyslogLevel(): int;

    /**
     * Returns the value of the additional field of the message
     */
    public function getAdditional(string $key): mixed;

    /**
     * Checks if an additional fields is set
     */
    public function hasAdditional(string $key): bool;

    /**
     * Returns all additional fields as an array
     */
    public function getAllAdditionals(): array;

    /**
     * Converts the message to an array
     */
    public function toArray(): array;
}
