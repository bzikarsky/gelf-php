<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf\Test\Transport;

use Gelf\Transport\UdpTransport;
use PHPUnit_Framework_TestCase as TestCase;

class UdpTransportTest extends TestCase
{

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $socketClient;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $message;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $encoder;

    /**
     * @var UdpTransport
     */
    protected $transport;

    protected $testMessage;

    public function setUp()
    {
        $this->testMessage = str_repeat("0123456789", 30); // 300 char string

        $this->socketClient = $this->getMock(
            "\\Gelf\\Transport\\StreamSocketClient",
            $methods = array(),
            $args = array(),
            $mockClassName = '',
            $callConstructor = false
        );
        $this->message = $this->getMock("\\Gelf\\Message");

        // create an encoder always return $testMessage
        $this->encoder = $this->getMock("\\Gelf\\Encoder\\EncoderInterface");
        $this->encoder->expects($this->any())->method('encode')->will(
            $this->returnValue($this->testMessage)
        );

        $this->transport = $this->getTransport(0);
    }

    protected function getTransport($chunkSize)
    {
        // initialize transport with an unlimited packet-size
        // and the mocked message encoder
        $transport = new UdpTransport(null, null, $chunkSize);
        $transport->setMessageEncoder($this->encoder);

        // replace internal stream socket client with our mock
        $reflectedTransport = new \ReflectionObject($transport);
        $reflectedClient = $reflectedTransport->getProperty('socketClient');
        $reflectedClient->setAccessible(true);
        $reflectedClient->setValue($transport, $this->socketClient);

        return $transport;
    }

    public function testSetEncoder()
    {
        $encoder = $this->getMock('\\Gelf\\Encoder\\EncoderInterface');
        $this->transport->setMessageEncoder($encoder);

        $this->assertEquals($encoder, $this->transport->getMessageEncoder());
    }

    public function testGetEncoder()
    {
        $transport = new UdpTransport();
        $this->assertInstanceOf(
            "\\Gelf\\Encoder\\EncoderInterface", 
            $transport->getMessageEncoder()
        );
    }

    public function testSendUnchunked()
    {
        $this->socketClient
            ->expects($this->once())
            ->method('write')
            ->with($this->testMessage);

        $this->transport->send($this->message);
    }

    public function testSendChunked()
    {
        $chunkSize = 10;
        $transport = $this->getTransport(10);
        $expectedMessageCount =  strlen($this->testMessage) / $chunkSize;

        $this->socketClient
            ->expects($this->exactly($expectedMessageCount))
            ->method('write');

        $transport->send($this->message);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvalidChunkNumber()
    {
        $transport = $this->getTransport(1);
        $transport->send($this->message);
    }
}
