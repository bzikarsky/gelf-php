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

namespace Gelf\Test;

use Closure;
use Exception;
use Gelf\Logger;
use Gelf\MessageInterface;
use Gelf\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class LoggerTest extends TestCase
{
    /**
     * @var MockObject|PublisherInterface
     */
    protected $publisher;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $facility = 'test-facility';

    protected function setUp(): void
    {
        $this->publisher = $this->getMockBuilder(PublisherInterface::class)->getMock();
        $this->logger = new Logger($this->publisher, $this->facility);
    }

    public function testPublisher(): void
    {
        $this->assertEquals($this->publisher, $this->logger->getPublisher());

        /** @var MockObject|PublisherInterface $newPublisher */
        $newPublisher = $this->getMockBuilder(PublisherInterface::class)->getMock();
        $this->logger->setPublisher($newPublisher);
        $this->assertEquals($newPublisher, $this->logger->getPublisher());
    }

    public function testFacility(): void
    {
        $this->assertEquals($this->facility, $this->logger->getFacility());

        $newFacility = 'foobar-facil';
        $this->logger->setFacility($newFacility);
        $this->assertEquals($newFacility, $this->logger->getFacility());

        $newFacility = null;
        $this->logger->setFacility($newFacility);
        $this->assertEquals($newFacility, $this->logger->getFacility());
    }

    public function testSimpleLog(): void
    {
        $test = $this;
        $facility = $this->facility;
        $this->validatePublish(
            function (MessageInterface $message) use ($test, $facility): void {
                $test->assertEquals('test', $message->getShortMessage());
                $test->assertEquals(LogLevel::ALERT, $message->getLevel());
                $test->assertEquals($facility, $message->getFacility());
            }
        );

        $this->logger->log(LogLevel::ALERT, 'test');
    }

    public function testLogContext(): void
    {
        $test = $this;
        $additional = ['test' => 'bar', 'abc' => 'buz'];
        $this->validatePublish(
            function (MessageInterface $message) use ($test, $additional): void {
                $test->assertEquals('foo bar', $message->getShortMessage());
                $test->assertEquals($additional, $message->getFullContext());
            }
        );

        $this->logger->log(LogLevel::NOTICE, 'foo {test}', $additional);
    }

    /**
     * @see https://github.com/bzikarsky/gelf-php/issues/50
     * @dataProvider providerLogContextWithStructuralValues
     * @param mixed $contextValue
     * @param mixed $expected
     */
    public function testLogContextWithStructuralValues($contextValue, $expected): void
    {
        $test = $this;
        $additional = ['context' => $contextValue];
        $this->validatePublish(
            function (MessageInterface $message) use ($test, $expected): void {
                // Use Message::toArray() as it filters invalid values
                $final = $message->toArray();
                if (!isset($final['_context'])) {
                    $test->fail('Expected context key missing');
                }
                $actual = $final['_context'];
                // Only scalar values are allowed, with exception of boolean
                $test->assertTrue(
                    \is_scalar($actual) && !\is_bool($actual),
                    'Unexpected context value of type: ' . \gettype($actual)
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

        $toString = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['__toString'])
            ->getMock();

        $toString->method('__toString')->willReturn('toString');

        return [
            'array'     => [['bar' => 'buz'], '{"bar":"buz"}'],
            'boolTrue'  => [true, 'true'],
            'boolFalse' => [false, 'false'],
            'integer'   => [123, 123],
            'float'     => [123.456, 123.456],
            'object'    => [$stdClass, '[object (stdClass)]'],
            'toString'  => [$toString, 'toString'],
            'resource'  => [\fopen('php://memory', 'r'), '[resource]'],
            'null'      => [null, 'NULL']
        ];
    }

    public function testLogException(): void
    {
        $test = $this;

        // offset is the line-distance to the throw statement!
        $line = __LINE__ + 3;

        try {
            throw new Exception('test-message', 123);
        } catch (Exception $e) {
            $this->validatePublish(
                function (MessageInterface $message) use ($e, $line, $test): void {
                    $test->assertContains(
                        $e->getMessage(),
                        $message->getFullMessage()
                    );
                    $test->assertContains(
                        \get_class($e),
                        $message->getFullMessage()
                    );
                    $test->assertEquals($line, $message->getLine());
                    $test->assertEquals(__FILE__, $message->getFile());
                }
            );

            $this->logger->log(
                LogLevel::ALERT,
                $e->getMessage(),
                ['exception' => $e]
            );
        }
    }

    /** @see https://github.com/bzikarsky/gelf-php/issues/9 */
    public function testStringZeroMessage(): void
    {
        $test = $this;
        $this->validatePublish(
            function (MessageInterface $message) use ($test): void {
                $test->assertEquals('0', $message->getShortMessage());
            }
        );

        $this->logger->info('0');
    }

    /** @see https://github.com/bzikarsky/gelf-php/issues/9 */
    public function testNumericZeroMessage(): void
    {
        $test = $this;
        $this->validatePublish(
            function (MessageInterface $message) use ($test): void {
                $test->assertEquals(0, $message->getShortMessage());
            }
        );

        $this->logger->alert(0);
    }

    private function validatePublish(Closure $validator): void
    {
        $this->publisher->expects($this->once())->method('publish')->will(
            $this->returnCallback($validator)
        );
    }
}
