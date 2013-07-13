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

$transport = new Gelf\Transport\UdpTransport();
$publisher = new Gelf\Publisher($transport);
$logger = new Gelf\Logger($publisher);

try {
    throw new Exception("test exception");
} catch (Exception $e) {
    $logger->emergency(
        "Exception example",
        array('exception' => $e)
    );
}
