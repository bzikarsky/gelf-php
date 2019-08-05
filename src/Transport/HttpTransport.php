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

namespace Gelf\Transport;

use Gelf\Transport\Encoder\CompressedJsonEncoder;
use Gelf\Transport\Encoder\EncoderInterface;
use Gelf\Transport\Encoder\JsonEncoder;
use Gelf\Transport\Stream\SslOptions;
use Gelf\Transport\Stream\StreamSocketClient;
use RuntimeException;

/**
 * HttpTransport allows the transfer of GELF-messages to an compatible
 * GELF-HTTP-backend as described in
 * http://www.graylog2.org/resources/documentation/sending/gelfhttp
 *
 * It can also act as a direct publisher
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class HttpTransport implements TransportInterface
{
    public const DEFAULT_HOST = '127.0.0.1';

    public const DEFAULT_PORT = 12202;

    public const DEFAULT_PATH = '/gelf';

    public const AUTO_SSL_PORT = 443;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $path;

    /**
     * @var StreamSocketClient
     */
    private $socketClient;

    /**
     * @var SslOptions|null
     */
    private $sslOptions = null;

    /**
     * @var string|null
     */
    private $authentication = null;

    /**
     * @var string|null
     */
    private $proxyUri = null;

    /**
     * @var bool
     */
    private $requestFullUri = false;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * Class constructor
     *
     * @param string     $host
     * @param int        $port
     * @param string     $path
     * @param SslOptions|null
     */
    public function __construct(
        string $host = self::DEFAULT_HOST,
        int $port = self::DEFAULT_PORT,
        string $path = self::DEFAULT_PATH,
        ?SslOptions $sslOptions = null
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;

        if (self::AUTO_SSL_PORT === $port && null === $sslOptions) {
            $sslOptions = new SslOptions();
        }

        $this->sslOptions = $sslOptions;
        $this->socketClient = new StreamSocketClient(
            $this->getScheme(),
            $this->host,
            $this->port,
            $this->getContext()
        );

        $this->encoder = new CompressedJsonEncoder();
    }

    /**
     * Create a HttpTransport from a URI
     *
     * Supports http and https schemes, port-, path- and auth-definitions
     * If the port is omitted 80 and 443 are used respectively.
     * If a username but no password is given, and empty password is used.
     * If a https URI is given, the provided SslOptions (with a fallback to
     * the default SslOptions) are used.
     *
     * @param  string          $url
     * @param  SslOptions|null $sslOptions
     *
     * @return HttpTransport
     */
    public static function fromUrl(string $url, SslOptions $sslOptions = null): self
    {
        $parsed = \parse_url($url);

        // check it's a valid URL
        if (false === $parsed || !isset($parsed['host']) || !isset($parsed['scheme'])) {
            throw new \InvalidArgumentException("$url is not a valid URL");
        }

        // check it's http or https
        $scheme = \strtolower($parsed['scheme']);
        if (!\in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException("$url is not a valid http/https URL");
        }

        // setup defaults
        $defaults = ['port' => 80, 'path' => '', 'user' => null, 'pass' => ''];

        // change some defaults for https
        if ('https' === $scheme) {
            $sslOptions = $sslOptions ?: new SslOptions();
            $defaults['port'] = 443;
        }

        // merge defaults and real data and build transport
        $parsed = \array_merge($defaults, $parsed);
        $transport = new static($parsed['host'], $parsed['port'], $parsed['path'], $sslOptions);

        // add optional authentication
        if ($parsed['user']) {
            $transport->setAuthentication($parsed['user'], $parsed['pass']);
        }

        return $transport;
    }

    /**
     * Set HTTP basic authentication
     *
     * @param string $username
     * @param string $password
     * @return self
     */
    public function setAuthentication(string $username, string $password): self
    {
        $this->authentication = $username . ':' . $password;

        return $this;
    }

    /**
     * Enable HTTP proxy
     *
     * @param string $proxyUri
     * @param bool $requestFullUri
     * @return self
     */
    public function setProxy(string $proxyUri, bool $requestFullUri = false): self
    {
        $this->proxyUri = $proxyUri;
        $this->requestFullUri = $requestFullUri;

        $this->socketClient->setContext($this->getContext());

        return $this;
    }

    /** @inheritdoc */
    public function send(array $data): void
    {
        $rawMessage = $this->encoder->encode($data);

        $request = [
            \sprintf('POST %s HTTP/1.1', $this->path),
            \sprintf('Host: %s:%d', $this->host, $this->port),
            \sprintf('Content-Length: %d', \strlen($rawMessage)),
            'Content-Type: application/json',
            'Connection: Keep-Alive',
            'Accept: */*'
        ];

        if (null !== $this->authentication) {
            $request[] = 'Authorization: Basic ' . \base64_encode($this->authentication);
        }

        if ($this->encoder instanceof CompressedJsonEncoder) {
            $request[] = 'Content-Encoding: gzip';
        }

        $request[] = ''; // blank line to separate headers from body
        $request[] = $rawMessage;

        $request = \implode($request, "\r\n");

        $this->socketClient->write($request);
        $headers = $this->readResponseHeaders();

        // if we don't have a HTTP/1.1 connection, or the server decided to close the connection
        // we should do so as well. next read/write-attempt will open a new socket in this case.
        if (0 !== \strpos($headers, 'HTTP/1.1') || \preg_match('!Connection:\\s*Close!i', $headers)) {
            $this->socketClient->close();
        }

        if (!\preg_match('!^HTTP/1.\\d 202 Accepted!i', $headers)) {
            throw new RuntimeException(
                \sprintf(
                    "Graylog-Server didn't answer properly, expected 'HTTP/1.x 202 Accepted', response is '%s'",
                    \trim($headers)
                )
            );
        }
    }

    private function readResponseHeaders(): string
    {
        $chunkSize = 1024; // number of bytes to read at once
        $delimiter = "\r\n\r\n"; // delimiter between headers and response
        $response = '';

        do {
            $chunk = $this->socketClient->read($chunkSize);
            $response .= $chunk;
        } while (false === \strpos($chunk, $delimiter) && '' !== $chunk);

        $elements = \explode($delimiter, $response, 2);

        return $elements[0];
    }

    private function getScheme(): string
    {
        return null === $this->sslOptions ? 'tcp' : 'ssl';
    }

    private function getContext(): array
    {
        $options = [];

        if (null !== $this->sslOptions) {
            $options = \array_merge($options, $this->sslOptions->toStreamContext($this->host));
        }

        if (null !== $this->proxyUri) {
            $options['http'] = [
                'proxy' => $this->proxyUri,
                'request_fulluri' => $this->requestFullUri
            ];
        }

        return $options;
    }

    /**
     * Sets the connect-timeout (seconds)
     *
     * @param int $timeout
     * @return self
     */
    public function setConnectTimeout(int $timeout): self
    {
        $this->socketClient->setConnectTimeout($timeout);

        return $this;
    }

    /**
     * Returns the connect-timeout (seconds)
     *
     * @return int
     */
    public function getConnectTimeout(): int
    {
        return $this->socketClient->getConnectTimeout();
    }

    /**
     * En- or disable the use of a compressing GELF encoder
     *
     * @param bool $enable
     * @return HttpTransport
     */
    public function useCompression(bool $enable = true): self
    {
        $this->encoder = $enable ? new CompressedJsonEncoder() : new JsonEncoder();
        return $this;
    }
}
