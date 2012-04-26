<?php

require __DIR__ . '/../GELFMessage.php';
require __DIR__ . '/../GELFMessagePublisher.php';


class GelfMessageTest extends PHPUnit_Framework_TestCase
{
    public function testGelfMessage()
    {
        $message = new GELFMessage();
        $message->setShortMessage('something is broken.');
        $message->setFullMessage("lol full message!");
        $message->setHost('somehost');
        $message->setLevel(2);
        $message->setFile('/var/www/example.php');
        $message->setLine(1337);
        $message->setAdditional("something", "foo");
        $message->setAdditional("something_else", "bar");

        $this->assertEquals(
            array(
                'version' => null,
                'timestamp' => null,
                'short_message' => 'something is broken.',
                'full_message' => 'lol full message!',
                'facility' => null,
                'host' => 'somehost',
                'level' => 2,
                'file' => '/var/www/example.php',
                'line' => 1337,
                '_something' => 'foo',
                '_something_else' => 'bar',
            ),
            $message->toArray()
        );
    }
}

