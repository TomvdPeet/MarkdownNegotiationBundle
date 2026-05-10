<?php

namespace TomvdPeet\MarkdownNegotiationBundle\Http;

use Symfony\Component\HttpFoundation\Response;

class MarkdownResponse extends Response
{
    public const CONTENT_TYPE = 'text/markdown';

    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(?string $content = '', int $status = 200, array $headers = [])
    {
        parent::__construct($content, $status, $headers);

        if (!$this->headers->has('Content-Type')) {
            $this->headers->set('Content-Type', self::CONTENT_TYPE.'; charset='.($this->getCharset() ?: 'UTF-8'));
        }
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    public static function negotiated(?string $content = '', int $status = 200, array $headers = []): self
    {
        $response = new self($content, $status, $headers);
        $response->setVary(array_unique(array_merge($response->getVary(), ['Accept'])));

        return $response;
    }
}
