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

namespace Gelf\Transport;

use Gelf\MessageInterface as Message;
use Gelf\Encoder\CompressedJsonEncoder as DefaultEncoder;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

/**
 * UdpTransport allows the transfer of GELF-messages to an compatible
 * GELF-UDP-backend as described in
 * https://github.com/Graylog2/graylog2-docs/wiki/GELF
 *
 * It can also act as a direct publisher
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class UdpTransport extends AbstractTransport
{
    private const CHUNK_GELF_ID = "\x1e\x0f";
    private const CHUNK_MAX_COUNT = 128; // as per GELF spec
    private const CHUNK_HEADER_LENGTH = 12; // GELF ID (2b), id (8b) , sequence (2b)
    
    public const CHUNK_SIZE_LAN = 8154;
    public const CHUNK_SIZE_WAN = 1420;

    private const DEFAULT_HOST = "127.0.0.1";
    private const DEFAULT_PORT = 12201;
    
    private StreamSocketClient $socketClient;

    /**
     * Class constructor
     *
     * @param string $host when NULL or empty DEFAULT_HOST is used
     * @param int $port when NULL or empty DEFAULT_PORT is used
     * @param int $chunkSize defaults to CHUNK_SIZE_WAN,
     *                          0 disables chunks completely
     */
    public function __construct(
        string $host = self::DEFAULT_HOST,
        int $port = self::DEFAULT_PORT,
        private int $chunkSize = self::CHUNK_SIZE_WAN
    ) {
        parent::__construct();

        // allow NULL-like values for fallback on default
        $host = $host ?: self::DEFAULT_HOST;
        $port = $port ?: self::DEFAULT_PORT;

        $this->socketClient = new StreamSocketClient('udp', $host, $port);

        if ($chunkSize > 0 && $chunkSize <= self::CHUNK_HEADER_LENGTH) {
            throw new InvalidArgumentException('Chunk-size has to exceed ' . self::CHUNK_HEADER_LENGTH
                . ' which is the number of bytes reserved for the chunk-header');
        }
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): int
    {
        $rawMessage = $this->getMessageEncoder()->encode($message);

        // test if we need to split the message to multiple chunks
        // chunkSize == 0 allows for an unlimited packet-size, and therefore
        // disables chunking
        if ($this->chunkSize && strlen($rawMessage) > $this->chunkSize) {
            return $this->sendMessageInChunks($rawMessage);
        }

        // send message in one packet
        $this->socketClient->write($rawMessage);

        return 1;
    }

    /**
     * Sends given string in multiple chunks
     */
    private function sendMessageInChunks(string $rawMessage): int
    {
        /** @var int<1, max> $length */
        $length = $this->chunkSize - self::CHUNK_HEADER_LENGTH;

        // split to chunks
        $chunks = str_split($rawMessage, $length);

        $numChunks = count($chunks);

        if ($numChunks > self::CHUNK_MAX_COUNT) {
            throw new RuntimeException(
                sprintf(
                    "Message is too big. Chunk count exceeds %d",
                    self::CHUNK_MAX_COUNT
                )
            );
        }

        // generate a random 8byte-message-id
        $messageId = substr(md5(uniqid("", true), true), 0, 8);

        // send chunks with a correct chunk-header
        // @link http://graylog2.org/gelf#specs
        foreach ($chunks as $idx => $chunk) {
            $data = self::CHUNK_GELF_ID            // GELF chunk magic bytes
                . $messageId                       // unique message id
                . pack('CC', $idx, $numChunks)     // sequence information
                . $chunk                           // chunk-data
            ;

            $this->socketClient->write($data);
        }

        return $numChunks;
    }
}
