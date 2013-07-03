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
        $msg = $this->getMock('\Gelf\MessageInterface');
        $msg->expects($this->exactly(2))->method('getVersion')->will($this->returnValue("1.0"));
        $msg->expects($this->once())->method('getHost')->will($this->returnValue("example.local"));
        $msg->expects($this->once())->method('getShortMessage')->will($this->returnValue("lorem ipsum"));

        $this->assertTrue($this->messageValidator->validate($msg));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInvalidVersion()
    {
        $msg = $this->getMock('\Gelf\MessageInterface');
        $msg->expects($this->atLeastOnce())->method('getVersion')->will($this->returnValue("0.1"));

        $this->messageValidator->validate($msg);
    }

    public function testMissingShortMessage()
    {
        $msg = $this->getMock('\Gelf\MessageInterface');
        $msg->expects($this->atLeastOnce())->method('getVersion')->will($this->returnValue("1.0"));
        $msg->expects($this->any())->method('getHost')->will($this->returnValue("example.local"));

        $this->assertFalse($this->messageValidator->validate($msg));
    }

    public function testMissingHost()
    {
        $msg = $this->getMock('\Gelf\MessageInterface');
        $msg->expects($this->atLeastOnce())->method('getVersion')->will($this->returnValue("1.0"));
        $msg->expects($this->any())->method('getShortMessage')->will($this->returnValue("lorem ipsum"));

        $this->assertFalse($this->messageValidator->validate($msg));
    }

    public function testMissingVersion()
    {
        $msg = $this->getMock('\Gelf\MessageInterface');
        $msg->expects($this->any())->method('getHost')->will($this->returnValue("example.local"));
        $msg->expects($this->any())->method('getShortMessage')->will($this->returnValue("lorem ipsum"));


        // direct into version validate, parent would throw invalid version
        $this->assertFalse($this->messageValidator->validate0100($msg));
    }
}
