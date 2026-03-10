<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception;

use function implode;
use Throwable;

class ValidationException extends RequestException
{
    public static function forUnprocessableEntity(?Throwable $throwable = null): self
    {
        $title = 'Invalid request';

        $detail = [];

        if ($throwable instanceof Throwable) {
            $detail[] = $throwable->getMessage();

            if ($throwable->getPrevious() instanceof Throwable) {
                $detail[] = $throwable->getPrevious()->getMessage();
            }
        }

        return new self(RequestErrorType::UNPROCESSABLE_ENTITY, $title, implode('\n', $detail), 422, $throwable);
    }

    public static function forNotAcceptable(?Throwable $throwable = null): self
    {
        $title  = 'The server cannot produce a representation for your accept header';
        $detail = $throwable?->getMessage() ?? '';

        return new self(RequestErrorType::NOT_ACCEPTABLE, $title, $detail, 406, $throwable);
    }

    public static function forNotFound(?Throwable $throwable = null): self
    {
        $title  = 'The server cannot find the requested content';
        $detail = $throwable?->getMessage() ?? '';

        return new self(RequestErrorType::NOT_FOUND, $title, $detail, 404, $throwable);
    }

    public static function forViolations(?Throwable $throwable = null): self
    {
        $title  = 'Request/Response not valid';
        $detail = $throwable?->getMessage() ?? '';

        return new self(RequestErrorType::VIOLATIONS, $title, $detail, 500, $throwable);
    }
}
