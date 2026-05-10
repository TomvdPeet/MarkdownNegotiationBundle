<?php

namespace TomvdPeet\MarkdownNegotiationBundle\Routing;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class MarkdownRouteMapWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly MarkdownRouteMap $markdownRouteMap,
        private readonly string $cacheFile,
    ) {
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $directory = \dirname($this->cacheFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($this->cacheFile, '<?php return '.var_export($this->markdownRouteMap->buildRoutes(), true).';'.PHP_EOL);

        return [$this->cacheFile];
    }

    public function isOptional(): bool
    {
        return true;
    }
}
