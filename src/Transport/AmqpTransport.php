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

namespace Gelf\Transport;

use AMQPExchange;
use AMQPQueue;
use Gelf\Transport\Encoder\EncoderInterface;
use Gelf\Transport\Encoder\JsonEncoder;

/**
 * Class AmqpTransport
 *
 * @package Gelf\Transport
 */
class AmqpTransport implements TransportInterface
{
    /**
     * @var AMQPExchange $exchange
     */
    private $exchange;

    /**
     * @var AMQPQueue $exchange
     */
    private $queue;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @param AMQPExchange $exchange
     * @param AMQPQueue $queue
     */
    public function __construct(AMQPExchange $exchange, AMQPQueue $queue)
    {
        $this->queue = $queue;
        $this->exchange = $exchange;
        $this->encoder = new JsonEncoder();
    }

    /** @inheritdoc */
    public function send(array $data): void
    {
        $rawMessage = $this->encoder->encode($data);
        $attributes = ['Content-type' => 'application/json'];

        // if queue is durable then mark message as 'persistent'
        if (($this->queue->getFlags() & AMQP_DURABLE) > 0) {
            $attributes['delivery_mode'] = 2;
        }

        $this->exchange->publish(
            $rawMessage,
            $this->queue->getName(),
            AMQP_NOPARAM,
            $attributes
        );
    }
}
