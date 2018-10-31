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

namespace Gelf\Test\Encoder;

use Gelf\Encoder\JsonEncoder;
use Gelf\MessageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JsonEncoderTest extends TestCase
{
    /**
     * @var MockObject|MessageInterface
     */
    protected $message;

    /**
     * @var CompressedJsonEncoder
     */
    protected $encoder;

    protected function setUp(): void
    {
        $this->message = $this->getMockBuilder(MessageInterface::class)->getMock();
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
        $data = \json_decode($json, $assoc = true);
        $this->assertInternalType('array', $data);

        // check that we have our data array
        $this->assertEquals($testData, $data);
    }
}
