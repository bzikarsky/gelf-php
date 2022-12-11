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

namespace Gelf\Transport;

/**
 * Abstraction of supported SSL configuration parameters
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class SslOptions
{
    /**
     * Enable certificate validation of remote party
     */
    private bool $verifyPeer = true;

    /**
     * Allow self-signed certificates
     */
    private bool $allowSelfSigned = false;

    /**
     * Require verification of peer name.
     */
    private bool $verifyPeerName = true;

    /**
     * Path to custom CA
     */
    private ?string $caFile = null;

    /**
     * List of ciphers the SSL layer may use
     *
     * Formatted as specified in `ciphers(1)`
     */
    private ?string $ciphers = null;

    /**
     * Whether self-signed certificates are allowed
     */
    public function getAllowSelfSigned(): bool
    {
        return $this->allowSelfSigned;
    }

    /**
     * Enables or disables the error on self-signed certificates
     */
    public function setAllowSelfSigned(bool $allowSelfSigned): void
    {
        $this->allowSelfSigned = $allowSelfSigned;
    }

    /**
     * Returns the path to a custom CA
     */
    public function getCaFile(): ?string
    {
        return $this->caFile;
    }

    /**
     * Sets the path toa custom CA
     */
    public function setCaFile(?string $caFile): void
    {
        $this->caFile = $caFile;
    }

    /**
     * Returns des description of allowed ciphers
     */
    public function getCiphers(): ?string
    {
        return $this->ciphers;
    }

    /**
     * Set the allowed SSL/TLS ciphers
     *
     * Format must follow `ciphers(1)`
     */
    public function setCiphers(?string $ciphers): void
    {
        $this->ciphers = $ciphers;
    }

    /**
     * Whether to check the peer certificate
     */
    public function getVerifyPeer(): bool
    {
        return $this->verifyPeer;
    }

    /**
     * Enable or disable the peer certificate check
     */
    public function setVerifyPeer(bool $verifyPeer): void
    {
        $this->verifyPeer = $verifyPeer;
    }

    /**
     * Whether to check the peer name
     */
    public function getVerifyPeerName(): bool
    {
        return $this->verifyPeerName;
    }

    /**
     * Enable or disable the peer name check
     */
    public function setVerifyPeerName(bool $verifyPeerName): void
    {
        $this->verifyPeerName = $verifyPeerName;
    }

    /**
     * Returns a stream-context representation of this config
     */
    public function toStreamContext(?string $serverName = null): array
    {
        $sslContext = [
            'verify_peer'       => $this->verifyPeer,
            'verify_peer_name'  => $this->verifyPeerName,
            'allow_self_signed' => $this->allowSelfSigned
        ];

        if (null !== $this->caFile) {
            $sslContext['cafile'] = $this->caFile;
        }

        if (null !== $this->ciphers) {
            $sslContext['ciphers'] = $this->ciphers;
        }

        if (null !== $serverName) {
            $sslContext['SNI_enabled'] = true;
            $sslContext[PHP_VERSION_ID < 50600 ? 'SNI_server_name' : 'peer_name'] = $serverName;

            if ($this->verifyPeer) {
                $sslContext[PHP_VERSION_ID < 50600 ? 'CN_match' : 'peer_name'] = $serverName;
            }
        }

        return ['ssl' => $sslContext];
    }
}
