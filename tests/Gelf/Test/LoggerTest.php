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
use Gelf\PublisherInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Log\LogLevel;
use Exception;
use Closure;

class LoggerTest extends TestCase
{

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var Logger
     */
    protected $logger;
    protected $facility = "test-facility";

    public function setUp()
    {
        $this->publisher = $this->getMock('\Gelf\PublisherInterface');
        $this->logger = new Logger($this->publisher, $this->facility);
    }

    public function testPublisher()
    {
        $this->assertEquals($this->publisher, $this->logger->getPublisher());

        $newPublisher = $this->getMock('\Gelf\PublisherInterface');
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
        $test = $this;
        $facility = $this->facility;
        $this->validatePublish(
            function (MessageInterface $message) use ($test, $facility) {
                $test->assertEquals("test", $message->getShortMessage());
                $test->assertEquals(LogLevel::ALERT, $message->getLevel());
                $test->assertEquals($facility, $message->getFacility());
            }
        );

        $this->logger->log(LogLevel::ALERT, "test");
    }

    public function testLogContext()
    {
        $test = $this;
        $additional = array('test' => 'bar', 'abc' => 'buz');
        $this->validatePublish(
            function (MessageInterface $message) use ($test, $additional) {
                $test->assertEquals("foo bar", $message->getShortMessage());
                $test->assertEquals($additional, $message->getAllAdditionals());
            }
        );

        $this->logger->log(LogLevel::NOTICE, "foo {test}", $additional);
    }

    /**
     * @see https://github.com/bzikarsky/gelf-php/issues/50
     * @dataProvider providerLogContextWithStructuralValues
     */
    public function testLogContextWithStructuralValues($contextValue, $expected)
    {
        $test = $this;
        $additional = array('context' => $contextValue);
        $this->validatePublish(
            function (MessageInterface $message) use ($test, $expected) {
                // Use Message::toArray() as it filters invalid values
                $final = $message->toArray();
                if (!isset($final['_context'])) {
                    $test->fail("Expected context key missing");
                }
                $actual = $final['_context'];
                // Only scalar values are allowed, with exception of boolean
                $test->assertTrue(
                    is_scalar($actual) && !is_bool($actual),
                    'Unexpected context value of type: ' . gettype($actual)
                );
                $test->assertSame($expected, $actual);
            }
        );

        // Log message length must exceed longest context key length + 2
        // to cause strtr() in Logger::interpolate() to throw notices for nested arrays
        $this->logger->log(LogLevel::NOTICE, 'test message', $additional);
    }

    public function providerLogContextWithStructuralValues()
    {
        $stdClass = new \stdClass();
        $stdClass->prop1 = 'val1';

        $toString = $this->getMock('dummyClass', array('__toString'));
        $toString->method('__toString')
            ->willReturn('toString');

        return array(
            'array'     => array(array('bar' => 'buz'), '{"bar":"buz"}'),
            'boolTrue'  => array(true, 'true'),
            'boolFalse' => array(false, 'false'),
            'integer'   => array(123, 123),
            'float'     => array(123.456, 123.456),
            'object'    => array($stdClass, '[object (stdClass)]'),
            'toString'  => array($toString, 'toString'),
            'resource'  => array(fopen('php://memory', 'r'), '[resource]'),
            'null'      => array(null, 'NULL')
        );
    }

    public function testLogException()
    {
        $test = $this;

        // offset is the line-distance to the throw statement!
        $line = __LINE__ + 3;

        try {
            throw new Exception("test-message", 123);
        } catch (Exception $e) {
            $this->validatePublish(
                function (MessageInterface $message) use ($e, $line, $test) {
                    $test->assertContains(
                        $e->getMessage(),
                        $message->getFullMessage()
                    );
                    $test->assertContains(
                        get_class($e),
                        $message->getFullMessage()
                    );
                    $test->assertEquals($line, $message->getLine());
                    $test->assertEquals(__FILE__, $message->getFile());
                }
            );

            $this->logger->log(
                LogLevel::ALERT,
                $e->getMessage(),
                array('exception' => $e)
            );
        }
    }

    // @see https://github.com/bzikarsky/gelf-php/issues/9
    public function testStringZeroMessage()
    {
        $test = $this;
        $this->validatePublish(
            function (MessageInterface $message) use ($test) {
                $test->assertEquals("0", $message->getShortMessage());
            }
        );

        $this->logger->info('0');
    }

    // @see https://github.com/bzikarsky/gelf-php/issues/9
    public function testNumericZeroMessage()
    {
        $test = $this;
        $this->validatePublish(
            function (MessageInterface $message) use ($test) {
                $test->assertEquals(0, $message->getShortMessage());
            }
        );

        $this->logger->alert(0);
    }

    private function validatePublish(Closure $validator)
    {
        $this->publisher->expects($this->once())->method('publish')->will(
            $this->returnCallback($validator)
        );
    }
}
