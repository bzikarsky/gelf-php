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

namespace Gelf\Test;

use Gelf\MessageInterface;
use Gelf\MessageValidatorInterface;
use Gelf\Publisher;
use Gelf\Transport\TransportInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PublisherTest extends TestCase
{
    /**
     * @var MockObject|TransportInterface
     */
    protected $transportA;

    /**
     * @var MockObject|TransportInterface
     */
    protected $transportB;

    /**
     * @var MockObject|MessageValidatorInterface
     */
    protected $messageValidator;

    /**
     * @var MockObject|MessageInterface
     */
    protected $message;

    /**
     * @var Publisher
     */
    protected $publisher;

    protected function setUp(): void
    {
        $this->transportA = $this->getMockBuilder(TransportInterface::class)->getMock();
        $this->transportB = $this->getMockBuilder(TransportInterface::class)->getMock();
        $this->messageValidator = $this->getMockBuilder(MessageValidatorInterface::class)->getMock();
        $this->message = $this->getMockBuilder(MessageInterface::class)->getMock();

        $this->publisher = new Publisher(
            $this->transportA,
            $this->messageValidator
        );
    }

    public function testPublish(): void
    {
        $this->transportA->expects($this->once())
            ->method('send')
            ->with($this->equalTo($this->message));

        $this->messageValidator->expects($this->once())
            ->method('validate')
            ->will($this->returnValue(true));

        $this->publisher->publish($this->message);
    }

    public function testPublishErrorOnInvalid(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->messageValidator->expects($this->once())
            ->method('validate')
            ->will($this->returnValue(false));

        $this->publisher->publish($this->message);
    }

    public function testMissingTransport(): void
    {
        $this->expectException(\RuntimeException::class);

        $publisher = new Publisher(null, $this->messageValidator);
        $this->assertCount(0, $publisher->getTransports());

        $publisher->publish($this->message);
    }

    public function testMultipleTransports(): void
    {
        $pub = $this->publisher;
        $pub->addTransport($this->transportB);
        $this->transportA->expects($this->once())
            ->method('send')
            ->with($this->equalTo($this->message));

        $this->transportB->expects($this->once())
            ->method('send')
            ->with($this->equalTo($this->message));

        $this->messageValidator->expects($this->once())
            ->method('validate')
            ->will($this->returnValue(true));

        $pub->publish($this->message);
    }

    public function testGetTransports(): void
    {
        $pub = new Publisher(null, $this->messageValidator);
        $this->assertCount(0, $pub->getTransports());

        $pub->addTransport($this->transportA);
        $this->assertCount(1, $pub->getTransports());

        $pub->addTransport($this->transportB);
        $this->assertCount(2, $pub->getTransports());

        $pub->addTransport($this->transportA);
        $this->assertCount(2, $pub->getTransports());
    }

    public function testInitWithDefaultValidator(): void
    {
        $pub = new Publisher();
        $this->assertInstanceOf(
            MessageValidatorInterface::class,
            $pub->getMessageValidator()
        );
    }
}
