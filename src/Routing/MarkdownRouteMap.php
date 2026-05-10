<?php

namespace TomvdPeet\MarkdownNegotiationBundle\Routing;

use Symfony\Component\Routing\RouterInterface;
use TomvdPeet\MarkdownNegotiationBundle\MarkdownNegotiationBundle;

class MarkdownRouteMap
{
    /**
     * @var array<string, true>|null
     */
    private ?array $routes = null;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly string $cacheFile,
    ) {
    }

    public function has(string $route): bool
    {
        return isset($this->getRoutes()[$route]);
    }

    /**
     * @return array<string, true>
     */
    public function getRoutes(): array
    {
        if (null !== $this->routes) {
            return $this->routes;
        }

        if (is_file($this->cacheFile)) {
            $routes = require $this->cacheFile;

            if (\is_array($routes)) {
                return $this->routes = $routes;
            }
        }

        return $this->routes = $this->buildRoutes();
    }

    /**
     * @return array<string, true>
     */
    public function buildRoutes(): array
    {
        $routes = [];

        foreach ($this->router->getRouteCollection() as $name => $route) {
            if (true === $route->getOption(MarkdownNegotiationBundle::ROUTE_OPTION)) {
                $routes[$name] = true;
            }
        }

        return $routes;
    }
}
