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

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


$autoloadFile = __DIR__ . '/../vendor/autoload.php';

if (!\file_exists($autoloadFile)) {
    die('Autoloader cannot be found. ' .
        "Please install dependencies first ('composer install --dev')\n");
}

require_once $autoloadFile;

require_once __DIR__ . '/Gelf/TestCase.php';
