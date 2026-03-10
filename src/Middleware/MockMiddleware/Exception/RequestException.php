<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception;

use InvalidArgumentException;
use Throwable;

class RequestException extends InvalidArgumentException
{
    private readonly string $title;

    public function __construct(private readonly RequestErrorType|string $type, string $title, ?string $detail = null, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($detail ?? $title, $code, $previous);
        $this->title = $title;
    }

    public function getType(): string
    {
        return $this->type instanceof RequestErrorType ? $this->type->value : $this->type;
    }

    public function getErrorType(): ?RequestErrorType
    {
        return $this->type instanceof RequestErrorType ? $this->type : RequestErrorType::tryFrom($this->type);
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
