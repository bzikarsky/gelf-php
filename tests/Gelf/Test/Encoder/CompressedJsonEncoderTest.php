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

use Gelf\Encoder\CompressedJsonEncoder;
use PHPUnit_Framework_TestCase as TestCase;

class CompressedJsonEncoderTest extends TestCase
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
        $this->encoder = new CompressedJsonEncoder();
    }

    public function testEncode()
    {
        $testData = array('foo' => 'bar');

        $this->message
            ->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($testData));

        $bytes = $this->encoder->encode($this->message);

        // check for valid zlib-compressed string
        $this->assertEquals("\x78\x9c", substr($bytes, 0, 2));

        // check that it's uncompressable
        $json = gzuncompress($bytes);
        $this->assertTrue(is_string($json));

        // check that there is JSON inside
        $data = json_decode($json, $assoc = true);
        $this->assertTrue(is_array($data));

        // check that we have our data array
        $this->assertEquals($testData, $data);
    }
}
