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

    public function testValid()
    {
        $this->assertTrue(
            $this->messageValidator->validate($this->getMessage())
        );
    }
    
    public function testZeroMessagesValidates()
    {
        $msg = $this->getMessage(0);
        $this->assertTrue($this->messageValidator->validate($msg));
    
        $msg = $this->getMessage("0");
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
    
    public function testMissingShortMessage()
    {
        $msg = $this->getMessage(null, "example.local", "1.0");
        $this->assertFalse($this->messageValidator->validate($msg));
    }

    public function testMissingHost()
    {
        $msg = $this->getMessage("lorem ipsum", null, "1.0");
        $this->assertFalse($this->messageValidator->validate($msg));
    }

    public function testMissingVersion()
    {
        $msg = $this->getMessage("lorem ipsum", "example.local", null);

        // direct into version validate, parent would throw invalid version
        $this->assertFalse($this->messageValidator->validate0100($msg));
    }

    private function getMessage(
        $shortMessage = "lorem ipsum", 
        $host = "example.local", 
        $version = "1.0"
    )
    {
        $msg = $this->getMock('Gelf\MessageInterface');
        $msg->expects($this->any())->method('getHost')
            ->will($this->returnValue($host));
        $msg->expects($this->any())->method('getVersion')
            ->will($this->returnValue($version));
        $msg->expects($this->any())->method('getShortMessage')
            ->will($this->returnValue($shortMessage));

        return $msg;
    }
}
