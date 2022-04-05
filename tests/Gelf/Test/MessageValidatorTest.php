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
use Gelf\MessageValidator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MessageValidatorTest extends TestCase
{
    private MessageValidator $messageValidator;

    public function setUp(): void
    {
        $this->messageValidator = new MessageValidator();
    }

    /**
     * @dataProvider versions
     */
    public function testValid(string $version): void
    {
        $msg = $this->getMessage("lorem", "example.local", $version);
        self::assertTrue($this->messageValidator->validate($msg, $reason));
    }

    /**
     * @dataProvider versions
     */
    public function testMissingShortMessage(string $version): void
    {
        $msg = $this->getMessage(null, "example.local", $version);
        self::assertFalse($this->messageValidator->validate($msg, $reason));
        self::assertStringContainsString('short-message', $reason);
    }

    /**
     * @dataProvider versions
     */
    public function testInvalidAddtionalFieldID(string $version): void
    {
        $msg = $this->getMessage(
            "lorem ipsum",
            "example.local",
            $version,
            ['id' => 1]
        );

        self::assertFalse($this->messageValidator->validate($msg, $reason));
        self::assertStringContainsString('id', $reason);
    }

    public function testInvalidAddtionalKeyV11(): void
    {
        $msg = $this->getMessage(
            "lorem",
            "example.local",
            "1.1",
            ['foo?' => 1]
        );

        self::assertFalse($this->messageValidator->validate($msg, $reason));
        self::assertStringContainsString('additional', $reason);
    }

    private function getMessage(
        ?string $shortMessage = "lorem ipsum",
        ?string $host = "example.local",
        ?string $version = "1.0",
        array $additionals = []
    ): MessageInterface {
        $msg = $this->createMock(MessageInterface::class);
        $msg->expects($this->any())->method('getHost')
            ->will($this->returnValue($host));
        $msg->expects($this->any())->method('getVersion')
            ->will($this->returnValue($version));
        $msg->expects($this->any())->method('getShortMessage')
            ->will($this->returnValue($shortMessage));

        $msg->expects($this->any())->method('getAllAdditionals')
            ->will($this->returnValue($additionals));

        $msg->expects($this->any())->method('hasAdditional')
            ->will($this->returnCallback(
                function ($key) use ($additionals) {
                    return isset($additionals[$key]);
                }
            ));

        return $msg;
    }

    public static function versions(): array
    {
        return [['1.0'], ['1.1']];
    }
}
