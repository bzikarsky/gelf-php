<?php
declare(strict_types=1);

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf\Test\Transport;

use Gelf\Transport\SslOptions;
use PHPUnit\Framework\TestCase;

class SslOptionsTest extends TestCase
{
    public function testState(): void
    {
        $options = new SslOptions();

        // test sane defaults
        self::assertTrue($options->getVerifyPeer());
        self::assertFalse($options->getAllowSelfSigned());
        self::assertNull($options->getCaFile());
        self::assertNull($options->getCiphers());

        // test setters
        $options->setVerifyPeer(false);
        $options->setAllowSelfSigned(true);
        $options->setCaFile('/path/to/ca');
        $options->setCiphers('ALL:!ADH:@STRENGTH');

        self::assertFalse($options->getVerifyPeer());
        self::assertTrue($options->getAllowSelfSigned());
        self::assertEquals('/path/to/ca', $options->getCaFile());
        self::assertEquals('ALL:!ADH:@STRENGTH', $options->getCiphers());
    }

    public function testToStreamContext(): void
    {
        $options = new SslOptions();

        self::assertEquals([
            'ssl' => [
                'verify_peer' => true,
                'allow_self_signed' => false,
            ]
        ], $options->toStreamContext());

        $options->setVerifyPeer(false);
        $options->setAllowSelfSigned(true);
        $options->setCaFile('/path/to/ca');
        $options->setCiphers('ALL:!ADH:@STRENGTH');

        self::assertEquals([
            'ssl' => [
                'verify_peer' => false,
                'allow_self_signed' => true,
                'cafile' => '/path/to/ca',
                'ciphers' => 'ALL:!ADH:@STRENGTH'
            ]
        ], $options->toStreamContext());

        $options->setCaFile(null);
        $options->setCiphers(null);

        self::assertEquals([
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

        self::assertArrayHasKey('ssl', $context);
        self::assertArrayHasKey('SNI_enabled', $context['ssl']);
        self::assertArrayNotHasKey('CN_match', $context['ssl']);
        self::assertArrayHasKey($sniPeerNameKey, $context['ssl']);

        self::assertEquals(true, $context['ssl']['SNI_enabled']);
        self::assertEquals($host, $context['ssl'][$sniPeerNameKey]);


        $options->setVerifyPeer(true);
        $context = $options->toStreamContext($host);

        self::assertArrayHasKey('ssl', $context);
        self::assertArrayHasKey('SNI_enabled', $context['ssl']);
        self::assertArrayHasKey($peerNameKey, $context['ssl']);
        self::assertArrayHasKey($sniPeerNameKey, $context['ssl']);

        self::assertEquals(true, $context['ssl']['SNI_enabled']);
        self::assertEquals($host, $context['ssl'][$peerNameKey]);
        self::assertEquals($host, $context['ssl'][$sniPeerNameKey]);
    }
}
