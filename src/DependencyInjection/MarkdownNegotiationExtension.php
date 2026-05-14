<?php

namespace TomvdPeet\MarkdownNegotiationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use TomvdPeet\MarkdownNegotiationBundle\Http\DebugMarkdownNegotiator;
use TomvdPeet\MarkdownNegotiationBundle\Http\MarkdownNegotiator;

class MarkdownNegotiationExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        if (!$container->getParameter('kernel.debug') || null === $config['debug_query_parameter']) {
            return;
        }

        $container->getDefinition(MarkdownNegotiator::class)
            ->setClass(DebugMarkdownNegotiator::class)
            ->setArgument('$debugQueryParameter', $config['debug_query_parameter'])
        ;
    }
}
