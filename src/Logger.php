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

use Exception;
use Gelf\Transport\UdpTransport;
use Gelf\Util\MessageInterpolation;
use Psr\Log\AbstractLogger;
use Throwable;

/**
 * A basic PSR-3 compliant logger
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class Logger extends AbstractLogger
{
    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * Create a PSR-3 Logger for GELF/Graylog2
     *
     * @param PublisherInterface|null $publisher
     */
    public function __construct(?PublisherInterface $publisher = null)
    {
        // if no publisher is provided build a "default" publisher
        // which is logging via Gelf over UDP to localhost on the default port
        $this->publisher = $publisher ?: new Publisher(new UdpTransport());
    }

    /**
     * Publish a given message and context with given level
     *
     * @param mixed $level
     * @param mixed $rawMessage
     * @param array $context
     */
    public function log($level, $rawMessage, array $context = []): void
    {
        $message = $this->initMessage($level, $rawMessage, $context);

        $this->publisher->publish($message);
    }

    /**
     * Return the currently used publisher
     *
     * @return PublisherInterface
     */
    public function getPublisher(): PublisherInterface
    {
        return $this->publisher;
    }

    /**
     * Set a new publisher
     *
     * @param PublisherInterface $publisher
     * @return self
     */
    public function setPublisher(PublisherInterface $publisher): self
    {
        $this->publisher = $publisher;

        return $this;
    }

    /**
     * Initialize a message-object
     *
     * @param  mixed   $level
     * @param  mixed   $message
     * @param  array   $context
     * @return Message
     */
    protected function initMessage($level, $message, array $context): Message
    {
        // Assert that message is a string, and interpolate placeholders
        $message = (string) $message;
        $message = MessageInterpolation::interpolate($message, $context);

        // Create message object
        $messageObj = new Message($message, $level);

        foreach ($context as $key => $value) {
            if ('exception' === $key && $value instanceof \Throwable) {
                $messageObj = $this->initExceptionData($messageObj, $context['exception']);
                continue;
            }

            $messageObj =  $messageObj->withContext($key, $value);
        }

        return $messageObj;
    }

    /**
     * Initialize Exception-data with given message
     *
     * @param Message $message
     * @param Throwable $exception
     * @return Message
     */
    private function initExceptionData(Message $message, Throwable $exception): Message
    {
        $message = $message->withFullContext([
            'line' => $exception->getLine(),
            'file' => $exception->getFile()
        ]);

        $longText = '';

        do {
            $longText .= \sprintf(
                "%s: %s (%d)\n\n%s\n",
                \get_class($exception),
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getTraceAsString()
            );

            $exception = $exception->getPrevious();
        } while ($exception && $longText .= "\n--\n\n");

        return $message->withFullMessage($longText);
    }
}
