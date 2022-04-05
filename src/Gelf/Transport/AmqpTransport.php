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

namespace Gelf\Transport;

use AMQPExchange;
use AMQPQueue;
use Gelf\Encoder\JsonEncoder as DefaultEncoder;
use Gelf\MessageInterface as Message;

/**
 * Class AmqpTransport
 *
 * @package Gelf\Transport
 * @see http://php.net/manual/pl/book.amqp.php
 */
class AmqpTransport extends AbstractTransport
{
    public function __construct(
        private AMQPExchange $exchange,
        private AMQPQueue $queue
    ) {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function send(Message $message): int
    {
        $rawMessage = $this->getMessageEncoder()->encode($message);

        $attributes = [
            'Content-type' => 'application/json'
        ];

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
        return 1;
    }
}
