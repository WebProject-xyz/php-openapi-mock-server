<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception;

use Throwable;

class UnknownException extends RequestException
{
    public static function forUnexpectedErrorOccurred(?Throwable $throwable = null): self
    {
        $title  = 'Unexpected error occurred';
        $detail = $throwable?->getMessage() ?? '';

        return new self(RequestErrorType::UNEXPECTED_ERROR_OCCURRED, $title, $detail, 500, $throwable);
    }
}
