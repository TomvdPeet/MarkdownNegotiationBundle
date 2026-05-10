<?php

namespace TomvdPeet\MarkdownNegotiationBundle\Tests\Http;

use PHPUnit\Framework\TestCase;
use TomvdPeet\MarkdownNegotiationBundle\Http\MarkdownResponse;

class MarkdownResponseTest extends TestCase
{
    public function testItDefaultsToMarkdownContentType(): void
    {
        $response = new MarkdownResponse('# Hello');

        $this->assertSame('# Hello', $response->getContent());
        $this->assertSame('text/markdown; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame([], $response->getVary());
    }

    public function testItKeepsExplicitContentType(): void
    {
        $response = new MarkdownResponse('# Hello', 200, ['Content-Type' => 'text/plain']);

        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
    }

    public function testNegotiatedResponseVariesOnAccept(): void
    {
        $response = MarkdownResponse::negotiated('# Hello');

        $this->assertSame('text/markdown; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame(['Accept'], $response->getVary());
    }

    public function testNegotiatedResponsePreservesExistingVaryHeaders(): void
    {
        $response = MarkdownResponse::negotiated('# Hello', 200, ['Vary' => 'Accept-Encoding']);

        $this->assertSame(['Accept-Encoding', 'Accept'], $response->getVary());
    }
}
