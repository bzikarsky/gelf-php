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

namespace Gelf\Test;

use Gelf\Logger;
use Gelf\MessageInterface;
use Gelf\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Exception;
use Closure;
use Stringable;

class LoggerTest extends TestCase
{
    private MockObject|PublisherInterface $publisher;
    private Logger $logger;
    private string $facility = "test-facility";

    public function setUp(): void
    {
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->logger = new Logger($this->publisher, $this->facility);
    }

    public function testPublisher(): void
    {
        self::assertEquals($this->publisher, $this->logger->getPublisher());

        $newPublisher = $this->createMock(PublisherInterface::class);
        $this->logger->setPublisher($newPublisher);
        self::assertEquals($newPublisher, $this->logger->getPublisher());
    }

    public function testFacility(): void
    {
        self::assertEquals($this->facility, $this->logger->getFacility());

        $newFacility = "foobar-facil";
        $this->logger->setFacility($newFacility);
        self::assertEquals($newFacility, $this->logger->getFacility());

        $newFacility = null;
        $this->logger->setFacility($newFacility);
        self::assertEquals($newFacility, $this->logger->getFacility());
    }

    public function testSimpleLog(): void
    {
        $facility = $this->facility;
        $this->validatePublish(
            function (MessageInterface $message) use ($facility) {
                self::assertEquals("test", $message->getShortMessage());
                self::assertEquals(LogLevel::ALERT, $message->getLevel());
                self::assertEquals($facility, $message->getFacility());
            }
        );

        $this->logger->log(LogLevel::ALERT, "test");
    }

    public function testLogContext(): void
    {
        $additional = ['test' => 'bar', 'abc' => 'buz'];
        $this->validatePublish(
            function (MessageInterface $message) use ($additional) {
                self::assertEquals("foo bar", $message->getShortMessage());
                self::assertEquals($additional, $message->getAllAdditionals());
            }
        );

        $this->logger->log(LogLevel::NOTICE, "foo {test}", $additional);
    }

    /**
     * @see https://github.com/bzikarsky/gelf-php/issues/50
     * @dataProvider providerLogContextWithStructuralValues
     */
    public function testLogContextWithStructuralValues(mixed $contextValue, mixed $expected): void
    {
        $additional = ['context' => $contextValue];
        $this->validatePublish(
            function (MessageInterface $message) use ($expected) {
                // Use Message::toArray() as it filters invalid values
                $final = $message->toArray();
                if (!isset($final['_context'])) {
                    self::fail("Expected context key missing");
                }
                $actual = $final['_context'];
                // Only scalar values are allowed, with exception of boolean
                self::assertTrue(
                    is_scalar($actual) && !is_bool($actual),
                    'Unexpected context value of type: ' . gettype($actual)
                );
                self::assertSame($expected, $actual);
            }
        );

        // Log message length must exceed longest context key length + 2
        // to cause strtr() in Logger::interpolate() to throw notices for nested arrays
        $this->logger->log(LogLevel::NOTICE, 'test message', $additional);
    }

    public static function providerLogContextWithStructuralValues(): array
    {
        $stdClass = new \stdClass();
        $stdClass->prop1 = 'val1';

        $toString = new class implements Stringable {
            public function __toString(): string
            {
                return 'toString';
            }
        };

        return [
            'array'     => [['bar' => 'buz'], '{"bar":"buz"}'],
            'boolTrue'  => [true, 'true'],
            'boolFalse' => [false, 'false'],
            'integer'   => [123, 123],
            'float'     => [123.456, 123.456],
            'object'    => [$stdClass, '[object (stdClass)]'],
            'toString'  => [$toString, 'toString'],
            'resource'  => [fopen('php://memory', 'r'), '[resource]'],
            'null'      => [null, 'NULL']
        ];
    }

    public function testLogException(): void
    {
        // offset is the line-distance to the throw statement!
        $line = __LINE__ + 3;

        try {
            throw new Exception("test-message", 123);
        } catch (Exception $e) {
            $this->validatePublish(
                function (MessageInterface $message) use ($e, $line) {
                    self::assertStringContainsString(
                        $e->getMessage(),
                        $message->getFullMessage()
                    );
                    self::assertStringContainsString(
                        get_class($e),
                        $message->getFullMessage()
                    );
                    self::assertEquals($line, $message->getLine());
                    self::assertEquals(__FILE__, $message->getFile());
                }
            );

            $this->logger->log(
                LogLevel::ALERT,
                $e->getMessage(),
                context: ['exception' => $e]
            );
        }
    }

    // @see https://github.com/bzikarsky/gelf-php/issues/9
    public function testStringZeroMessage(): void
    {
        $this->validatePublish(
            function (MessageInterface $message) {
                self::assertEquals("0", $message->getShortMessage());
            }
        );

        $this->logger->info('0');
    }

    // @see https://github.com/bzikarsky/gelf-php/issues/9
    public function testNumericZeroMessage(): void
    {
        $this->validatePublish(
            function (MessageInterface $message) {
                self::assertEquals(0, $message->getShortMessage());
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
