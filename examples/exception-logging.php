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

// Logger takes an optional Publisher
// If the publisher is omitted (or equals null) a default publisher
// is created which logs GELF to  udp://localhost:12201
$logger = new Gelf\Logger(null);

// throw an exception, catch it immediately and pass it
// to the logger
try {
    throw new Exception("test exception");
} catch (Exception $e) {
    $logger->emergency(
        "Exception example",
        array('exception' => $e)
    );
}
