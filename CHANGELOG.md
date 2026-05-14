# Changelog

All notable changes to this package will be documented in this file.

## 1.1.0 - 2026-05-14

- Add a debug-only query parameter for forcing Markdown negotiation from a browser.
- Configure the debug query parameter with `markdown_negotiation.debug_query_parameter`.

## 1.0.0 - 2026-05-11

- Add route-option based Markdown response negotiation.
- Convert successful `text/html` responses to `text/markdown` when Markdown is preferred by the `Accept` header.
- Skip routes without `options: ['markdown' => true]` through a warmed route map.
- Skip responses that already use `text/markdown`.
- Clean decorated HTML before Markdown conversion.
- Add `MarkdownResponse` for manually returning Markdown responses.
- Add `MarkdownNegotiator` for custom controller negotiation logic.
