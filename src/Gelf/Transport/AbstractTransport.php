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

use Gelf\Encoder\EncoderInterface;
use Gelf\Encoder\JsonEncoder;
use Gelf\MessageInterface;
use Gelf\PublisherInterface;

/**
 * The CompressedJsonEncoder allows the encoding of GELF messages as described
 * in http://www.graylog2.org/resources/documentation/sending/gelfhttp
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
abstract class AbstractTransport implements TransportInterface
{
    protected EncoderInterface $messageEncoder;

    public function __construct(?EncoderInterface $messageEncoder = null)
    {
        $this->messageEncoder = $messageEncoder ?? new JsonEncoder();
    }

    /**
     * Sets a message encoder
     */
    public function setMessageEncoder(EncoderInterface $encoder): static
    {
        $this->messageEncoder = $encoder;

        return $this;
    }

    /**
     * Returns the current message encoder
     */
    public function getMessageEncoder(): EncoderInterface
    {
        return $this->messageEncoder;
    }
}
