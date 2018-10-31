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

namespace Gelf\Test\Transport;

use Gelf\Message;
use Gelf\MessageInterface;
use Gelf\TestCase;
use Gelf\Transport\AbstractTransport;
use Gelf\Transport\IgnoreErrorTransportWrapper;
use Gelf\Transport\TransportInterface;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;

class IgnoreErrorTransportWrapperTest extends TestCase
{
    public function testSend(): void
    {
        $expectedMessage   = $this->buildMessage();
        $expectedException = new \RuntimeException();

        $transport = $this->buildTransport();
        $wrapper   = new IgnoreErrorTransportWrapper($transport);

        $transport->expects($this->once())
                  ->method('send')
                  ->with($expectedMessage)
                  ->willThrowException($expectedException);

        $bytes = $wrapper->send($expectedMessage);
        $lastError = $wrapper->getLastError();

        $this->assertEquals(0, $bytes);
        $this->assertSame($expectedException, $lastError);
    }

    /**
     * @return MockObject|TransportInterface
     */
    private function buildTransport(): MockObject
    {
        return $this->getMockBuilder(TransportInterface::class)->getMock();
    }

    /**
     * @return MockObject|Message
     */
    private function buildMessage(): MockObject
    {
        return $this->getMockBuilder(MessageInterface::class)->getMock();
    }
}
