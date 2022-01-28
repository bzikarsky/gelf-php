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

use Gelf\Encoder\EncoderInterface;
use Gelf\Message;
use Gelf\Transport\HttpTransport;
use Gelf\Transport\SslOptions;
use Gelf\Transport\StreamSocketClient;
use PHPUnit\Framework\TestCase;

class HttpTransportTest extends TestCase
{

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|StreamSocketClient
     */
    protected $socketClient;

    /**
     * @var StreamSocketClient
     */
    protected $originalSocketClient;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Message
     */
    protected $message;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EncoderInterface
     */
    protected $encoder;

    /**
     * @var HttpTransport
     */
    protected $transport;

    protected $testMessage;

    public function setUp(): void
    {
        $this->testMessage = str_repeat("0123456789", 30); // 300 char string

        $this->socketClient = $this->createMock(
            "\\Gelf\\Transport\\StreamSocketClient",
            $methods = array(),
            $args = array(),
            $mockClassName = '',
            $callConstructor = false
        );
        $this->message = $this->createMock("\\Gelf\\Message");

        // create an encoder always return $testMessage
        $this->encoder = $this->createMock("\\Gelf\\Encoder\\EncoderInterface");
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

        $this->originalSocketClient = $reflectedClient->getValue($transport);
        $reflectedClient->setValue($transport, $this->socketClient);

        return $transport;
    }

    public function testConstructor()
    {
        $transport = new HttpTransport();
        $this->validateTransport($transport, '127.0.0.1', 12202, '/gelf');

        $transport = new HttpTransport('test.local', 80, '');
        $this->validateTransport($transport, 'test.local', 80, '');

        // test defaults:
        //   path NULL                                   => path: /gelf
        //   port 443 without explicit SSL options       => sslOptions: default
        $transport = new HttpTransport('localhost', 443);
        $this->validateTransport($transport, 'localhost', 443, '/gelf', new SslOptions());
    }

    public function testFromUrlConstructorInvalidUri()
    {
        $this->expectException(\InvalidArgumentException::class);
        HttpTransport::fromUrl('-://:-');
    }

    public function testFromUrlConstructorInvalidScheme()
    {
        $this->expectException(\InvalidArgumentException::class);
        HttpTransport::fromUrl('ftp://foobar');
    }

    public function testFromUrlConstructor()
    {
        $transport = HttpTransport::fromUrl('HTTP://localhost');
        $this->validateTransport($transport, 'localhost', 80, '', null, null);

        $transport = HttpTransport::fromUrl('http://localhost:1234');
        $this->validateTransport($transport, 'localhost', 1234, '', null, null);
        
        $transport = HttpTransport::fromUrl('http://localhost/abc');
        $this->validateTransport($transport, 'localhost', 80, '/abc', null, null);
        
        $transport = HttpTransport::fromUrl('http://localhost:1234/abc');
        $this->validateTransport($transport, 'localhost', 1234, '/abc', null, null);

        $transport = HttpTransport::fromUrl('http://user@localhost');
        $this->validateTransport($transport, 'localhost', 80, '', null, 'user:');

        $transport = HttpTransport::fromUrl('http://user:pass@localhost');
        $this->validateTransport($transport, 'localhost', 80, '', null, 'user:pass');

        $transport = HttpTransport::fromUrl('https://localhost');
        $this->validateTransport($transport, 'localhost', 443, '', new SslOptions(), null);
        
        $sslOptions = new SslOptions();
        $sslOptions->setVerifyPeer(false);
        $transport = HttpTransport::fromUrl('HTTPS://localhost', $sslOptions);
        $this->validateTransport($transport, 'localhost', 443, '', $sslOptions, null);
    }

    public function validateTransport(
        HttpTransport $transport,
        $host,
        $port,
        $path,
        $sslOptions = null,
        $authentication = null
    ) {
        $r = new \ReflectionObject($transport);

        $testProperties = array(
            'host' => $host,
            'port' => $port,
            'path' => $path,
            'sslOptions' => $sslOptions,
            'authentication' => $authentication
        );

        foreach ($testProperties as $property => $value) {
            $p = $r->getProperty($property);
            $p->setAccessible(true);
            $this->assertEquals($value, $p->getValue($transport));
        }
    }

    public function testSslOptionsAreUsed()
    {
        $sslOptions = $this->createMock('\\Gelf\\Transport\\SslOptions');
        $sslOptions->expects($this->exactly(2))
            ->method('toStreamContext')
            ->will($this->returnValue(array('ssl' => null)));

        $transport = new HttpTransport("localhost", "12345", "/gelf", $sslOptions);

        $reflectedTransport = new \ReflectionObject($transport);
        $reflectedGetContext = $reflectedTransport->getMethod('getContext');
        $reflectedGetContext->setAccessible(true);
        $context = $reflectedGetContext->invoke($transport);

        $this->assertEquals(array('ssl' => null), $context);
    }

    public function testSetEncoder()
    {
        $encoder = $this->createMock('\\Gelf\\Encoder\\EncoderInterface');
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

    public function testEmptyResponseException()
    {
        $this->expectExceptionMessage("Graylog-Server didn't answer properly, expected 'HTTP/1.x 202 Accepted', response is ''");
        $this->expectException(\RuntimeException::class);
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

    public function testAuthentication()
    {
        $this->transport->setAuthentication("test", "test");

        $test = $this;
        $this->socketClient->expects($this->once())
            ->method("write")
            ->will($this->returnCallback(function ($data) use ($test) {
                $test->assertStringContainsString("Authorization: Basic " . base64_encode("test:test"), $data);
            }));
            
        $this->socketClient
            ->expects($this->once())
            ->method("read")
            ->will($this->returnValue("HTTP/1.1 202 Accepted\r\n\r\n"));

        $this->transport->send($this->message);
    }

    public function testProxy()
    {
        $test = $this;
        $this->socketClient->expects($this->once())
            ->method("setContext")
            ->willReturnCallback(function (array $context) use ($test) {
                $test->assertArrayHasKey("http", $context);
                $test->assertEquals(
                    array(
                        "proxy" => "tcp://proxy.example.com:5100",
                        "request_fulluri" => true
                    ),
                    $context["http"]
                );
            });

        $this->transport->setProxy("tcp://proxy.example.com:5100", true);
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

        $compressedEncoder = $this->createMock("\\Gelf\\Encoder\\CompressedJsonEncoder");
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
        $transport = $this->getMockBuilder("\\Gelf\\Transport\\HttpTransport")
            ->setMethods(array("send"))
            ->disableOriginalConstructor()
            ->getMock();

        $transport
            ->expects($this->once())
            ->method("send")
            ->with($this->message)
            ->willReturn(42);

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
}
