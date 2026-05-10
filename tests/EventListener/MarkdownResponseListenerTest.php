<?php

namespace TomvdPeet\MarkdownNegotiationBundle\Tests\EventListener;

use League\HTMLToMarkdown\HtmlConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use TomvdPeet\MarkdownNegotiationBundle\EventListener\MarkdownResponseListener;
use TomvdPeet\MarkdownNegotiationBundle\Html\HtmlCleaner;
use TomvdPeet\MarkdownNegotiationBundle\Routing\MarkdownRouteMap;

class MarkdownResponseListenerTest extends TestCase
{
    public function testItDoesNothingForRoutesWithoutMarkdownOption(): void
    {
        $response = new Response('<h1>Hello</h1>', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        $listener = $this->createListener([]);

        $listener->onKernelResponse($this->createEvent('/test', 'plain_route', 'text/markdown', $response));

        $this->assertSame('<h1>Hello</h1>', $response->getContent());
        $this->assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function testItConvertsHtmlWhenMarkdownIsPreferred(): void
    {
        $response = new Response('<html><head><title>Ignored</title></head><body><main><h1>Hello</h1><p>World</p></main><footer>Ignored footer</footer></body></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        $listener = $this->createListener(['markdown_route' => true]);

        $listener->onKernelResponse($this->createEvent('/test', 'markdown_route', 'text/markdown, text/html;q=0.5', $response));

        $this->assertStringContainsString('Hello', (string) $response->getContent());
        $this->assertStringContainsString('World', (string) $response->getContent());
        $this->assertStringNotContainsString('<h1>', (string) $response->getContent());
        $this->assertStringNotContainsString('Ignored', (string) $response->getContent());
        $this->assertStringNotContainsString('<main>', (string) $response->getContent());
        $this->assertSame('text/markdown; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertContains('Accept', $response->getVary());
    }

    public function testItKeepsHtmlWhenHtmlIsPreferredByQuality(): void
    {
        $response = new Response('<h1>Hello</h1>', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        $listener = $this->createListener(['markdown_route' => true]);

        $listener->onKernelResponse($this->createEvent('/test', 'markdown_route', 'text/html, text/markdown;q=0.5', $response));

        $this->assertSame('<h1>Hello</h1>', $response->getContent());
        $this->assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function testItKeepsHtmlForWildcardAcceptHeader(): void
    {
        $response = new Response('<h1>Hello</h1>', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        $listener = $this->createListener(['markdown_route' => true]);

        $listener->onKernelResponse($this->createEvent('/test', 'markdown_route', '*/*', $response));

        $this->assertSame('<h1>Hello</h1>', $response->getContent());
        $this->assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function testItKeepsHtmlWhenWildcardBeatsMarkdownByQuality(): void
    {
        $response = new Response('<h1>Hello</h1>', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        $listener = $this->createListener(['markdown_route' => true]);

        $listener->onKernelResponse($this->createEvent('/test', 'markdown_route', 'text/markdown;q=0.5, */*;q=0.8', $response));

        $this->assertSame('<h1>Hello</h1>', $response->getContent());
        $this->assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function testItDoesNotConvertExistingMarkdownResponse(): void
    {
        $response = new Response('# Hello', 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
        $listener = $this->createListener(['markdown_route' => true]);

        $listener->onKernelResponse($this->createEvent('/test', 'markdown_route', 'text/markdown', $response));

        $this->assertSame('# Hello', $response->getContent());
        $this->assertSame('text/markdown; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertContains('Accept', $response->getVary());
    }

    public function testItOnlyConvertsTextHtmlResponses(): void
    {
        $response = new Response('{"hello":"world"}', 200, ['Content-Type' => 'application/json']);
        $listener = $this->createListener(['markdown_route' => true]);

        $listener->onKernelResponse($this->createEvent('/test', 'markdown_route', 'text/markdown', $response));

        $this->assertSame('{"hello":"world"}', $response->getContent());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
    }

    /**
     * @param array<string, true> $routes
     */
    private function createListener(array $routes): MarkdownResponseListener
    {
        return new MarkdownResponseListener(new MarkdownRouteMap(new EmptyRouter(), $this->writeCacheFile($routes)), new HtmlConverter(), new HtmlCleaner());
    }

    /**
     * @param array<string, true> $routes
     */
    private function writeCacheFile(array $routes): string
    {
        $file = sys_get_temp_dir().'/markdown_negotiation_routes_'.bin2hex(random_bytes(6)).'.php';
        file_put_contents($file, '<?php return '.var_export($routes, true).';');

        return $file;
    }

    private function createEvent(string $path, string $route, string $accept, Response $response): ResponseEvent
    {
        $request = Request::create($path, 'GET', [], [], [], ['HTTP_ACCEPT' => $accept]);
        $request->attributes->set('_route', $route);

        return new ResponseEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }
}

class EmptyRouter implements RouterInterface
{
    public function getRouteCollection(): RouteCollection
    {
        return new RouteCollection();
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
