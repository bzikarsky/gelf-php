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

// When creating a logger without any options, it logs automatically to localhost:12201 via UDP
// For a move advanced configuration, check out the advanced.php example
$logger = new Gelf\Logger();

// Log!
$logger->alert("Foobaz!");
