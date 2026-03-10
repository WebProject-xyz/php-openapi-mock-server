<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception;

use Throwable;

class RoutingException extends RequestException
{
    public static function forNoResourceProvided(?Throwable $throwable = null): self
    {
        $title  = 'Route not resolved, no resource provided';
        $detail = $throwable?->getMessage() ?? '';

        return new self(RequestErrorType::NO_RESOURCE_PROVIDED_ERROR, $title, $detail, 404);
    }

    public static function forNoPathMatched(?Throwable $throwable = null): self
    {
        $title  = 'Route not resolved, no path matched';
        $detail = $throwable?->getMessage() ?? '';

        return new self(RequestErrorType::NO_PATH_MATCHED_ERROR, $title, $detail, 404);
    }

    public static function forNoPathAndMethodMatched(?Throwable $throwable = null): self
    {
        $title  = 'Route resolved, but no path matched';
        $detail = $throwable?->getMessage() ?? '';

        return new self(RequestErrorType::NO_PATH_AND_METHOD_MATCHED_ERROR, $title, $detail, 404);
    }

    public static function forNoPathAndMethodAndResponseCodeMatched(?Throwable $throwable = null): self
    {
        $title  = 'Route resolved, but no path, method or response code matched';
        $detail = $throwable?->getMessage() ?? '';

        return new self(RequestErrorType::NO_PATH_AND_METHOD_AND_RESPONSE_CODE_MATCHED_ERROR, $title, $detail, 405);
    }
}
