<?php

namespace TomvdPeet\MarkdownNegotiationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('markdown_negotiation');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('debug_query_parameter')
                    ->defaultValue('_markdown')
                    ->treatFalseLike(null)
                    ->info('Query parameter that forces Markdown negotiation while kernel.debug is enabled. Set to null or false to disable.')
                    ->validate()
                        ->ifTrue(static fn (mixed $value): bool => null !== $value && (!\is_string($value) || '' === trim($value)))
                        ->thenInvalid('The debug_query_parameter option must be null, false, or a non-empty string.')
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
