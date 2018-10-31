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

namespace Gelf;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public function failsOnHHVM(): void
    {
        if (\defined('HHVM_VERSION') && !\getenv('FORCE_HHVM_TESTS')) {
            $this->markTestSkipped('Relies on missing HHVM functionaility');
        }
    }
}
