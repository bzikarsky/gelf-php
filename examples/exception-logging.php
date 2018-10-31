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

require_once __DIR__ . '/../vendor/autoload.php';

// Logger takes an optional Publisher and a facility name
// If the publisher is omitted (or equals null) a default publisher
// is created which logs GELF to  udp://localhost:12201
$logger = new Gelf\Logger(null, 'test facility');

// throw an exception, catch it immediately and pass it to the logger
try {
    throw new RuntimeException('test exception');
} catch (Exception $e) {
    $logger->emergency('Exception example', ['exception' => $e]);
}
