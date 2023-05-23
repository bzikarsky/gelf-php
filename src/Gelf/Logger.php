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

use Gelf\Transport\UdpTransport;
use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;
use Exception;
use Stringable;

/**
 * A basic PSR-3 compliant logger
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class Logger extends AbstractLogger implements LoggerInterface
{
    private PublisherInterface $publisher;

    /**
     * Creates a PSR-3 Logger for GELF/Graylog2
     */
    public function __construct(
        PublisherInterface $publisher = null,
        private array $defaultContext = []
    ) {
        // if no publisher is provided build a "default" publisher
        // which is logging via Gelf over UDP to localhost on the default port
        $this->publisher = $publisher ?? new Publisher(new UdpTransport());
    }

    /** @inheritDoc */
    public function log($level, $message, array $context = []): void
    {
        $messageObj = $this->initMessage($level, $message, $context);

        // add exception data if present
        if (isset($context['exception'])
           && $context['exception'] instanceof Exception
        ) {
            $this->initExceptionData($messageObj, $context['exception']);
        }

        $this->publisher->publish($messageObj);
    }

    /**
     * Returns the currently used publisher
     */
    public function getPublisher(): PublisherInterface
    {
        return $this->publisher;
    }

    /**
     * Sets a new publisher
     */
    public function setPublisher(PublisherInterface $publisher): void
    {
        $this->publisher = $publisher;
    }

    public function getDefaultContext(): array
    {
        return $this->defaultContext;
    }

    public function setDefaultContext(array $defaultContext): void
    {
        $this->defaultContext = $defaultContext;
    }

    /**
     * Initializes message-object
     */
    private function initMessage(mixed $level, string|Stringable $message, array $context): Message
    {
        // assert that message is a string, and interpolate placeholders
        $message = (string) $message;
        $context = $this->initContext($context);
        $message = self::interpolate($message, $context);

        // create message object
        $messageObj = new Message();
        $messageObj->setLevel($level);
        $messageObj->setShortMessage($message);

        foreach ($this->getDefaultContext() as $key => $value) {
            $messageObj->setAdditional($key, $value);
        }
        foreach ($context as $key => $value) {
            $messageObj->setAdditional($key, $value);
        }

        return $messageObj;
    }

    /**
     * Initializes context array, ensuring all values are string-safe
     */
    private function initContext(array $context): array
    {
        foreach ($context as &$value) {
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
                        $value = '[object (' . get_class($value) . ')]';
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
     * Initializes Exception-data with given message
     */
    private function initExceptionData(Message $message, Exception $exception): void
    {
        $message->setAdditional('line', $exception->getLine());
        $message->setAdditional('file', $exception->getFile());

        $longText = "";

        do {
            $longText .= sprintf(
                "%s: %s (%d)\n\n%s\n",
                get_class($exception),
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
     */
    private static function interpolate(string|Stringable $message, array $context): string
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr((string)$message, $replace);
    }
}
