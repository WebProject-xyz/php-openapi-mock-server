<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddleware;

class ForceMockActiveMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$request->hasHeader(OpenApiMockMiddleware::HEADER_OPENAPI_MOCK_ACTIVE)) {
            $request = $request->withHeader(OpenApiMockMiddleware::HEADER_OPENAPI_MOCK_ACTIVE, 'true');
        }

        return $handler->handle($request);
    }
}
