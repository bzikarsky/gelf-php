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

namespace Gelf\Test\Encoder;

use Gelf\Encoder\CompressedJsonEncoder;
use Gelf\Encoder\JsonEncoder;
use Gelf\MessageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompressedJsonEncoderTest extends TestCase
{
    private MockObject|MessageInterface $message;
    private CompressedJsonEncoder $encoder;

    public function setUp(): void
    {
        $this->message = $this->createMock(MessageInterface::class);
        $this->encoder = new CompressedJsonEncoder();
    }

    public function testEncode()
    {
        $testData = ['foo' => 'bar'];

        $this->message
            ->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($testData));

        $bytes = $this->encoder->encode($this->message);

        // check for valid zlib-compressed string
        $this->assertEquals("\x78\x9c", substr($bytes, 0, 2));

        // check that it's uncompressable
        $json = gzuncompress($bytes);

        // check that there is JSON inside
        $data = json_decode($json, associative: true);

        // check that we have our data array
        $this->assertEquals($testData, $data);
    }
}
