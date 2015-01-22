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

$transport = new Gelf\Transport\HttpTransport("127.0.0.1", 12201);
$publisher = new Gelf\Publisher();
$publisher->addTransport($transport);

// Now we can create custom messages and publish them
$message = new Gelf\Message();
$message->setShortMessage("Foobar!")
        ->setFullMessage("There was a foo in bar")
        ->setLine(10)
        ->setAdditional('ta', 'ma')
        ->setAdditional("foo", "bar")
;
// Publish to gray log
$publisher->publish($message);
