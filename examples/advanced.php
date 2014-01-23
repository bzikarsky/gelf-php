<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// We need a transport - UDP via port 12201 is standard.
$transport = new Gelf\Transport\UdpTransport("127.0.0.1", 12201, Gelf\Transport\UdpTransport::CHUNK_SIZE_LAN);

// While the UDP transport is itself a publisher, we wrap it in a real Publisher for convenience
// A publisher allows for message validation before transmission, and it calso supports to send messages
// to multiple backends at once
$publisher = new Gelf\Publisher();
$publisher->addTransport($transport);

// Now we can create custom messages and publish them
$message = new Gelf\Message();
$message->setShortMessage("Foobar!")
    ->setLevel(\Psr\Log\LogLevel::ALERT)
    ->setFullMessage("There was a foo in bar")
    ->setFacility("example-facility")
;
$publisher->publish($message);


// The implementation of PSR-3 is encapsulated in the Logger-class.
// It provides high-level logging methods, such as alert(), info(), etc.
$logger = new Gelf\Logger($publisher, "example-facility");

// Now we can log...
$logger->alert("Foobaz!");
