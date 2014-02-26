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

use Gelf\MessageValidator;
use PHPUnit_Framework_TestCase as TestCase;

class MessageValidatorTest extends TestCase
{

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
     * @expectedException RuntimeException
     */
    public function testInvalidVersion()
    {
        $msg = $this->getMessage("lorem ipsum", "example.local", null);
        $this->messageValidator->validate($msg);
    }

    /**
     * @dataProvider versions    
     */
    public function testMissingShortMessage($version)
    {
        $msg = $this->getMessage(null, "example.local", $version);
        $this->assertFalse($this->messageValidator->validate($msg, $reason));
        $this->assertContains('short-message', $reason);
    }

    /**
     * @dataProvider versions    
     */
    public function testMissingHost($version)
    {
        $msg = $this->getMessage("lorem ipsum", null, $version);
        $this->assertFalse($this->messageValidator->validate($msg, $reason));
        $this->assertContains('host', $reason);
    }

    public function testMissingVersion()
    {
        $msg = $this->getMessage("lorem ipsum", "example.local", null);

        // direct into version validate, parent would throw invalid version
        $this->assertFalse($this->messageValidator->validate0100($msg, $r));
        $this->assertContains('version', $r);
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
    )
    {
        $msg = $this->getMock('Gelf\MessageInterface');
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
                function($key) use ($additionals)
                {
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
