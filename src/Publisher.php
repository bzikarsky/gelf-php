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

use Gelf\Standard\StandardInterface;
use Gelf\Standard\V0101Standard;
use Gelf\Transport\TransportInterface;
use Gelf\Transport\UdpTransport;

/**
 * A GELF publisher functions as a hub for pushing out a GELF message
 * to a least one GELF endpoint
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class Publisher implements PublisherInterface
{
    /**
     * @var TransportInterface
     */
    private $transport;

    /**
     * @var StandardInterface
     */
    private $standard;

    /**
     * @var array
     */
    private $defaultContext = [];

    /**
     * Create a Publisher for GELF-messages.
     *
     * @param TransportInterface|null  $transport
     * @param StandardInterface|null   $standard
     */
    public function __construct(
        ?TransportInterface $transport = null,
        ?StandardInterface $standard = null
    ) {
        $this->transport = $transport ?? new UdpTransport();
        $this->standard = $standard ?? new V0101Standard();
    }

    /** @inheritdoc */
    public function publish(MessageInterface $message): void
    {
        // Apply default-context if set
        if (\count($this->defaultContext)) {
            $message = Message::buildWithDefaultContext($message, $this->defaultContext);
        }

        $data = $this->standard->serialize($message);
        $this->transport->send($data);
    }

    /** @inheritdoc */
    public function getDefaultContext(): array
    {
        return $this->defaultContext;
    }

    /** @inheritdoc */
    public function setDefaultContext(array $defaultContext): self
    {
        $this->defaultContext = $defaultContext;

        return $this;
    }

    /**
     * Return the transport
     *
     * @return TransportInterface
     */
    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }

    /**
     * Return the message standard
     *
     * @return StandardInterface
     */
    public function getStandard(): StandardInterface
    {
        return $this->standard;
    }
}
