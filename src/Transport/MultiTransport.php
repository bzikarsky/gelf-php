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

class MultiTransport implements TransportInterface
{
    private $transports;

    public function __construct(TransportInterface ...$transports)
    {
        $this->transports = $transports;
    }

    public function addTransport(TransportInterface $transport): self
    {
        $this->transports[] = $transport;

        return $this;
    }

    public function send(array $data): void
    {
        foreach ($this->transports as $transport) {
            $transport->send($data);
        }
    }
}
