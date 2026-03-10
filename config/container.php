<?php
declare(strict_types=1);

use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddleware;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\ConfigProvider;
use Mezzio\ProblemDetails\ConfigProvider as ProblemDetailsConfigProvider;
use Mezzio\Router\ConfigProvider as RouterConfigProvider;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\FastRouteRouter\ConfigProvider as FastRouteConfigProvider;
use Mezzio\Router\RouterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use WebProject\PhpOpenApiMockServer\Factory\OpenApiMockMiddlewareFactory;
use WebProject\PhpOpenApiMockServer\Middleware\ForceMockActiveMiddleware;

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
$container->setFactory(ResponseInterface::class, static fn(): Closure => static fn(): ResponseInterface => (new ResponseFactory())->createResponse());
$container->setService(ResponseFactoryInterface::class, new ResponseFactory());
$container->setService(StreamFactoryInterface::class, new StreamFactory());

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Psr\Cache\CacheItemPoolInterface;

// ...

// Cache Configuration
$container->setFactory(CacheItemPoolInterface::class, static fn(): FilesystemAdapter => new FilesystemAdapter('openapi_mock', 0, dirname(__DIR__) . '/data/cache'));

// Application Middleware
$container->setFactory(OpenApiMockMiddleware::class, OpenApiMockMiddlewareFactory::class);
$container->setInvokableClass(ForceMockActiveMiddleware::class, ForceMockActiveMiddleware::class);

return $container;
