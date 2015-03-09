<?php

namespace Gelf;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{

    public function failsOnHHVM()
    {
        if (defined('HHVM_VERSION') && !getenv('FORCE_HHVM_TESTS')) {
            $this->markTestSkipped("Relies on missing HHVM functionaility");
        }
    }
}
