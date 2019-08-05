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

namespace Gelf\Transport\Stream;

/**
 * Abstraction of supported SSL configuration parameters
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 * @internal
 */
class SslOptions
{
    /**
     * Enable certificate validation of remote party
     *
     * @param boolean
     */
    private $verifyPeer = true;

    /**
     * Allow self-signed certificates
     *
     * @param boolean
     */
    private $allowSelfSigned = false;

    /**
     * Path to custom CA
     *
     * @param string|null
     */
    private $caFile = null;

    /**
     * List of ciphers the SSL layer may use
     *
     * Formatted as specified in `ciphers(1)`
     *
     * @param string|null
     */
    private $ciphers = null;

    /**
     * Return whether self-signed certificates are allowed
     *
     * @return bool
     */
    public function getAllowSelfSigned(): bool
    {
        return $this->allowSelfSigned;
    }

    /**
     * Enable or disable the error on self-signed certificates
     *
     * @param boolean $allowSelfSigned
     * @return self
     */
    public function setAllowSelfSigned(bool $allowSelfSigned): self
    {
        $this->allowSelfSigned = $allowSelfSigned;

        return $this;
    }

    /**
     * Return the (optional) path to a custom CA
     *
     * @return string|null
     */
    public function getCaFile(): ?string
    {
        return $this->caFile;
    }

    /**
     * Set the path toa custom CA
     *
     * @param string|null $caFile
     * @return self
     */
    public function setCaFile(?string  $caFile): self
    {
        $this->caFile = $caFile;

        return $this;
    }

    /**
     * Return des description of allowed ciphers
     *
     * @return string|null
     */
    public function getCiphers(): ?string
    {
        return $this->ciphers;
    }

    /**
     * Set the allowed SSL/TLS ciphers
     *
     * Format must follow `ciphers(1)`
     *
     * @param string|null $ciphers
     * @return self
     */
    public function setCiphers($ciphers): self
    {
        $this->ciphers = $ciphers;

        return $this;
    }

    /**
     * Return whether to check the peer certificate
     *
     * @return bool
     */
    public function getVerifyPeer(): bool
    {
        return $this->verifyPeer;
    }

    /**
     * Enable or disable the peer certificate check
     *
     * @param boolean $verifyPeer
     * @return self
     */
    public function setVerifyPeer(bool $verifyPeer): self
    {
        $this->verifyPeer = $verifyPeer;

        return $this;
    }

    /**
     * Returns a stream-context representation of this config
     *
     * @param string|null $serverName
     * @return array
     */
    public function toStreamContext(?string $serverName = null): array
    {
        $sslContext = [
            'verify_peer'       => $this->verifyPeer,
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
            $sslContext['peer_name'] = $serverName;

            if ($this->verifyPeer) {
                $sslContext['peer_name'] = $serverName;
            }
        }

        return ['ssl' => $sslContext];
    }
}
