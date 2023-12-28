<?php

namespace SolaPhp\Http\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use SolaPhp\Http\Psr7\Uri;

class Request extends Message implements RequestInterface
{
    public const AVAILABLE_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    protected Uri $uri;

    public function __construct(
        protected string $method,
        UriInterface|string $uri,
        protected array $headers = [],
        protected string $body = '',
    ) {
        if (is_string($uri)) {
            $this->uri = new Uri($uri);
        }
    }

    /**
     * @inheritDoc
     */
    public function getRequestTarget(): string
    {
        return $this->uri->__toString();
    }

    /**
     * @inheritDoc
     */
    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        $this->validateRequestTarget($requestTarget);
        $that = clone $this;

        $that->uri = new Uri($requestTarget);
        return $that;
    }


    /**
     * @inheritDoc
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @inheritDoc
     */
    public function withMethod(string $method): RequestInterface
    {
        $method = $this->validateMethod($method);
        $that = clone $this;

        $that->method = $method;
        return $that;
    }

    /**
     * @inheritDoc
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @inheritDoc
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        if ($uri === $this->uri) {
            return $this;
        }

        $that = clone $this;
        $that->uri = $uri;

        if (!$preserveHost || !$this->hasHeader('Host')) {
            $that->updateHostFromUri();
        }

        return $that;
    }

    private function updateHostFromUri(): void
    {
        if ('' === $host = $this->uri->getHost()) {
            return;
        }

        if (null !== ($port = $this->uri->getPort())) {
            $host .= ':' . $port;
        }

        if (array_key_exists('host', $this->headers)) {
            $header = $this->headers['host'];
        } else {
            $this->headers['host'] = $header = [];
        }

        // Ensure Host is the first header.
        // See: http://tools.ietf.org/html/rfc7230#section-5.4
        $this->headers = [$header => [$host]] + $this->headers;
    }

    protected function validateMethod(string $method): string
    {
        $method = strtoupper($method);
        if (!in_array($method, self::AVAILABLE_METHODS)) {
            throw new InvalidArgumentException("Invalid HTTP method $method");
        }

        return $method;
    }

    protected function validateRequestTarget(string $requestTarget): void
    {
        if (!is_string($requestTarget)) {
            throw new InvalidArgumentException('Request target must be a string');
        }

        if (preg_match('/\s/', $requestTarget)) {
            throw new InvalidArgumentException('Request target contains whitespace');
        }
    }
}
