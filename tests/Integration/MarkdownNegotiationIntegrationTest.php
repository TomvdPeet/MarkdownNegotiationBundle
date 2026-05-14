<?php

namespace TomvdPeet\MarkdownNegotiationBundle\Tests\Integration;

require_once __DIR__.'/Fixtures/DemoController.php';

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\RouteCollectionBuilder;
use TomvdPeet\MarkdownNegotiationBundle\MarkdownNegotiationBundle;

class MarkdownNegotiationIntegrationTest extends TestCase
{
    public function testItConvertsOptedInHtmlRouteThroughSymfonyKernel(): void
    {
        $response = $this->handle('/enabled', 'text/markdown, text/html;q=0.5');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/markdown; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Hello', (string) $response->getContent());
        $this->assertStringContainsString('Converted', (string) $response->getContent());
        $this->assertStringNotContainsString('<html>', (string) $response->getContent());
    }

    public function testItLeavesExistingMarkdownResponseUntouched(): void
    {
        $response = $this->handle('/markdown', 'text/markdown');

        $this->assertSame('# Already Markdown', $response->getContent());
        $this->assertSame('text/markdown; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function testItLeavesNonOptedInRouteUntouched(): void
    {
        $response = $this->handle('/disabled', 'text/markdown');

        $this->assertSame('<h1>Hello</h1>', $response->getContent());
        $this->assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function testDebugQueryParameterConvertsInDebugWithoutMarkdownAcceptHeader(): void
    {
        $response = $this->handle('/enabled', 'text/html', ['_markdown' => '1'], true);

        $this->assertSame('text/markdown; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('# Hello', (string) $response->getContent());
    }

    public function testDebugQueryParameterDoesNothingWhenDebugIsDisabled(): void
    {
        $response = $this->handle('/enabled', 'text/html', ['_markdown' => '1'], false);

        $this->assertSame('<html><body><h1>Hello</h1><p>Converted</p></body></html>', $response->getContent());
        $this->assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function testDebugQueryParameterIsConfigurable(): void
    {
        $response = $this->handle('/enabled', 'text/html', ['markdown_preview' => '1'], true, [
            'debug_query_parameter' => 'markdown_preview',
        ]);

        $this->assertSame('text/markdown; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('# Hello', (string) $response->getContent());
    }

    public function testDebugQueryParameterCanBeDisabled(): void
    {
        $response = $this->handle('/enabled', 'text/html', ['_markdown' => '1'], true, [
            'debug_query_parameter' => null,
        ]);

        $this->assertSame('<html><body><h1>Hello</h1><p>Converted</p></body></html>', $response->getContent());
        $this->assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    /**
     * @param array<string, string> $query
     * @param array<string, mixed>  $bundleConfig
     */
    private function handle(string $path, string $accept, array $query = [], bool $debug = false, array $bundleConfig = []): \Symfony\Component\HttpFoundation\Response
    {
        $kernel = new MarkdownNegotiationTestKernel('test', $debug, $bundleConfig);
        $request = Request::create($path, 'GET', $query, [], [], ['HTTP_ACCEPT' => $accept]);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        $kernel->shutdown();

        return $response;
    }
}

class MarkdownNegotiationTestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @param array<string, mixed> $bundleConfig
     */
    public function __construct(string $environment, bool $debug, private readonly array $bundleConfig = [])
    {
        parent::__construct($environment, $debug);
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new MarkdownNegotiationBundle(),
        ];
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/markdown_negotiation_bundle/cache/'.$this->environment.'/'.($this->debug ? 'debug' : 'prod').'/'.md5(serialize($this->bundleConfig));
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/markdown_negotiation_bundle/log/'.$this->environment;
    }

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface|ContainerBuilder $loader): void
    {
        $container->extension('framework', [
            'secret' => 'markdown-negotiation-test',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => [
                'log' => true,
            ],
            'secrets' => [
                'enabled' => false,
            ],
            'router' => [
                'utf8' => true,
            ],
        ]);

        if ([] !== $this->bundleConfig) {
            $container->extension('markdown_negotiation', $this->bundleConfig);
        }
    }

    protected function configureRoutes(RoutingConfigurator|RouteCollectionBuilder $routes): void
    {
        $routes->import(__DIR__.'/Fixtures/*Controller.php', 'attribute');
    }
}
