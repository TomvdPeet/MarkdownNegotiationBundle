# MarkdownNegotiationBundle

Symfony bundle that negotiates Markdown responses for opted-in routes.

## Installation

```bash
composer require tomvdpeet/markdown-negotiation-bundle
```

Symfony Flex enables the bundle automatically. If you do not use Flex, register it manually in `config/bundles.php`:

```php
TomvdPeet\MarkdownNegotiationBundle\MarkdownNegotiationBundle::class => ['all' => true],
```

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

HTML remains the response format when it has a higher or equal quality:

```http
Accept: text/html, text/markdown;q=0.5
```

Wildcard-only requests also keep HTML:

```http
Accept: */*
```

## Debug Query Parameter

When `kernel.debug` is enabled, you can force Markdown negotiation from a browser by adding `_markdown` to the URL:

```text
/docs?_markdown
```

This behaves like the request preferred `text/markdown`, but only in debug kernels. Production kernels keep the normal `Accept` header behavior and do not check the query string.

The query parameter name is configurable:

```yaml
markdown_negotiation:
    debug_query_parameter: markdown_preview
```

Set it to `null` or `false` to disable the debug shortcut.

## Behavior

The bundle only converts responses when all of these conditions match:

- the current route has `options: ['markdown' => true]`
- the request is the main request
- `text/markdown` has a strictly higher effective `Accept` quality than `text/html`
- the response is successful
- the response content type is `text/html`
- the response is not streamed or binary

Routes without the option return immediately through an optimized route map. Responses that already use `text/markdown` are left untouched.

Before conversion, the HTML is cleaned to remove common layout/decorative nodes such as `head`, `nav`, `script`, `style`, `footer`, and `aside`. Markdown-relevant tags such as headings, paragraphs, links, images, code, lists, tables, and blockquotes are preserved for conversion.

Links and images keep their `href` and `src` values. Absolute same-host URLs are normalized to relative URLs before conversion.

## Manual Markdown Responses

Use `MarkdownResponse` when a controller already has Markdown and should return it directly:

```php
use TomvdPeet\MarkdownNegotiationBundle\Http\MarkdownResponse;

return new MarkdownResponse("# Hello\n\nManual Markdown.");
```

This sets `Content-Type: text/markdown; charset=UTF-8` and does not add `Vary: Accept`.

If the response was selected through content negotiation, use the named constructor:

```php
return MarkdownResponse::negotiated("# Hello");
```

That also adds `Vary: Accept`.

For custom controller logic, inject `MarkdownNegotiator`:

```php
use Symfony\Component\HttpFoundation\Request;
use TomvdPeet\MarkdownNegotiationBundle\Http\MarkdownNegotiator;
use TomvdPeet\MarkdownNegotiationBundle\Http\MarkdownResponse;

public function docs(Request $request, MarkdownNegotiator $negotiator): Response
{
    if ($negotiator->prefersMarkdown($request)) {
        return MarkdownResponse::negotiated("# Docs");
    }

    return $this->render('docs/show.html.twig');
}
```
