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

use Gelf\Encoder\JsonEncoder;
use Gelf\MessageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JsonEncoderTest extends TestCase
{
    private MockObject|MessageInterface $message;
    private JsonEncoder $encoder;

    public function setUp(): void
    {
        $this->message = $this->createMock(MessageInterface::class);
        $this->encoder = new JsonEncoder();
    }

    public function testEncode(): void
    {
        $testData = ['foo' => 'bar'];

        $this->message
            ->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($testData));

        $json = $this->encoder->encode($this->message);

        // check that there is JSON inside
        $data = json_decode($json, associative: true);

        // check that we have our data array
        self::assertEquals($testData, $data);
    }

    public function testUnicodeEncode(): void
    {
        $testData = ['foo' => 'бар'];

        $this->message
            ->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($testData));

        $json = $this->encoder->encode($this->message);

        self::assertEquals('{"foo":"бар"}', $json);
    }
}
