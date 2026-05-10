<?php

namespace TomvdPeet\MarkdownNegotiationBundle\Tests\Http;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use TomvdPeet\MarkdownNegotiationBundle\Http\MarkdownNegotiator;

class MarkdownNegotiatorTest extends TestCase
{
    public function testItDetectsWhenMarkdownIsPreferred(): void
    {
        $negotiator = new MarkdownNegotiator();

        foreach ($this->provideAcceptHeaders() as $label => [$accept, $expected]) {
            $server = [];
            if (null !== $accept) {
                $server['HTTP_ACCEPT'] = $accept;
            }

            $this->assertSame($expected, $negotiator->prefersMarkdown(Request::create('/docs', 'GET', [], [], [], $server)), $label);
        }
    }

    /**
     * @return array<string, array{string|null, bool}>
     */
    private function provideAcceptHeaders(): array
    {
        return [
            'markdown beats html' => ['text/markdown, text/html;q=0.5', true],
            'html beats markdown' => ['text/html, text/markdown;q=0.5', false],
            'html ties markdown' => ['text/html, text/markdown', false],
            'wildcard only' => ['*/*', false],
            'wildcard beats markdown' => ['text/markdown;q=0.5, */*;q=0.8', false],
            'markdown only' => ['text/markdown', true],
            'markdown forbidden' => ['text/markdown;q=0, text/html', false],
            'no accept header' => [null, false],
        ];
    }
}
