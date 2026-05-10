<?php

namespace TomvdPeet\MarkdownNegotiationBundle\Tests\Integration\Fixtures;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DemoController
{
    #[Route('/enabled', name: 'enabled', options: ['markdown' => true])]
    public function enabled(): Response
    {
        return new Response('<html><body><h1>Hello</h1><p>Converted</p></body></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    #[Route('/markdown', name: 'markdown', options: ['markdown' => true])]
    public function markdown(): Response
    {
        return new Response('# Already Markdown', 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    #[Route('/disabled', name: 'disabled')]
    public function disabled(): Response
    {
        return new Response('<h1>Hello</h1>', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
