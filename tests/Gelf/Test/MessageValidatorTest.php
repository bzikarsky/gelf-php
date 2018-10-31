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

use Gelf\MessageInterface;
use Gelf\MessageValidator;
use PHPUnit\Framework\TestCase;

class MessageValidatorTest extends TestCase
{

    /**
     * @var MessageValidator
     */
    protected $messageValidator;

    public function setUp()
    {
        $this->messageValidator = new MessageValidator();
    }

    /**
     * @dataProvider versions
     */
    public function testValid($version)
    {
        $msg = $this->getMessage("lorem", "example.local", $version);
        $this->assertTrue($this->messageValidator->validate($msg, $reason));
    }

    /**
     * @dataProvider versions
     */
    public function testZeroMessagesValidates($version)
    {
        $msg = $this->getMessage(0, "example.local", $version);
        $this->assertTrue($this->messageValidator->validate($msg));

        $msg = $this->getMessage("0", "example.local", $version);
        $this->assertTrue($this->messageValidator->validate($msg));
    }

    /**
     * @dataProvider versions
     */
    public function testInvalidAddtionalFieldID($version)
    {
        $msg = $this->getMessage(
            "lorem ipsum",
            "example.local",
            $version,
            array('id' => 1)
        );

        $this->assertFalse($this->messageValidator->validate($msg, $reason));
        $this->assertContains('id', $reason);
    }

    public function testInvalidAddtionalKeyV11()
    {
        $msg = $this->getMessage(
            "lorem",
            "example.local",
            "1.1",
            array('foo?' => 1)
        );

        $this->assertFalse($this->messageValidator->validate($msg, $reason));
        $this->assertContains('additional', $reason);
    }

    private function getMessage(
        $shortMessage = "lorem ipsum",
        $host = "example.local",
        $version = "1.0",
        $additionals = array()
    ) {
        $msg = $this->getMockBuilder(MessageInterface::class)->getMock();
        $msg->expects($this->any())->method('getHost')
            ->will($this->returnValue($host));
        $msg->expects($this->any())->method('getVersion')
            ->will($this->returnValue($version));
        $msg->expects($this->any())->method('getShortMessage')
            ->will($this->returnValue($shortMessage));

        $msg->expects($this->any())->method('getAllAdditionals')
            ->will($this->returnValue($additionals));

        $msg->expects($this->any())->method('hasAdditional')
            ->will($this->returnCallback(
                function ($key) use ($additionals) {
                    return isset($additionals[$key]);
                }
            ));

        return $msg;
    }

    public static function versions()
    {
        return array(array('1.0'), array('1.1'));
    }
}
