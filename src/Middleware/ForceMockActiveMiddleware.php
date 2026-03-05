<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ForceMockActiveMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$request->hasHeader('X-OpenApi-Mock-Active')) {
            $request = $request->withHeader('X-OpenApi-Mock-Active', 'true');
        }

        return $handler->handle($request);
    }
}
