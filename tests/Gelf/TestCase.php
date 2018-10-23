<?php

namespace Gelf;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{

    public function failsOnHHVM()
    {
        if (defined('HHVM_VERSION') && !getenv('FORCE_HHVM_TESTS')) {
            $this->markTestSkipped("Relies on missing HHVM functionaility");
        }
    }
}
