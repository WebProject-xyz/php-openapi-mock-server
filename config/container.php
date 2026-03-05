<?php
declare(strict_types=1);

use Cschindl\OpenApiMockMiddleware\OpenApiMockMiddleware;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Application;
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
// @phpstan-ignore-next-line
$container->configure((new ConfigProvider())->getDependencies());
// @phpstan-ignore-next-line
$container->configure((new RouterConfigProvider())->getDependencies());
// @phpstan-ignore-next-line
$container->configure((new FastRouteConfigProvider())->getDependencies());
// @phpstan-ignore-next-line
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
$container->setFactory(ResponseInterface::class, static function () {
    return static function () {
        return (new ResponseFactory())->createResponse();
    };
});
$container->setService(ResponseFactoryInterface::class, new ResponseFactory());
$container->setService(StreamFactoryInterface::class, new StreamFactory());

// Application Middleware
$container->setFactory(OpenApiMockMiddleware::class, OpenApiMockMiddlewareFactory::class);
$container->setInvokableClass(ForceMockActiveMiddleware::class, ForceMockActiveMiddleware::class);

return $container;
