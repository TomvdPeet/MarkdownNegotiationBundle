<?php

namespace TomvdPeet\MarkdownNegotiationBundle\Html;

use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\HtmlConverter;
use League\HTMLToMarkdown\HtmlConverterInterface;

class HtmlConverterFactory
{
    public function create(TableConverter $tableConverter): HtmlConverterInterface
    {
        $converter = new HtmlConverter([
            'header_style' => 'atx',
        ]);

        $converter->getEnvironment()->addConverter($tableConverter);

        return $converter;
    }
}
