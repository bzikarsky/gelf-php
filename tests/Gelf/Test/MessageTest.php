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

use DateTime;
use Gelf\Message;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use RuntimeException;

class MessageTest extends TestCase
{
    private Message $message;

    public function setUp(): void
    {
        $this->message = new Message();
    }

    public function testTimestamp(): void
    {
        self::assertLessThanOrEqual(microtime(true), $this->message->getTimestamp());
        self::assertGreaterThan(0, $this->message->getTimestamp());

        $this->message->setTimestamp(123);
        self::assertEquals(123, $this->message->getTimestamp());
    }

    public function testVersion(): void
    {
        self::assertEquals("1.0", $this->message->getVersion());
        self::assertEquals($this->message, $this->message->setVersion("1.1"));
        self::assertEquals("1.1", $this->message->getVersion());
    }

    public function testHost(): void
    {
        // default is current hostname
        self::assertEquals(gethostname(), $this->message->getHost());

        $this->message->setHost("example.local");
        self::assertEquals("example.local", $this->message->getHost());
    }

    public function testLevel(): void
    {
        self::assertEquals(1, $this->message->getSyslogLevel());
        self::assertEquals(LogLevel::ALERT, $this->message->getLevel());

        $this->message->setLevel(0);
        self::assertEquals(0, $this->message->getSyslogLevel());
        self::assertEquals(LogLevel::EMERGENCY, $this->message->getLevel());

        $this->message->setLevel(LogLevel::EMERGENCY);
        self::assertEquals(0, $this->message->getSyslogLevel());
        self::assertEquals(LogLevel::EMERGENCY, $this->message->getLevel());
    }

    public function testLevelInvalidString(): void
    {
        self::expectException(RuntimeException::class);
        $this->message->setLevel("invalid");
    }

    public function testLevelInvalidInteger()
    {
        self::expectException(RuntimeException::class);
        $this->message->setLevel(8);
    }

    public function testLogLevelToPsr(): void
    {
        self::assertEquals(LogLevel::ALERT, Message::logLevelToPsr("alert"));
        self::assertEquals(LogLevel::ALERT, Message::logLevelToPsr("ALERT"));
        self::assertEquals(LogLevel::ALERT, Message::logLevelToPsr(1));
    }

    public function testLogLevelToPsrInvalidString(): void
    {
        self::expectException(RuntimeException::class);
        Message::logLevelToPsr("invalid");
    }

    public function testLogLevelToPsrInvalidInt(): void
    {
        self::expectException(RuntimeException::class);
        Message::logLevelToPsr(-1);
    }

    public function testOptionalMessageFields(): void
    {
        $fields = [
            "Line" => 1337,
            "File" => '/foo/bar.php',
            "Facility" => 'test facility',
            "FullMessage" => 'full message',
            "ShortMessage" => 'short message'
        ];

        foreach ($fields as $field => $value) {
            $g = "get$field";
            $s = "set$field";
            self::assertEmpty($this->message->$g());

            $this->message->$s($value);
            self::assertEquals($value, $this->message->$g());
        }
    }

    public function testAdditionals(): void
    {
        self::assertCount(0, $this->message->getAllAdditionals());

        self::assertFalse($this->message->hasAdditional("foo"));
        $this->message->setAdditional("foo", "bar");
        self::assertEquals("bar", $this->message->getAdditional("foo"));
        self::assertTrue($this->message->hasAdditional("foo"));
        self::assertCount(1, $this->message->getAllAdditionals());

        $this->message->setAdditional("foo", "buk");
        self::assertEquals("buk", $this->message->getAdditional("foo"));
        self::assertCount(1, $this->message->getAllAdditionals());

        self::assertEquals(
            ["foo" => "buk"],
            $this->message->getAllAdditionals()
        );
    }
    
    public function testSetAdditionalEmptyKey(): void
    {
        self::expectException(RuntimeException::class);
        $this->message->setAdditional("", "test");
    }

    public function testGetAdditionalInvalidKey(): void
    {
        self::expectException(RuntimeException::class);
        $this->message->getAdditional("invalid");
    }

    public function testSetTimestamp(): void
    {
        $dt = new DateTime('@1393661544.3012');
        $this->message->setTimestamp($dt);

        self::assertEquals($dt->format("U.u"), $this->message->getTimestamp());
    }

