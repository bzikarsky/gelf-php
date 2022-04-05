<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf\Transport;

use Gelf\MessageInterface;
use Gelf\Encoder\CompressedJsonEncoder;
use Gelf\Encoder\JsonEncoder as DefaultEncoder;
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
class HttpTransport extends AbstractTransport
{
    private const DEFAULT_HOST = "127.0.0.1";
    private const DEFAULT_PORT = 12202;
    private const DEFAULT_PATH = "/gelf";
    private const AUTO_SSL_PORT = 443;

    private StreamSocketClient $socketClient;

    private ?string $authentication = null;
    private ?string $proxyUri = null;
    private ?bool $requestFullUri = false;

    public function __construct(
        private string $host = self::DEFAULT_HOST,
        private int $port = self::DEFAULT_PORT,
        private string $path = self::DEFAULT_PATH,
        private ?SslOptions $sslOptions = null
    ) {
        parent::__construct();

        if ($port == self::AUTO_SSL_PORT && $sslOptions === null) {
            $this->sslOptions = new SslOptions();
        }

        $this->socketClient = new StreamSocketClient(
            $this->getScheme(),
            $this->host,
            $this->port,
            $this->getContext()
        );
    }

    /**
     * Creates a HttpTransport from a URI
     *
     * Supports http and https schemes, port-, path- and auth-definitions
     * If the port is omitted 80 and 443 are used respectively.
     * If a username but no password is given, and empty password is used.
     * If a https URI is given, the provided SslOptions (with a fallback to
     * the default SslOptions) are used.
     */
    public static function fromUrl(string $url, ?SslOptions $sslOptions = null): self
    {
        $parsed = parse_url($url);
        
        // check it's a valid URL
        if (false === $parsed || !isset($parsed['host']) || !isset($parsed['scheme'])) {
            throw new \InvalidArgumentException("$url is not a valid URL");
        }
        
        // check it's http or https
        $scheme = strtolower($parsed['scheme']);
        if (!in_array($scheme, ['http', 'https'])) {
            throw new \InvalidArgumentException("$url is not a valid http/https URL");
        }

        // setup defaults
        $defaults = ['port' => 80, 'path' => '', 'user' => null, 'pass' => ''];

        // change some defaults for https
        if ($scheme == 'https') {
            $sslOptions = $sslOptions ?: new SslOptions();
            $defaults['port'] = 443;
        }
         
        // merge defaults and real data and build transport
        $parsed = array_merge($defaults, $parsed);
        $transport = new self($parsed['host'], $parsed['port'], $parsed['path'], $sslOptions);

        // add optional authentication
        if ($parsed['user']) {
            $transport->setAuthentication($parsed['user'], $parsed['pass']);
        }

        return $transport;
    }

    /**
     * Sets HTTP basic authentication
     */
    public function setAuthentication(string $username, string $password): void
    {
        $this->authentication = $username . ":" . $password;
    }

    /**
     * Enables HTTP proxy
     */
    public function setProxy(string $proxyUri, bool $requestFullUri = false): void
    {
        $this->proxyUri = $proxyUri;
        $this->requestFullUri = $requestFullUri;

        $this->socketClient->setContext($this->getContext());
    }

    /**
     * @inheritDoc
     */
    public function send(MessageInterface $message): int
    {
        $messageEncoder = $this->getMessageEncoder();
        $rawMessage = $messageEncoder->encode($message);

        $request = [
            sprintf("POST %s HTTP/1.1", $this->path),
            sprintf("Host: %s:%d", $this->host, $this->port),
            sprintf("Content-Length: %d", strlen($rawMessage)),
            "Content-Type: application/json",
            "Connection: Keep-Alive",
            "Accept: */*"
        ];

        if (null !== $this->authentication) {
            $request[] = "Authorization: Basic " . base64_encode($this->authentication);
        }

        if ($messageEncoder instanceof CompressedJsonEncoder) {
            $request[] = "Content-Encoding: gzip";
        }

        $request[] = ""; // blank line to separate headers from body
        $request[] = $rawMessage;

        $request = implode("\r\n", $request);

        $byteCount = $this->socketClient->write($request);
        $headers = $this->readResponseHeaders();

        // if we don't have a HTTP/1.1 connection, or the server decided to close the connection
        // we should do so as well. next read/write-attempt will open a new socket in this case.
        if (!str_starts_with($headers, "HTTP/1.1") || preg_match("!Connection:\s*Close!i", $headers)) {
            $this->socketClient->close();
        }

        if (!preg_match("!^HTTP/1.\d 202 Accepted!i", $headers)) {
            throw new RuntimeException(
                sprintf(
                    "Graylog-Server didn't answer properly, expected 'HTTP/1.x 202 Accepted', response is '%s'",
                    trim($headers)
                )
            );
        }

        return $byteCount;
    }

    private function readResponseHeaders(): string
    {
        $chunkSize = 1024; // number of bytes to read at once
        $delimiter = "\r\n\r\n"; // delimiter between headers and response
        $response = "";

        do {
            $chunk = $this->socketClient->read($chunkSize);
            $response .= $chunk;
        } while (!str_contains($chunk, $delimiter) && strlen($chunk) > 0);

        $elements = explode($delimiter, $response, 2);

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
            $options = array_merge($options, $this->sslOptions->toStreamContext($this->host));
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
     * Sets the connect-timeout
     */
    public function setConnectTimeout(int $timeout): void
    {
        $this->socketClient->setConnectTimeout($timeout);
    }

    /**
     * Returns the connect-timeout
     */
    public function getConnectTimeout(): int
    {
        return $this->socketClient->getConnectTimeout();
    }
}
