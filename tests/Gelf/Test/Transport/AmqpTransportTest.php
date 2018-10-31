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

namespace Gelf\Test\Transport;

use Gelf\Encoder\EncoderInterface;
use Gelf\MessageInterface;
use Gelf\Transport\AmqpTransport;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AmqpTransportTest extends TestCase
{
    /**
     * @var MockObject|MessageInterface
     */
    protected $message;

    /**
     * @var MockObject|EncoderInterface
     */
    protected $encoder;

    /**
     * @var string
     */
    protected $testMessage;

    /**
     * @var MockObject|AmqpTransport
     */
    protected $transport;

    /**
     * @var MockObject|\AMQPExchange
     */
    protected $exchange;

    /**
     * @var MockObject|\AMQPQueue
     */
    protected $queue;

    protected function setUp(): void
    {
        if (!\defined('AMQP_NOPARAM')) {
            \define('AMQP_NOPARAM', 0);
        }

        if (!\defined('AMQP_DURABLE')) {
            \define('AMQP_DURABLE', 2);
        }

        $this->testMessage = \str_repeat('0123456789', 30); // 300 char string

        $this->exchange = $this->getMockBuilder(\AMQPExchange::class)
            ->setMethods(['publish'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->queue = $this->getMockBuilder(\AMQPQueue::class)
            ->setMethods(['getName', 'getFlags'])
            ->disableOriginalConstructor()
            ->getMock();


        $this->message = $this->getMockBuilder(MessageInterface::class)->getMock();

        // create an encoder always return $testMessage
        $this->encoder = $this->getMockBuilder(EncoderInterface::class)->getMock();
        $this->encoder->expects($this->any())->method('encode')->will(
            $this->returnValue($this->testMessage)
        );

        $this->transport = $this->getTransport();
    }

    protected function getTransport()
    {
        $transport = new AmqpTransport($this->exchange, $this->queue);
        $transport->setMessageEncoder($this->encoder);

        $reflectedTransport = new \ReflectionObject($transport);

        $reflectedExchange = $reflectedTransport->getProperty('exchange');
        $reflectedExchange->setAccessible(true);
        $reflectedExchange->setValue($transport, $this->exchange);

        $reflectedQueue = $reflectedTransport->getProperty('queue');
        $reflectedQueue->setAccessible(true);
        $reflectedQueue->setValue($transport, $this->queue);

        return $transport;
    }

    public function testSetEncoder(): void
    {
        /** @var EncoderInterface|MockObject $encoder */
        $encoder = $this->getMockBuilder(EncoderInterface::class)->getMock();
        $this->transport->setMessageEncoder($encoder);

        $this->assertEquals($encoder, $this->transport->getMessageEncoder());
    }

    public function testGetEncoder(): void
    {
        $transport = new AmqpTransport($this->exchange, $this->queue);
        $this->assertInstanceOf(EncoderInterface::class, $transport->getMessageEncoder());
    }

    public function testSend(): void
    {
        $transport = $this->getTransport();
        $this->exchange->expects($this->once())
            ->method('publish');

        $transport->send($this->message);
    }
}
