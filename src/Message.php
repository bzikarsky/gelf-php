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
     * @var null|string
     */
    private $fullMessage = null;

    /**
     * @var array
     */
    private $context = [];

    /**
     * Create a new message
     *
     * Populate timestamp and host with sane default values
     *
     * @param string $shortMessage
     * @param int $level
     */
    public function __construct(string $shortMessage, int $level = self::DEFAULT_LEVEL)
    {
        $this->timestamp = \date_create_immutable();
        $this->host = \gethostname();

        $this->shortMessage = $shortMessage;
        $this->level = $level;
    }

    /**
     * Create a message with all the default context and the given message's data
     *
     * @param MessageInterface $message
     * @param array $defaultContext
     * @return self
     */
    public static function buildWithDefaultContext(MessageInterface $message, array $defaultContext): MessageInterface
    {
        $message = new self($message->getShortMessage(), $message->getLevel());

        // Copy properties
        $message->timestamp = $message->getTimestamp();
        $message->host = $message->getHost();
        $message->fullMessage = $message->getFullMessage();
        $message->context = $message->getFullContext();

        // Find missing context
        $missingContext = \array_diff(
            \array_keys($defaultContext),
            \array_keys($message->getFullContext())
        );

        // Add missing context from default-context
        foreach ($missingContext as $key) {
            $message->context[$key] = $defaultContext[$key];
        }

        return $message;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function withHost(string $host): self
    {
        $newInstance = clone $this;
        $newInstance->host = $host;

        return $newInstance;
    }

    public function getShortMessage(): string
    {
        return $this->shortMessage;
    }

    public function withShortMessage(string $shortMessage): self
    {
        $newInstance = clone $this;
        $newInstance->shortMessage = $shortMessage;

        return $newInstance;
    }

    public function getFullMessage(): ?string
    {
        return $this->fullMessage;
    }

    public function withFullMessage(?string $fullMessage): self
    {
        $newInstance = clone $this;
        $newInstance->fullMessage = $fullMessage;

        return $newInstance;
    }

    public function getTimestamp(): DateTimeInterface
    {
        return $this->timestamp;
    }

    public function withTimestamp(DateTimeInterface $timestamp): self
    {
        $newInstance = clone $this;
        $newInstance->timestamp = $timestamp;

        return $newInstance;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function withLevel(int $level): self
    {
        $newInstance = clone $this;
        $newInstance->level = $level;

        return $newInstance;
    }

    public function getContext(string $key)
    {
        if (!isset($this->context[$key])) {
            return null;
        }

        return $this->context[$key];
    }

    public function hasContext(string $key): bool
    {
        return isset($this->context[$key]);
    }

    public function withContext(string $key, $value): self
    {
        $newInstance = clone $this;
        $newInstance->context[$key] = $value;

        return $newInstance;
    }

    public function withFullContext(array $context): self
    {
        $newInstance = clone $this;
        foreach ($context as $key => $value) {
            $newInstance->context[$key] = $value;
        }

        return $newInstance;
    }

    public function getFullContext(): array
    {
        return $this->context;
    }
}
