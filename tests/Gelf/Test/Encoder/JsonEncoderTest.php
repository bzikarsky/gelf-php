<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf\Test\Encoder;

use Gelf\Encoder\JsonEncoder;
use PHPUnit_Framework_TestCase as TestCase;

class JsonEncoderTest extends TestCase
{

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $message;

    /**
     * @var CompressedJsonEncoder
     */
    protected $encoder;

    public function setUp()
    {
        $this->message = $this->getMock('\\Gelf\\Message');
        $this->encoder = new JsonEncoder();
    }

    public function testEncode()
    {
        $testData = array('foo' => 'bar');

        $this->message
            ->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($testData));

        $json = $this->encoder->encode($this->message);

        // check that there is JSON inside
        $data = json_decode($json, $assoc = true);
        $this->assertTrue(is_array($data));

        // check that we have our data array
        $this->assertEquals($testData, $data);
    }
}
