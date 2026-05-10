<?php

namespace TomvdPeet\MarkdownNegotiationBundle\EventListener;

use League\HTMLToMarkdown\HtmlConverterInterface;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use TomvdPeet\MarkdownNegotiationBundle\Html\HtmlCleaner;
use TomvdPeet\MarkdownNegotiationBundle\Routing\MarkdownRouteMap;

class MarkdownResponseListener
{
    public function __construct(
        private readonly MarkdownRouteMap $markdownRouteMap,
        private readonly HtmlConverterInterface $converter,
        private readonly HtmlCleaner $htmlCleaner,
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (!\is_string($route) || !$this->markdownRouteMap->has($route)) {
            return;
        }

        $response = $event->getResponse();

        if ($this->isMarkdownResponse($response)) {
            $this->addVaryAccept($response);

            return;
        }

        if (!$this->prefersMarkdown($request)) {
            return;
        }

        if (!$this->canConvert($response)) {
            return;
        }

        $content = $response->getContent();
        if (false === $content || '' === trim($content)) {
            return;
        }

        $response->setContent($this->converter->convert($this->htmlCleaner->clean($content, $request->getUri())));
        $response->headers->set('Content-Type', 'text/markdown; charset='.($response->getCharset() ?: 'UTF-8'));
        $this->addVaryAccept($response);
    }

    private function canConvert(Response $response): bool
    {
        if (!$response->isSuccessful()) {
            return false;
        }

        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return false;
        }

        return 'text/html' === $this->getContentType($response);
    }

    private function isMarkdownResponse(Response $response): bool
    {
        return 'text/markdown' === $this->getContentType($response);
    }

    private function prefersMarkdown(Request $request): bool
    {
        $accept = AcceptHeader::fromString($request->headers->get('Accept'));
        $markdown = $accept->get('text/markdown');

        if (null === $markdown || $markdown->getQuality() <= 0.0) {
            return false;
        }

        $html = $accept->get('text/html');

        return null === $html || $markdown->getQuality() > $html->getQuality();
    }

    private function getContentType(Response $response): ?string
    {
        $contentType = $response->headers->get('Content-Type');
        if (null === $contentType) {
            return null;
        }

        return strtolower(trim(explode(';', $contentType, 2)[0]));
    }

    private function addVaryAccept(Response $response): void
    {
        $response->setVary(array_unique(array_merge($response->getVary(), ['Accept'])));
    }
}
