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

use DateTime;
use Gelf\Message;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class MessageTest extends TestCase
{

    /**
     * @var Message
     */
    private $message;

    public function setUp()
    {
        $this->message = new Message('test-message');
    }


    public function testHost()
    {
        // default is current hostname
        $this->assertEquals(gethostname(), $this->message->getHost());

        $this->message->setHost("example.local");
        $this->assertEquals("example.local", $this->message->getHost());
    }

    public function testLevel()
    {
        $this->assertEquals(1, $this->message->getSyslogLevel());
        $this->assertEquals(LogLevel::ALERT, $this->message->getLevel());

        $this->message->setLevel(0);
        $this->assertEquals(0, $this->message->getSyslogLevel());
        $this->assertEquals(LogLevel::EMERGENCY, $this->message->getLevel());

        $this->message->setLevel(LogLevel::EMERGENCY);
        $this->assertEquals(0, $this->message->getSyslogLevel());
        $this->assertEquals(LogLevel::EMERGENCY, $this->message->getLevel());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLevelInvalidString(): void
    {
        $this->message->setLevel('invalid');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLevelInvalidInteger(): void
    {
        $this->message->setLevel(8);
    }

    public function testLogLevelToPsr(): void
    {
        $this->assertEquals(LogLevel::ALERT, Message::logLevelToPsr("alert"));
        $this->assertEquals(LogLevel::ALERT, Message::logLevelToPsr("ALERT"));
        $this->assertEquals(LogLevel::ALERT, Message::logLevelToPsr(1));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLogLevelToPsrInvalidString(): void
    {
        Message::logLevelToPsr('invalid');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLogLevelToPsrInvalidInt(): void
    {
        Message::logLevelToPsr(-1);
    }

    public function testAdditionals(): void
    {
        $this->assertInternalType('array', $this->message->getAllAdditionals());
        $this->assertCount(0, $this->message->getAllAdditionals());

        $this->assertFalse($this->message->hasAdditional("foo"));
        $this->message->setAdditional("foo", "bar");
        $this->assertEquals("bar", $this->message->getAdditional("foo"));
        $this->assertTrue($this->message->hasAdditional("foo"));
        $this->assertCount(1, $this->message->getAllAdditionals());

        $this->message->setAdditional("foo", "buk");
        $this->assertEquals("buk", $this->message->getAdditional("foo"));
        $this->assertCount(1, $this->message->getAllAdditionals());

        $this->assertEquals(
            array("foo" => "buk"),
            $this->message->getAllAdditionals()
        );
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSetAdditionalEmptyKey(): void
    {
        $this->message->setAdditional("", "test");
    }
    /**
     * @expectedException \RuntimeException
     */
    public function testGetAdditionalInvalidKey(): void
    {
        $this->message->getAdditional("invalid");
    }

    public function testSetTimestamp(): void
    {
        $dt = new \DateTime('@1393661544.3012');
        $this->message->setTimestamp($dt);

        $this->assertEquals($dt, $this->message->getTimestamp());
    }

    public function testMethodChaining(): void
    {
        $message = $this->message
            ->setTimestamp(new \DateTime())
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

        $this->assertEquals($this->message, $message);
    }

    public function testToArrayV10(): void
    {
        $this->message->setAdditional("foo", "bar");
        $this->message->setAdditional("bool-true", true);
        $this->message->setAdditional("bool-false", false);
        $data = $this->message->toArray();
        $this->assertInternalType('array', $data);

        // test additionals
        $this->assertArrayHasKey("_foo", $data);
        $this->assertEquals("bar", $data["_foo"]);
        $this->assertArrayHasKey("_bool-true", $data);
        $this->assertTrue($data["_bool-true"]);
        $this->assertArrayHasKey("_bool-false", $data);
        $this->assertFalse($data["_bool-false"]);

        // Test timestamp
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertEquals($this->message->getTimestamp()->format("U.u"), $data["timestamp"]);

        $map = array(
            "version"       => "getVersion",
            "host"          => "getHost",
            "full_message"  => "getFullMessage",
            "short_message" => "getShortMessage",
            "line"          => "getLine",
            "file"          => "getFile",
            "facility"      => "getFacility",
            "level"         => "getSyslogLevel"
        );

        foreach ($map as $k => $method) {
            $r = $this->message->$method();
            if (empty($r)) {
                $error = sprintf(
                    "When method %s returns an empty value, " .
                    "%s should not be in array",
                    $method,
                    $k
                );
                $this->assertFalse(array_key_exists($k, $data), $error);
            } else {
                $this->assertEquals($data[$k], $this->message->$method());
            }
        }
    }

    public function testToArrayWithArrayData(): void
    {
        $this->message->setAdditional("foo", array("foo" => "bar"));
        $data = $this->message->toArray();

        // Test timestamp
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertEquals($this->message->getTimestamp()->format("U.u"), $data["timestamp"]);

        $map = array(
            "version"       => "getVersion",
            "host"          => "getHost",
            "full_message"  => "getFullMessage",
            "short_message" => "getShortMessage",
            "line"          => "getLine",
            "file"          => "getFile",
            "facility"      => "getFacility",
            "level"         => "getSyslogLevel"
        );

        foreach ($map as $k => $method) {
            $r = $this->message->$method();
            if (empty($r)) {
                $error = sprintf(
                    "When method %s returns an empty value, " .
                    "%s should not be in array",
                    $method,
                    $k
                );
                $this->assertArrayNotHasKey($k, $data, $error);
            } else {
                $this->assertEquals($data[$k], $this->message->$method());
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

        // check that deperacted behaviour is overridden in 1.1
        $this->message->setLine(50);
        $this->message->setAdditional("line", 100);

        $this->message->setFile("foo/bar");

        $data = $this->message->toArray();

        $this->assertSame('1.1', $data['version']);
        $this->assertSame('lorem ipsum', $data['short_message']);

        $this->assertArrayHasKey('_line', $data);
        $this->assertSame(100, $data['_line']);
        $this->assertArrayNotHasKey('line', $data);

        $this->assertArrayHasKey('_file', $data);
        $this->assertSame('foo/bar', $data['_file']);
        $this->assertArrayNotHasKey('file', $data);

        $this->assertArrayHasKey('_foo', $data);
        $this->assertSame('bar', $data['_foo']);
        $this->assertArrayHasKey("_bool-true", $data);
        $this->assertTrue($data["_bool-true"]);
        $this->assertArrayHasKey("_bool-false", $data);
        $this->assertFalse($data["_bool-false"]);

        // Test timestamp
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertEquals($this->message->getTimestamp()->format("U.u"), $data["timestamp"]);
    }
}
