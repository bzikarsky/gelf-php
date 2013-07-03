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

use Gelf\Logger;
use Gelf\MessageInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Log\LogLevel;
use Exception;

class LoggerTest extends TestCase
{

    protected $publisher;
    protected $logger;
    protected $facility = "test-facility";

    public function setUp()
    {
        $this->publisher = $this->getMock('\Gelf\Publisher');
        $this->logger = new Logger($this->publisher, $this->facility);
    }

    public function testPublisher()
    {
        $this->assertEquals($this->publisher, $this->logger->getPublisher());

        $newPublisher = $this->getMock('\Gelf\Publisher');
        $this->logger->setPublisher($newPublisher);
        $this->assertEquals($newPublisher, $this->logger->getPublisher());
    }

    public function testFacility()
    {
        $this->assertEquals($this->facility, $this->logger->getFacility());

        $newFacility = "foobar-facil";
        $this->logger->setFacility($newFacility);
        $this->assertEquals($newFacility, $this->logger->getFacility());
        
        $newFacility = null;
        $this->logger->setFacility($newFacility);
        $this->assertEquals($newFacility, $this->logger->getFacility());
    }

    public function testSimpleLog()
    {
        $this->validatePublish(
            function (MessageInterface $message)
            {
                $this->assertEquals("test", $message->getShortMessage());
                $this->assertEquals(LogLevel::ALERT, $message->getLevel());
                $this->assertEquals($this->facility, $message->getFacility());
            }
        );

        $this->logger->log(LogLevel::ALERT, "test");
    }

    public function testLogContext()
    {
        $additionals = ['test' => 'bar', 'abc' => 'buz'];
        $this->validatePublish(
            function (MessageInterface $message) use ($additionals)
            {
                $this->assertEquals("foo bar", $message->getShortMessage());
                $this->assertEquals($additionals, $message->getAllAdditionals());
            }
        );

        $this->logger->log(LogLevel::NOTICE, "foo {test}", $additionals);
    }

    public function testLogException()
    {
        $line = __LINE__ + 2; // careful, offset is the line-distance to the throw statement
        try {
            throw new Exception("test-message", 123);
        } catch (Exception $e) {
            $this->validatePublish(
                function (MessageInterface $message) use ($e, $line)
                {
                    $this->assertTrue(strstr($message->getFullMessage(), $e->getMessage()) !== false);
                    $this->assertTrue(strstr($message->getFullMessage(), get_class($e)) !== false);
                    $this->assertEquals($line, $message->getLine());
                    $this->assertEquals(__FILE__, $message->getFile());
                }
            );

            $this->logger->log(LogLevel::ALERT, $e->getMessage(), ['exception' => $e]);
        }
    }

    private function validatePublish(callable $validator)
    {
        $this->publisher->expects($this->once())->method('publish')->will(
            $this->returnCallback($validator)
        );
    }
}
