<?php

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

/**
 * A basic PSR-3 compliant logger
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class Logger extends AbstractLogger implements LoggerInterface
{
    /**
     * @var string
     */
    protected $facility;

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * Creates a PSR-3 Logger for GELF/Graylog2
     *
     * @param Publisher $publisher
     * @param string    $facility
     */
    public function __construct(
        PublisherInterface $publisher = null,
        $facility = null
    ) {
        // if no publisher is provided build a "default" publisher
        // which is logging via Gelf over UDP to localhost on the default port
        $publisher = $publisher ?: new Publisher(new UdpTransport());

        $this->setPublisher($publisher);
        $this->setFacility($facility);
    }

    /**
     * Publishes a given message and context with given level
     *
     * @param mixed $level
     * @param mixed $rawMessage
     * @param array $context
     */
    public function log($level, $rawMessage, array $context = array())
    {
        $message = $this->initMessage($level, $rawMessage, $context);

        // add exception data if present
        if (
           isset($context['exception'])
           && $context['exception'] instanceof Exception
        ) {
            $this->initExceptionData($message, $context['exception']);
        }

        $this->publisher->publish($message);
    }

    /**
     * Returns the currently used publisher
     *
     * @return PublisherInterface
     */
    public function getPublisher()
    {
        return $this->publisher;
    }

    /**
     * Sets a new publisher
     *
     * @param PublisherInterface $publisher
     */
    public function setPublisher(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * Returns the faciilty-name used in GELF
     *
     * @return string
     */
    public function getFacility()
    {
        return $this->facility;
    }

    /**
     * Sets the facility for GELF messages
     *
     * @param string $facility
     */
    public function setFacility($facility = null)
    {
        $this->facility = $facility;
    }

    /**
     * Initializes message-object
     *
     * @param  mixed   $level
     * @param  mixed   $rawMessage
     * @param  array   $context
     * @return Message
     */
    protected function initMessage($level, $message, $context)
    {
        // assert that message is a string, and interpolate placeholders
        $message = (string) $message;
        $message = self::interpolate($message, $context);

        // create message object
        $messageObj = new Message();
        $messageObj->setLevel($level);
        $messageObj->setShortMessage($message);
        $messageObj->setFacility($this->facility);

        foreach ($context as $key => $value) {
            $messageObj->setAdditional($key, $value);
        }

        return $messageObj;
    }

    /**
     * Initializes Exceptiondata with given message
     *
     * @param Message   $message
     * @param Exception $e
     */
    protected function initExceptionData(Message $message, Exception $e)
    {
        $message->setLine($e->getLine());
        $message->setFile($e->getFile());

        $longText = "";

        do {
            $longText .= sprintf(
                "%s: %s (%d)\n\n%s\n",
                get_class($e),
                $e->getMessage(),
                $e->getCode(),
                $e->getTraceAsString()
            );

            $e = $e->getPrevious();
        } while ($e && $longText .= "\n--\n\n");

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
     */
    private static function interpolate($message, array $context)
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
