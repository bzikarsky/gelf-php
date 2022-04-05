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

use Gelf\Transport\TransportInterface;
use Gelf\MessageValidator as DefaultMessageValidator;
use RuntimeException;

/**
 * A GELF publisher functions as a hub for pushing out a GELF message
 * to a least one GELF endpoint
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class Publisher implements PublisherInterface
{
    private array $transports = [];
    private MessageValidatorInterface $messageValidator;

    /**
     * Creates a Publisher for GELF-messages.
     */
    public function __construct(
        ?TransportInterface $transport = null,
        ?MessageValidatorInterface $messageValidator = null
    ) {
        $this->messageValidator = $messageValidator
            ?: new DefaultMessageValidator();

        if (null !== $transport) {
            $this->addTransport($transport);
        }
    }

    /**
     * @inheritDoc
     */
    public function publish(MessageInterface $message): void
    {
        if (count($this->transports) == 0) {
            throw new RuntimeException(
                "Publisher requires at least one transport"
            );
        }

        $reason = '';
        if (!$this->messageValidator->validate($message, $reason)) {
            throw new RuntimeException("Message is invalid: $reason");
        }

        foreach ($this->transports as $transport) {
            /* @var $transport TransportInterface */
            $transport->send($message);
        }
    }

    /**
     * Adds a transport object to the publisher.
     */
    public function addTransport(TransportInterface $transport): void
    {
        $this->transports[spl_object_hash($transport)] = $transport;
    }

    /**
     * Returns all defined transports.
     *
     * @return TransportInterface[]
     */
    public function getTransports(): array
    {
        return array_values($this->transports);
    }

    /**
     * Returns the current message validator.
     */
    public function getMessageValidator(): MessageValidatorInterface
    {
        return $this->messageValidator;
    }
}
