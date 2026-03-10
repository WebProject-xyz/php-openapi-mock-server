<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Utils;

enum HttpMethod: string
{
    case GET     = 'get';
    case POST    = 'post';
    case PUT     = 'put';
    case PATCH   = 'patch';
    case DELETE  = 'delete';
    case HEAD    = 'head';
    case OPTIONS = 'options';
    case TRACE   = 'trace';

    public static function fromString(string $method): self
    {
        return self::from(strtolower($method));
    }
}
