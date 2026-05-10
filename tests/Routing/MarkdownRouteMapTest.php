<?php

namespace TomvdPeet\MarkdownNegotiationBundle\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use TomvdPeet\MarkdownNegotiationBundle\Routing\MarkdownRouteMap;

class MarkdownRouteMapTest extends TestCase
{
    public function testItBuildsMapFromMarkdownRouteOptions(): void
    {
        $collection = new RouteCollection();
        $collection->add('markdown_enabled', new Route('/enabled', options: ['markdown' => true]));
        $collection->add('markdown_disabled', new Route('/disabled', options: ['markdown' => false]));
        $collection->add('without_option', new Route('/without-option'));

        $map = new MarkdownRouteMap(new InMemoryRouter($collection), __DIR__.'/../var/missing.php');

        $this->assertTrue($map->has('markdown_enabled'));
        $this->assertFalse($map->has('markdown_disabled'));
        $this->assertFalse($map->has('without_option'));
    }
}

class InMemoryRouter implements RouterInterface
{
    public function __construct(private readonly RouteCollection $routes)
    {
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->routes;
    }

    public function setContext(RequestContext $context): void
    {
    }

    public function getContext(): RequestContext
    {
        return new RequestContext();
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        return '';
    }

    public function match(string $pathinfo): array
    {
        return [];
    }
}
