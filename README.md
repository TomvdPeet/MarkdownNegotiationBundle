# MarkdownNegotiationBundle

Symfony bundle that negotiates Markdown responses for opted-in routes.

## Usage

Enable Markdown negotiation per route with the `markdown` route option:

```php
use Symfony\Component\Routing\Attribute\Route;

#[Route('/docs', name: 'docs', options: ['markdown' => true])]
public function docs(): Response
{
    return $this->render('docs/index.html.twig');
}
```

Requests that prefer `text/markdown` over `text/html` receive a converted Markdown response:

```http
Accept: text/markdown, text/html;q=0.5
```

The bundle only converts successful `text/html` responses. Routes without the option return immediately through an optimized route map, and responses that already use `text/markdown` are left untouched.
