<?php
declare(strict_types=1);

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\ConfigProvider;
use Mezzio\ProblemDetails\ConfigProvider as ProblemDetailsConfigProvider;
use Mezzio\Router\ConfigProvider as RouterConfigProvider;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\FastRouteRouter\ConfigProvider as FastRouteConfigProvider;
use Mezzio\Router\RouterInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use WebProject\PhpOpenApiMockServer\Factory\OpenApiMockMiddlewareFactory;
use WebProject\PhpOpenApiMockServer\Middleware\ForceMockActiveMiddleware;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddleware;

$container = new ServiceManager();

// Register required Mezzio services
$container->configure((new ConfigProvider())->getDependencies());
$container->configure((new RouterConfigProvider())->getDependencies());
$container->configure((new FastRouteConfigProvider())->getDependencies());
$container->configure((new ProblemDetailsConfigProvider())->getDependencies());

// Configuration
$container->setService('config', [
    'problem-details' => [
        'include_stack_trace' => true,
    ],
]);

// Standard Mezzio Aliases
$container->setAlias(RouterInterface::class, FastRouteRouter::class);

// Factories
$container->setFactory(ResponseInterface::class, static fn (): Closure => static fn (): ResponseInterface => (new ResponseFactory())->createResponse());
$container->setService(ResponseFactoryInterface::class, new ResponseFactory());
$container->setService(StreamFactoryInterface::class, new StreamFactory());

// Cache Configuration — isolated per process to avoid conflicts between parallel server instances
$processCacheDir = sys_get_temp_dir() . '/openapi_mock_cache/' . getmypid();
$container->setFactory(CacheItemPoolInterface::class, static fn (): FilesystemAdapter => new FilesystemAdapter('openapi_mock', 0, $processCacheDir));

register_shutdown_function(static function () use ($processCacheDir): void {
    if (!is_dir($processCacheDir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($processCacheDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $path) {
        $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
    }

    rmdir($processCacheDir);
});

// Application Middleware
$container->setFactory(OpenApiMockMiddleware::class, OpenApiMockMiddlewareFactory::class);
$container->setInvokableClass(ForceMockActiveMiddleware::class, ForceMockActiveMiddleware::class);

return $container;
