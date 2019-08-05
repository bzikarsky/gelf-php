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

namespace Gelf\Test\Transport;

use Gelf\Transport\Stream\SslOptions;
use PHPUnit\Framework\TestCase;

class SslOptionsTest extends TestCase
{
    public function testState(): void
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

    public function testToStreamContext(): void
    {
        $options = new SslOptions();

        $this->assertEquals([
            'ssl' => [
                'verify_peer' => true,
                'allow_self_signed' => false,
            ]
        ], $options->toStreamContext());

        $options->setVerifyPeer(false);
        $options->setAllowSelfSigned(true);
        $options->setCaFile('/path/to/ca');
        $options->setCiphers('ALL:!ADH:@STRENGTH');

        $this->assertEquals([
            'ssl' => [
                'verify_peer' => false,
                'allow_self_signed' => true,
                'cafile' => '/path/to/ca',
                'ciphers' => 'ALL:!ADH:@STRENGTH'
            ]
        ], $options->toStreamContext());

        $options->setCaFile(null);
        $options->setCiphers(null);

        $this->assertEquals([
            'ssl' => [
                'verify_peer' => false,
                'allow_self_signed' => true,
            ]
        ], $options->toStreamContext());
    }

    public function testToStreamContextWithHostname(): void
    {
        $options = new SslOptions();
        $peerNameKey = PHP_VERSION_ID < 50600 ? 'CN_match' : 'peer_name';
        $sniPeerNameKey = PHP_VERSION_ID < 50600 ? 'SNI_server_name' : 'peer_name';
        $host = 'test.local';

        $options->setVerifyPeer(false);
        $context = $options->toStreamContext($host);

        $this->assertArrayHasKey('ssl', $context);
        $this->assertArrayHasKey('SNI_enabled', $context['ssl']);
        $this->assertArrayNotHasKey('CN_match', $context['ssl']);
        $this->assertArrayHasKey($sniPeerNameKey, $context['ssl']);

        $this->assertTrue($context['ssl']['SNI_enabled']);
        $this->assertEquals($host, $context['ssl'][$sniPeerNameKey]);


        $options->setVerifyPeer(true);
        $context = $options->toStreamContext($host);

        $this->assertArrayHasKey('ssl', $context);
        $this->assertArrayHasKey('SNI_enabled', $context['ssl']);
        $this->assertArrayHasKey($peerNameKey, $context['ssl']);
        $this->assertArrayHasKey($sniPeerNameKey, $context['ssl']);

        $this->assertTrue($context['ssl']['SNI_enabled']);
        $this->assertEquals($host, $context['ssl'][$peerNameKey]);
        $this->assertEquals($host, $context['ssl'][$sniPeerNameKey]);
    }
}
