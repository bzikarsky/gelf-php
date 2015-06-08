<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf\Test\Transport;

use Gelf\Transport\AmqpTransport;
use PHPUnit_Framework_TestCase as TestCase;

class AmqpTransportTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $message;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Gelf\Encoder\EncoderInterface
     */
    protected $encoder;
    /**
     * @var string
     */
    protected $testMessage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | AmqpTransport
     */
    protected $transport;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \AMQPExchange
     */
    protected $exchange;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \AMQPQueue
     */
    protected $queue;

    public function setUp()
    {
        if (!defined('AMQP_NOPARAM')) {
            define('AMQP_NOPARAM', 0);
        }

        if (!defined('AMQP_DURABLE')) {
            define('AMQP_DURABLE', 2);
        }

        $this->testMessage = str_repeat("0123456789", 30); // 300 char string

        $this->exchange = $this->getMock(
            "\\AMQPExchange",
            $methods = array('publish'),
            $args = array(),
            $mockClassName = '',
            $callConstructor = false
        );
        $this->queue = $this->getMock(
            "\\AMQPQueue",
            $methods = array('getName', 'getFlags'),
            $args = array(),
            $mockClassName = '',
            $callConstructor = false
        );

        $this->message = $this->getMock("\\Gelf\\Message");

        // create an encoder always return $testMessage
        $this->encoder = $this->getMock("\\Gelf\\Encoder\\EncoderInterface");
        $this->encoder->expects($this->any())->method('encode')->will(
            $this->returnValue($this->testMessage)
        );
        $this->transport = $this->getTransport(0);
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

    public function testSetEncoder()
    {
        $encoder = $this->getMock('\\Gelf\\Encoder\\EncoderInterface');
        $this->transport->setMessageEncoder($encoder);

        $this->assertEquals($encoder, $this->transport->getMessageEncoder());
    }

    public function testGetEncoder()
    {
        $transport = new AmqpTransport($this->exchange, $this->queue);
        $this->assertInstanceOf(
            "\\Gelf\\Encoder\\EncoderInterface",
            $transport->getMessageEncoder()
        );
    }

    public function testPublish()
    {
        $transport = $this->getMock(
            "\\Gelf\\Transport\\AmqpTransport",
            $methods = array("send"),
            $args = array(),
            $mockClassName = '',
            $callConstructor = false
        );

        $transport
            ->expects($this->once())
            ->method("send")
            ->with($this->message)
            ->will($this->returnValue(42));

        $response = $transport->publish($this->message);

        $this->assertSame(42, $response);
    }

    public function testSend()
    {
        $transport = $this->getTransport();
        $transport->send($this->message);
    }
}
