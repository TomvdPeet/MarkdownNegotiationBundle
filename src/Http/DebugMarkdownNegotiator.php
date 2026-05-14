<?php

namespace TomvdPeet\MarkdownNegotiationBundle\Http;

use Symfony\Component\HttpFoundation\Request;

class DebugMarkdownNegotiator extends MarkdownNegotiator
{
    public function __construct(private readonly string $debugQueryParameter)
    {
    }

    public function prefersMarkdown(Request $request): bool
    {
        if ($request->query->has($this->debugQueryParameter)) {
            return true;
        }

        return parent::prefersMarkdown($request);
    }
}
