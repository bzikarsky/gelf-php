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

use Gelf\Encoder\CompressedJsonEncoder;
use Gelf\Encoder\JsonEncoder;
use Gelf\Transport\TcpTransport;
use Gelf\Transport\SslOptions;
use PHPUnit\Framework\TestCase;

class TcpTransportTest extends TestCase
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
     * @var TcpTransport
     */
    protected $transport;

    protected $testMessage;

    public function setUp()
    {
        $this->testMessage = str_repeat("0123456789", 30); // 300 char string

        $this->socketClient = $this->getMockBuilder("\\Gelf\\Transport\\StreamSocketClient")
                ->disableOriginalConstructor()
                ->getMock();
        $this->message = $this->createMock("\\Gelf\\Message");

        // create an encoder always return $testMessage
        $this->encoder = $this->createMock("\\Gelf\\Encoder\\NoNullByteEncoderInterface");
        $this->encoder->expects($this->any())->method('encode')->will(
            $this->returnValue($this->testMessage)
        );

        $this->transport = $this->getTransport();
    }

    protected function getTransport()
    {
        // initialize transport with an unlimited packet-size
        // and the mocked message encoder
        $transport = new TcpTransport(null, null);
        $transport->setMessageEncoder($this->encoder);

        // replace internal stream socket client with our mock
        $reflectedTransport = new \ReflectionObject($transport);
        $reflectedClient = $reflectedTransport->getProperty('socketClient');
        $reflectedClient->setAccessible(true);
        $reflectedClient->setValue($transport, $this->socketClient);

        return $transport;
    }

    public function testConstructor()
    {
        $transport = new TcpTransport();
        $this->validateTransport($transport, '127.0.0.1', 12201);

        $transport = new TcpTransport('test.local', 2202);
        $this->validateTransport($transport, 'test.local', 2202);

        // test defaults:
        //   port 12202 without explicit SSL options       => sslOptions: default
        $transport = new TcpTransport('localhost', 12202);
        $this->validateTransport($transport, 'localhost', 12202, new SslOptions());
    }

    public function validateTransport(
        TcpTransport $transport,
        $host,
        $port,
        $sslOptions = null
    ) {
        $r = new \ReflectionObject($transport);

        foreach (array('host', 'port', 'sslOptions') as $test) {
            $p = $r->getProperty($test);
            $p->setAccessible(true);
            $this->assertEquals(${$test}, $p->getValue($transport));
        }
    }

    public function testSslOptionsAreUsed()
    {
        $sslOptions = $this->createMock('\\Gelf\\Transport\\SslOptions');
        $sslOptions->expects($this->exactly(2))
            ->method('toStreamContext')
            ->will($this->returnValue(array('ssl' => null)));

        $transport = new TcpTransport("localhost", "12202", $sslOptions);

        $reflectedTransport = new \ReflectionObject($transport);
        $reflectedGetContext = $reflectedTransport->getMethod('getContext');
        $reflectedGetContext->setAccessible(true);
        $context = $reflectedGetContext->invoke($transport);

        $this->assertEquals(array('ssl' => null), $context);
    }

    public function testSetEncoder()
    {
        $encoder = $this->createMock('\\Gelf\\Encoder\\NoNullByteEncoderInterface');
        $this->transport->setMessageEncoder($encoder);

        $this->assertEquals($encoder, $this->transport->getMessageEncoder());
    }

    public function testGetEncoder()
    {
        $transport = new TcpTransport();
        $this->assertInstanceOf(
            "\\Gelf\\Encoder\\EncoderInterface",
            $transport->getMessageEncoder()
        );
    }

    public function testSend()
    {
        $this->socketClient
            ->expects($this->once())
            ->method('write')
            // TCP protocol requires every message to be
            // terminated with \0
            ->with($this->testMessage . "\0");

        $this->transport->send($this->message);
    }

    public function testConnectTimeout()
    {
        $this->socketClient
            ->expects($this->once())
            ->method('getConnectTimeout')
            ->will($this->returnValue(123));

        $this->assertEquals(123, $this->transport->getConnectTimeout());

        $this->socketClient
            ->expects($this->once())
            ->method('setConnectTimeout')
            ->with(123);

        $this->transport->setConnectTimeout(123);
    }

    public function testNonNullSafeEncoderFails()
    {
        $this->expectException('InvalidArgumentException');
        $this->transport->setMessageEncoder(new CompressedJsonEncoder());
    }

    public function testSafeEncoderSucceeds()
    {
        $this->assertInstanceOf(
            "Gelf\Encoder\NoNullByteEncoderInterface",
            $this->transport->getMessageEncoder()
        );

        $encoder = new JsonEncoder();
        $this->assertInstanceOf(
            "Gelf\Encoder\NoNullByteEncoderInterface",
            $encoder
        );

        $this->transport->setMessageEncoder($encoder);
        $this->assertEquals($encoder, $this->transport->getMessageEncoder());
    }
}
