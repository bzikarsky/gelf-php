<?php

namespace Gelf\Test\Transport;

use Gelf\Message;
use Gelf\TestCase;
use Gelf\Transport\AbstractTransport;
use Gelf\Transport\IgnoreErrorTransportWrapper;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;

class IgnoreErrorTransportWrapperTest extends TestCase
{
    public function testSend()
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
     * @return MockObject|AbstractTransport
     */
    private function buildTransport()
    {
        return $this->getMockBuilder(AbstractTransport::class)->getMock();
    }

    /**
     * @return MockObject|Message
     */
    private function buildMessage()
    {
        return $this->getMockBuilder(Message::class)->getMock();
    }
}
