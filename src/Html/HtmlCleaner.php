<?php

namespace TomvdPeet\MarkdownNegotiationBundle\Html;

class HtmlCleaner
{
    /**
     * @var string[]
     */
    private array $parentNodes = [];

    /**
     * @var array<int, true>
     */
    private array $startedNodes = [];

    private string $currentText = '';

    /**
     * @var string[]
     */
    private array $result = [];

    private ?string $baseUrl = null;

    /**
     * @var array{includeHrefs: bool, includeImgSrc: bool}
     */
    private array $options = [
        'includeHrefs' => true,
        'includeImgSrc' => true,
    ];

    private const DEAD_END_NODES = ['head', 'nav', '#comment', 'svg', 'hr', 'iframe', 'br', 'script', 'meta', 'style', 'select', 'footer', 'dialog', 'aside', 'details'];

    private const STRUCTURE_NODES = ['p', 'blockquote', 'strong', 'b', 'i', 'em', 'code', 'pre', 'ul', 'ol', 'li', 'table', 'tbody', 'tr', 'td', 'th', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    /**
     * @param array{includeHrefs?: bool, includeImgSrc?: bool} $options
     */
    public function clean(string $html, ?string $baseUrl = null, array $options = []): string
    {
        $copy = clone $this;

        return $copy->doClean($html, $baseUrl, $options);
    }

    /**
     * @param array{includeHrefs?: bool, includeImgSrc?: bool} $options
     */
    private function doClean(string $html, ?string $baseUrl = null, array $options = []): string
    {
        $this->baseUrl = $baseUrl;
        $this->options = array_merge($this->options, $options);

        $root = $this->createDocument($html)->documentElement;
        if (null === $root) {
            return '';
        }

        $this->parse($root);

        return implode($this->result);
    }

    private function createDocument(string $html): \DOMDocument
    {
        $document = new \DOMDocument();

        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $document->encoding = 'UTF-8';
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }

    private function parse(\DOMNode $node): void
    {
        $nodeName = $node->nodeName;
        if ('#text' === $nodeName) {
            $text = preg_replace('/\s+/', ' ', (string) $node->textContent) ?? '';
            if ('' === trim($text)) {
                $lastResult = end($this->result);
                if ('' !== $this->currentText || (\is_string($lastResult) && !str_ends_with($lastResult, '>'))) {
                    $this->currentText .= ' ';
                }

                return;
            }

            if ('' !== $text) {
                $this->currentText .= $text;
            }

            return;
        }

        if ('img' === $nodeName) {
            $attributes = $this->getNodeAttributes($node);
            $this->result[] = "<img$attributes>";

            return;
        }

        if ('br' === $nodeName) {
            $this->currentText .= '<br>';

            return;
        }

        if (in_array($nodeName, self::DEAD_END_NODES, true)) {
            return;
        }

        $this->startNode($node);
        $this->parseChildren($node);
        $this->endNode($node);
    }

    private function startNode(\DOMNode $node): void
    {
        $nodeName = $node->nodeName;

        if (in_array($nodeName, self::STRUCTURE_NODES, true)) {
            $this->startedNodes[spl_object_id($node)] = true;
            $attributes = $this->getNodeAttributes($node);
            if ('' !== $this->currentText) {
                $this->result[] = $this->currentText;
                $this->currentText = '';
            }
            $this->result[] = "<$nodeName $attributes>";
        }

        $this->parentNodes[] = $nodeName;
    }

    private function endNode(\DOMNode $node): void
    {
        $text = $this->currentText;
        $this->currentText = '';

        $nodeName = array_pop($this->parentNodes);
        $oid = spl_object_id($node);

        if (isset($this->startedNodes[$oid])) {
            $this->result[] = "$text</{$nodeName}>";
            unset($this->startedNodes[$oid]);

            return;
        }

        if ('' !== $text) {
            $this->result[] = $text;
        }
    }

    private function parseChildren(\DOMNode $node): void
    {
        foreach ($node->childNodes as $child) {
            $this->parse($child);
        }
    }

    private function getNodeAttributes(\DOMNode $node): string
    {
        $nodeName = $node->nodeName;

        if ('a' === $nodeName) {
            if (!$this->options['includeHrefs']) {
                return 'href=""';
            }

            return 'href="'.$this->relativeUrl($node->getAttribute('href')).'"';
        }

        if ('img' === $nodeName) {
            $url = null;
            if ($this->options['includeImgSrc']) {
                $url = $this->relativeUrl($node->getAttribute('src'));
            }

            return $this->combineAttributes(['src' => $url, 'alt' => $node->getAttribute('alt')]);
        }

        return '';
    }

    /**
     * @param array<string, string|null> $attributes
     */
    private function combineAttributes(array $attributes): string
    {
        $string = '';
        foreach ($attributes as $attribute => $value) {
            if (null !== $value && '' !== $value) {
                $string .= " $attribute=\"$value\"";
            }
        }

        return $string;
    }

    private function relativeUrl(string $url): string
    {
        if ('' === $url || '#' === $url[0]) {
            return $url;
        }

        if (null === $this->baseUrl) {
            return $url;
        }

        $urlParts = parse_url($url);
        if (false === $urlParts) {
            return $url;
        }

        if (!isset($urlParts['scheme']) && !isset($urlParts['host'])) {
            return $url;
        }

        $base = parse_url($this->baseUrl) ?: [];
        $urlHost = strtolower($urlParts['host'] ?? '');
        $baseHost = strtolower($base['host'] ?? '');

        if ('' === $urlHost || $urlHost !== $baseHost) {
            return $url;
        }

        $relative = $urlParts['path'] ?? '/';
        if (isset($urlParts['query'])) {
            $relative .= '?'.$urlParts['query'];
        }
        if (isset($urlParts['fragment'])) {
            $relative .= '#'.$urlParts['fragment'];
        }

        return $relative;
    }
}