    public function testMethodChaining(): void
    {
        $message = $this->message
            ->setTimestamp(new DateTime())
            ->setAdditional("test", "value")
            ->setFacility("test")
            ->setHost("test")
            ->setFile("test")
            ->setFullMessage("testtest")
            ->setShortMessage("test")
            ->setLevel("ERROR")
            ->setLine(1)
            ->setVersion("1.1")
        ;

        self::assertEquals($this->message, $message);
    }

    public function testToArrayV10(): void
    {
        $this->message->setAdditional("foo", "bar");
        $this->message->setAdditional("bool-true", true);
        $this->message->setAdditional("bool-false", false);
        $this->message->setAdditional("int-zero", 0);
        $data = $this->message->toArray();

        // test additionals
        self::assertArrayHasKey("_foo", $data);
        self::assertEquals("bar", $data["_foo"]);
        self::assertArrayHasKey("_bool-true", $data);
        self::assertTrue($data["_bool-true"]);
        self::assertArrayHasKey("_bool-false", $data);
        self::assertFalse($data["_bool-false"]);
        self::assertArrayHasKey("_int-zero", $data);
        self::assertEquals(0, $data["_int-zero"]);

        $map = [
            "version"       => "getVersion",
            "host"          => "getHost",
            "timestamp"     => "getTimestamp",
            "full_message"  => "getFullMessage",
            "short_message" => "getShortMessage",
            "line"          => "getLine",
            "file"          => "getFile",
            "facility"      => "getFacility",
            "level"         => "getSyslogLevel"
        ];

        foreach ($map as $k => $method) {
            $r = $this->message->$method();
            if (empty($r)) {
                $error = sprintf(
                    "When method %s returns an empty value, " .
                    "%s should not be in array",
                    $method,
                    $k
                );
                self::assertArrayNotHasKey($k, $data, $error);
            } else {
                self::assertEquals($data[$k], $this->message->$method());
            }
        }
    }

    public function testToArrayWithArrayData(): void
    {
        $this->message->setAdditional("foo", ["foo" => "bar"]);
        $data = $this->message->toArray();

        $map = [
            "version"       => "getVersion",
            "host"          => "getHost",
            "timestamp"     => "getTimestamp",
            "full_message"  => "getFullMessage",
            "short_message" => "getShortMessage",
            "line"          => "getLine",
            "file"          => "getFile",
            "facility"      => "getFacility",
            "level"         => "getSyslogLevel"
        ];

        foreach ($map as $k => $method) {
            $r = $this->message->$method();
            if (empty($r)) {
                $error = sprintf(
                    "When method %s returns an empty value, " .
                    "%s should not be in array",
                    $method,
                    $k
                );
                self::assertArrayNotHasKey($k, $data, $error);
            } else {
                self::assertEquals($data[$k], $this->message->$method());
            }
        }
    }

    public function testToArrayV11(): void
    {
        $this->message->setVersion("1.1");
        $this->message->setShortMessage("lorem ipsum");
        $this->message->setAdditional("foo", "bar");
        $this->message->setAdditional("bool-true", true);
        $this->message->setAdditional("bool-false", false);
        $this->message->setAdditional("int-zero", 0);

        // check that deprecated behaviour is overridden in 1.1
        $this->message->setLine(50);
        $this->message->setAdditional("line", 100);

        $this->message->setFile("foo/bar");

        $data = $this->message->toArray();

        self::assertSame('1.1', $data['version']);
        self::assertSame('lorem ipsum', $data['short_message']);

        self::assertArrayHasKey('_line', $data);
        self::assertSame(100, $data['_line']);
        self::assertArrayNotHasKey('line', $data);

        self::assertArrayHasKey('_file', $data);
        self::assertSame('foo/bar', $data['_file']);
        self::assertArrayNotHasKey('file', $data);

        self::assertArrayHasKey('_foo', $data);
        self::assertSame('bar', $data['_foo']);
        self::assertArrayHasKey("_bool-true", $data);
        self::assertTrue($data["_bool-true"]);
        self::assertArrayHasKey("_bool-false", $data);
        self::assertFalse($data["_bool-false"]);
        self::assertArrayHasKey("_int-zero", $data);
        self::assertEquals(0, $data["_int-zero"]);
    }
}
