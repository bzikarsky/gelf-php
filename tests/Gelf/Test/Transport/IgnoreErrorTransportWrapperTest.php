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
        $expectedMessage = $this->buildMessage();

        $transport = $this->buildTransport();
        $wrapper   = new IgnoreErrorTransportWrapper($transport);

        $transport->expects($this->once())
                  ->method('send')
                  ->with($expectedMessage)
                  ->willThrowException(new \RuntimeException());

        $bytes = $wrapper->send($expectedMessage);

        $this->assertEquals(0, $bytes);
    }

    /**
     * @return MockObject|AbstractTransport
     */
    private function buildTransport()
    {
        return $this->getMockForAbstractClass("\\Gelf\\Transport\\AbstractTransport");
    }

    /**
     * @return MockObject|Message
     */
    private function buildMessage()
    {
        return $this->getMockForAbstractClass("\\Gelf\\Message");
    }
}
