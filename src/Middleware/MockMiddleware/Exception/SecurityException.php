<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception;

use Throwable;

class SecurityException extends RequestException
{
    public static function forUnauthorized(?Throwable $throwable = null): self
    {
        $title  = 'Invalid security scheme used';
        $detail = $throwable?->getMessage() ?? '';

        return new self(RequestErrorType::UNAUTHORIZED, $title, $detail, 401, $throwable);
    }
}
