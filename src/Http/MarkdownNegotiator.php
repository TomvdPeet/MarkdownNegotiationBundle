<?php

namespace TomvdPeet\MarkdownNegotiationBundle\Http;

use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;

class MarkdownNegotiator
{
    public function prefersMarkdown(Request $request): bool
    {
        $accept = AcceptHeader::fromString($request->headers->get('Accept'));
        $markdown = $accept->get(MarkdownResponse::CONTENT_TYPE);

        if (null === $markdown || $markdown->getQuality() <= 0.0) {
            return false;
        }

        $html = $accept->get('text/html');

        return null === $html || $markdown->getQuality() > $html->getQuality();
    }
}
