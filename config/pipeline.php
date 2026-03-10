<?php
declare(strict_types=1);

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Mezzio\ProblemDetails\ProblemDetailsMiddleware;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use WebProject\PhpOpenApiMockServer\Middleware\ForceMockActiveMiddleware;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddleware;

return static function (Application $application, MiddlewareFactory $middlewareFactory): void {
    // 1. Problem Details Catch-All
    $application->pipe(ProblemDetailsMiddleware::class);

    // 2. Force mock header
    $application->pipe(ForceMockActiveMiddleware::class);

    // 3. Mock middleware (Factory handles initialization)
    $application->pipe(OpenApiMockMiddleware::class);

    // 4. Standard Mezzio stack for fallbacks/routing
    $application->pipe(RouteMiddleware::class);
    $application->pipe(DispatchMiddleware::class);
};
