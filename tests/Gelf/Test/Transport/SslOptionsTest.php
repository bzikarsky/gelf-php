<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf\Test\Transport;

use Gelf\TestCase;
use Gelf\Transport\SslOptions;

class SslOptionsTest extends TestCase
{
    public function testState()
    {
        $options = new SslOptions();

        // test sane defaults
        $this->assertTrue($options->getVerifyPeer());
        $this->assertFalse($options->getAllowSelfSigned());
        $this->assertNull($options->getCaFile());
        $this->assertNull($options->getCiphers());

        // test setters
        $options->setVerifyPeer(false);
        $options->setAllowSelfSigned(true);
        $options->setCaFile('/path/to/ca');
        $options->setCiphers('ALL:!ADH:@STRENGTH');

        $this->assertFalse($options->getVerifyPeer());
        $this->assertTrue($options->getAllowSelfSigned());
        $this->assertEquals('/path/to/ca', $options->getCaFile());
        $this->assertEquals('ALL:!ADH:@STRENGTH', $options->getCiphers());
    }

    public function testToStreamContext()
    {
        $options = new SslOptions();

        $this->assertEquals(array(
            'ssl' => array(
                'verify_peer' => true,
                'allow_self_signed' => false,
            )
        ), $options->toStreamContext());

        $options->setVerifyPeer(false);
        $options->setAllowSelfSigned(true);
        $options->setCaFile('/path/to/ca');
        $options->setCiphers('ALL:!ADH:@STRENGTH');

        $this->assertEquals(array(
            'ssl' => array(
                'verify_peer' => false,
                'allow_self_signed' => true,
                'cafile' => '/path/to/ca',
                'ciphers' => 'ALL:!ADH:@STRENGTH'
            )
        ), $options->toStreamContext());

        $options->setCaFile(null);
        $options->setCiphers(null);

        $this->assertEquals(array(
            'ssl' => array(
                'verify_peer' => false,
                'allow_self_signed' => true,
            )
        ), $options->toStreamContext());
    }
}
