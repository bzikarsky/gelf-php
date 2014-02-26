<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf\Test;

use Gelf\Publisher;
use PHPUnit_Framework_TestCase as TestCase;

class PublisherTest extends TestCase
{

    protected $transportA;
    protected $transportB;
    protected $messageValidator;
    protected $message;
    protected $publisher;

    public function setUp()
    {
        $this->transportA = $this->getMock('Gelf\Transport\TransportInterface');
        $this->transportB = $this->getMock('Gelf\Transport\TransportInterface');
        $this->messageValidator = 
            $this->getMock('Gelf\MessageValidatorInterface');
        $this->message = $this->getMock('Gelf\MessageInterface');

        $this->publisher = new Publisher(
            $this->transportA, 
            $this->messageValidator
        );
    }

    public function testPublish()
    {
        $this->transportA->expects($this->once())
            ->method('send')
            ->with($this->equalTo($this->message));
        
        $this->messageValidator->expects($this->once())
            ->method('validate')
            ->will($this->returnValue(true));

        $this->publisher->publish($this->message);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testPublishErrorOnInvalid()
    {
        $this->messageValidator->expects($this->once())
            ->method('validate')
            ->will($this->returnValue(false));
 
        $this->publisher->publish($this->message);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testMissingTransport()
    {
        $publisher = new Publisher(null, $this->messageValidator);
        $this->assertEquals(0, count($publisher->getTransports()));

        $publisher->publish($this->message);
    }

    public function testMultipleTransports()
    {
        $pub = $this->publisher;
        $pub->addTransport($this->transportB);
        $this->transportA->expects($this->once())
            ->method('send')
            ->with($this->equalTo($this->message));

        $this->transportB->expects($this->once())
            ->method('send')
            ->with($this->equalTo($this->message));

        $this->messageValidator->expects($this->once())
            ->method('validate')
            ->will($this->returnValue(true));

        $pub->publish($this->message);
    }

    public function testGetTransports()
    {
        $pub = new Publisher(null, $this->messageValidator);
        $this->assertEquals(0, count($pub->getTransports()));

        $pub->addTransport($this->transportA);
        $this->assertEquals(1, count($pub->getTransports()));

        $pub->addTransport($this->transportB);
        $this->assertEquals(2, count($pub->getTransports()));

        $pub->addTransport($this->transportA);
        $this->assertEquals(2, count($pub->getTransports()));
    }

    public function testInitWithDefaultValidator()
    {
        $pub = new Publisher();
        $this->assertInstanceOf(
            'Gelf\MessageValidatorInterface', 
            $pub->getMessageValidator()
        );
    }
}
