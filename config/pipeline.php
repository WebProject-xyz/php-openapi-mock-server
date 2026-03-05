<?php
declare(strict_types=1);

use Cschindl\OpenApiMockMiddleware\OpenApiMockMiddleware;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Mezzio\ProblemDetails\ProblemDetailsMiddleware;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use WebProject\PhpOpenApiMockServer\Middleware\ForceMockActiveMiddleware;

return static function (Application $app, MiddlewareFactory $factory): void {
    // 1. Problem Details Catch-All
    $app->pipe(ProblemDetailsMiddleware::class);

    // 2. Force mock header
    $app->pipe(ForceMockActiveMiddleware::class);

    // 3. Mock middleware (Factory handles initialization)
    $app->pipe(OpenApiMockMiddleware::class);

    // 4. Standard Mezzio stack for fallbacks/routing
    $app->pipe(RouteMiddleware::class);
    $app->pipe(DispatchMiddleware::class);
};
