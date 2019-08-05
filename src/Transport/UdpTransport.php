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

namespace Gelf\Transport;

use Gelf\MessageInterface as Message;
use Gelf\Transport\Encoder\CompressedJsonEncoder;
use Gelf\Transport\Encoder\EncoderInterface;
use Gelf\Transport\Encoder\JsonEncoder;
use Gelf\Transport\Stream\StreamSocketClient;
use RuntimeException;

/**
 * UdpTransport allows the transfer of GELF-messages to an compatible GELF-UDP-backend
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class UdpTransport implements TransportInterface
{
    public const CHUNK_GELF_ID = "\x1e\x0f";

    public const CHUNK_MAX_COUNT = 128; // as per GELF spec

    public const CHUNK_SIZE_LAN = 8154;

    public const CHUNK_SIZE_WAN = 1420;

    public const DEFAULT_HOST = '127.0.0.1';

    public const DEFAULT_PORT = 12201;

    /**
     * @var int
     */
    private $chunkSize;

    /**
     * @var StreamSocketClient
     */
    private $socketClient;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * Class constructor
     *
     * @param string $host
     * @param int    $port
     * @param int    $chunkSize defaults to CHUNK_SIZE_WAN,
     *                          0 disables chunks completely
     */
    public function __construct(
        string $host = self::DEFAULT_HOST,
        int $port = self::DEFAULT_PORT,
        int $chunkSize = self::CHUNK_SIZE_WAN
    ) {
        // allow NULL-like values for fallback on default
        $host = $host ?: self::DEFAULT_HOST;
        $port = $port ?: self::DEFAULT_PORT;

        $this->socketClient = new StreamSocketClient('udp', $host, $port);
        $this->chunkSize = $chunkSize;

        $this->encoder = new CompressedJsonEncoder();
    }

    /** @inheritdoc */
    public function send(array $data): void
    {
        $rawMessage = $this->encoder->encode($data);

        // test if we need to split the message to multiple chunks
        // chunkSize == 0 allows for an unlimited packet-size, and therefore
        // disables chunking
        if ($this->chunkSize && \strlen($rawMessage) > $this->chunkSize) {
            $this->sendMessageInChunks($rawMessage);
        }

        // send message in one packet
        $this->socketClient->write($rawMessage);
    }

    /**
     * Sends given string in multiple chunks
     *
     * @param  string $rawMessage
     *
     * @throws TransportException on too large messages which would exceed the
                                  maximum number of possible chunks
     */
    private function sendMessageInChunks(string $rawMessage): void
    {
        // split to chunks
        $chunks = \str_split($rawMessage, $this->chunkSize);
        $numChunks = \count($chunks);

        if ($numChunks > self::CHUNK_MAX_COUNT) {
            throw new RuntimeException(
                \sprintf(
                    'Message is too big. Chunk count exceeds %d',
                    self::CHUNK_MAX_COUNT
                )
            );
        }

        // generate a random 8byte-message-id
        $messageId = \substr(\md5(\uniqid('', true), true), 0, 8);

        // send chunks with a correct chunk-header
        // @link http://graylog2.org/gelf#specs
        foreach ($chunks as $idx => $chunk) {
            $data = self::CHUNK_GELF_ID                    // GELF chunk magic bytes
                . $messageId                               // unique message id
                . \pack('CC', $idx, $numChunks)     // sequence information
                . $chunk;                                  // Actual chunk data

            $this->socketClient->write($data);
        }
    }

    /**
     * En- or disable the use of a compressing GELF encoder
     *
     * @param bool $enable
     * @return self
     */
    public function useCompression(bool $enable = true): self
    {
        $this->encoder = $enable ? new CompressedJsonEncoder() : new JsonEncoder();
        return $this;
    }
}
