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

use Gelf\Transport\HttpTransport;
use PHPUnit_Framework_TestCase as TestCase;

class HttpTransportTest extends TestCase
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
     * @var HttpTransport
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

        $this->transport = $this->getTransport();
    }

    protected function getTransport()
    {
        // initialize transport with an unlimited packet-size
        // and the mocked message encoder
        $transport = new HttpTransport();
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
        $transport = new HttpTransport();
        $this->assertInstanceOf(
            "\\Gelf\\Encoder\\EncoderInterface",
            $transport->getMessageEncoder()
        );
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Graylog-Server didn't answer properly, expected 'HTTP/1.x 202 Accepted', response is ''
     */
    public function testEmptyResponseException()
    {
        $this->transport->send($this->message);
    }

    public function testSendUncompressed()
    {
        $request = "POST /gelf HTTP/1.1"."\r\n"
                 . "Host: 127.0.0.1:12202"."\r\n"
                 . "Content-Length: 300"."\r\n"
                 . "Content-Type: application/json"."\r\n"
                 . "Connection: Keep-Alive"."\r\n"
                 . "Accept: */*"."\r\n"
                 . ""."\r\n"
                 . $this->testMessage;

        $this->socketClient
            ->expects($this->once())
            ->method("write")
            ->with($request);

        $this->socketClient
            ->expects($this->once())
            ->method("read")
            ->will($this->returnValue("HTTP/1.1 202 Accepted\r\n\r\n"));

        $this->transport->send($this->message);
    }

    public function testSendCompressed()
    {
        $request = "POST /gelf HTTP/1.1"."\r\n"
                 . "Host: 127.0.0.1:12202"."\r\n"
                 . "Content-Length: 300"."\r\n"
                 . "Content-Type: application/json"."\r\n"
                 . "Connection: Keep-Alive"."\r\n"
                 . "Accept: */*"."\r\n"
                 . "Content-Encoding: gzip"."\r\n"
                 . ""."\r\n"
                 . $this->testMessage;

        $this->socketClient
            ->expects($this->once())
            ->method("write")
            ->with($request);

        $this->socketClient
            ->expects($this->once())
            ->method("read")
            ->will($this->returnValue("HTTP/1.1 202 Accepted\r\n\r\n"));

        $compressedEncoder = $this->getMock("\\Gelf\\Encoder\\CompressedJsonEncoder");
        $compressedEncoder
            ->expects($this->any())
            ->method('encode')
            ->will(
                $this->returnValue($this->testMessage)
            );
        $this->transport->setMessageEncoder($compressedEncoder);

        $this->transport->send($this->message);
    }

    public function testPublish()
    {
        $transport = $this->getMock(
            "\\Gelf\\Transport\\HttpTransport",
            $methods = array("send"),
            $args = array(),
            $mockClassName = '',
            $callConstructor = false
        );

        $transport
            ->expects($this->once())
            ->method("send")
            ->with($this->message)
            ->will($this->returnValue(42));

        $response = $transport->publish($this->message);

        $this->assertSame(42, $response);
    }

    public function testCloseSocketOnHttpOneZero()
    {
        $this->socketClient
            ->expects($this->once())
            ->method("read")
            ->will($this->returnValue("HTTP/1.0 202 Accepted\r\n\r\n"));

        $this->socketClient
            ->expects($this->once())
            ->method("close");

        $this->transport->send($this->message);
    }

    public function testCloseSocketOnConnectionClose()
    {
        $this->socketClient
            ->expects($this->once())
            ->method("read")
            ->will($this->returnValue("HTTP/1.1 202 Accepted\r\nConnection: Close\r\n\r\n"));

        $this->socketClient
            ->expects($this->once())
            ->method("close");

        $this->transport->send($this->message);
    }
}
