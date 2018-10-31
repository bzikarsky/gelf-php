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

use Gelf\Transport\UdpTransport;
use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;
use Exception;

/**
 * A basic PSR-3 compliant logger
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class Logger extends AbstractLogger
{
    /**
     * @var string|null
     */
    protected $facility;

    /**
     * @var array
     */
    protected $defaultContext;

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * Create a PSR-3 Logger for GELF/Graylog2
     *
     * @param PublisherInterface|null $publisher
     * @param string|null $facility
     * @param array $defaultContext
     */
    public function __construct(
        PublisherInterface $publisher = null,
        ?string $facility = null,
        array $defaultContext = []
    ) {
        // if no publisher is provided build a "default" publisher
        // which is logging via Gelf over UDP to localhost on the default port
        $this->publisher = $publisher ?: new Publisher(new UdpTransport());

        $this->setFacility($facility);
        $this->setDefaultContext($defaultContext);
    }

    /**
     * Publish a given message and context with given level
     *
     * @param mixed $level
     * @param mixed $rawMessage
     * @param array $context
     */
    public function log($level, $rawMessage, array $context = array()): void
    {
        $message = $this->initMessage($level, $rawMessage, $context);

        // add exception data if present
        if (isset($context['exception'])
           && $context['exception'] instanceof Exception
        ) {
            self::initExceptionData($message, $context['exception']);
        }

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
     * Return the facility-name used in GELF
     *
     * @return string|null
     */
    public function getFacility(): ?string
    {
        return $this->facility;
    }

    /**
     * Sets the facility for GELF messages
     *
     * @param string|null $facility
     * @return self
     */
    public function setFacility(?string $facility): self
    {
        $this->facility = $facility;

        return $this;
    }

    /**
     * Get the default context
     *
     * @return array
     */
    public function getDefaultContext(): array
    {
        return $this->defaultContext;
    }

    /**
     * Set the default context
     *
     * @param array $defaultContext
     * @return self
     */
    public function setDefaultContext(array $defaultContext): self
    {
        $this->defaultContext = $defaultContext;

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
        // assert that message is a string, and interpolate placeholders
        $message = (string) $message;
        $context = self::initContext($context);
        $message = self::interpolate($message, $context);

        // create message object
        $messageObj = new Message($message, $level);
        $messageObj->setFacility($this->facility);

        foreach ($this->getDefaultContext() as $key => $value) {
            $messageObj->setAdditional($key, $value);
        }

        foreach ($context as $key => $value) {
            $messageObj->setAdditional($key, $value);
        }

        return $messageObj;
    }

    /**
     * Initialize context array, ensuring all values are string-safe
     *
     * @param array $context
     * @return array
     */
    private static function initContext(array $context): array
    {
        foreach ($context as $key => &$value) {
            switch (gettype($value)) {
                case 'string':
                case 'integer':
                case 'double':
                    // These types require no conversion
                    break;
                case 'array':
                case 'boolean':
                    $value = json_encode($value);
                    break;
                case 'object':
                    if (method_exists($value, '__toString')) {
                        $value = (string)$value;
                    } else {
                        $value = '[object (' . \get_class($value) . ')]';
                    }
                    break;
                case 'NULL':
                    $value = 'NULL';
                    break;
                default:
                    $value = '[' . gettype($value) . ']';
                    break;
            }
        }

        return $context;
    }

    /**
     * Initialize Exception-data with given message
     *
     * @param Message   $message
     * @param Exception $exception
     */
    private static function initExceptionData(Message $message, Exception $exception): void
    {
        $message->setLine($exception->getLine());
        $message->setFile($exception->getFile());

        $longText = '';

        do {
            $longText .= sprintf(
                "%s: %s (%d)\n\n%s\n",
                \get_class($exception),
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getTraceAsString()
            );

            $exception = $exception->getPrevious();
        } while ($exception && $longText .= "\n--\n\n");

        $message->setFullMessage($longText);
    }

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
    private static function interpolate(string $message, array $context): string
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}
