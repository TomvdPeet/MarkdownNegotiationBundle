<?php

namespace TomvdPeet\MarkdownNegotiationBundle\Tests\Html;

use PHPUnit\Framework\TestCase;
use TomvdPeet\MarkdownNegotiationBundle\Html\HtmlCleaner;

class HtmlCleanerTest extends TestCase
{
    public function testItKeepsMarkdownRelevantStructureAndDropsDecorativeMarkup(): void
    {
        $html = <<<'HTML'
            <!DOCTYPE html>
            <html>
                <head>
                    <title>Ignored title</title>
                    <style>body { color: red; }</style>
                </head>
                <body>
                    <nav>Ignored navigation</nav>
                    <main class="layout">
                        <section>
                            <h1>Article title</h1>
                            <p class="intro">Intro with <strong>strong text</strong>.</p>
                            <ul><li>First</li><li>Second</li></ul>
                        </section>
                    </main>
                    <footer>Ignored footer</footer>
                </body>
            </html>
            HTML;

        $cleaned = (new HtmlCleaner())->clean($html);

        $this->assertStringContainsString('<h1 >Article title</h1>', $cleaned);
        $this->assertStringContainsString('<p >Intro with <strong >strong text</strong>.</p>', $cleaned);
        $this->assertStringContainsString('<ul ><li >First</li><li >Second</li></ul>', $cleaned);
        $this->assertStringNotContainsString('Ignored navigation', $cleaned);
        $this->assertStringNotContainsString('Ignored footer', $cleaned);
        $this->assertStringNotContainsString('color: red', $cleaned);
    }

    public function testItCanKeepSameHostLinksRelative(): void
    {
        $cleaned = (new HtmlCleaner())->clean(
            '<p><a href="https://example.com/docs/page?foo=bar#part">Read more</a></p>',
            'https://example.com/current',
            ['includeHrefs' => true],
        );

        $this->assertStringContainsString('<a href="/docs/page?foo=bar#part">Read more</a>', $cleaned);
    }
}
