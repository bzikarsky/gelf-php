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

namespace Gelf\Test;

use Gelf\MessageInterface;
use Gelf\MessageValidatorInterface;
use Gelf\Publisher;
use Gelf\Transport\TransportInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PublisherTest extends TestCase
{
    private MockObject|TransportInterface $transportA;
    private MockObject|TransportInterface $transportB;
    private MockObject|MessageValidatorInterface $messageValidator;
    private MockObject|MessageInterface $message;
    private Publisher $publisher;

    public function setUp(): void
    {
        $this->transportA = $this->createMock(TransportInterface::class);
        $this->transportB = $this->createMock(TransportInterface::class);
        $this->messageValidator =
            $this->createMock(MessageValidatorInterface::class);
        $this->message = $this->createMock(MessageInterface::class);

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
            ->willReturn(true);

        $this->publisher->publish($this->message);
    }

    public function testPublishErrorOnInvalid(): void
    {
        $this->expectException(RuntimeException::class);
        $this->messageValidator->expects($this->once())
            ->method('validate')
            ->willReturn(false);

        $this->publisher->publish($this->message);
    }

    public function testMissingTransport(): void
    {
        $this->expectException(RuntimeException::class);
        $publisher = new Publisher(null, $this->messageValidator);
        self::assertCount(0, $publisher->getTransports());

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
            ->willReturn(true);

        $pub->publish($this->message);
    }

    public function testGetTransports(): void
    {
        $pub = new Publisher(null, $this->messageValidator);
        self::assertCount(0, $pub->getTransports());

        $pub->addTransport($this->transportA);
        self::assertCount(1, $pub->getTransports());

        $pub->addTransport($this->transportB);
        self::assertCount(2, $pub->getTransports());

        $pub->addTransport($this->transportA);
        self::assertCount(2, $pub->getTransports());
    }
}
