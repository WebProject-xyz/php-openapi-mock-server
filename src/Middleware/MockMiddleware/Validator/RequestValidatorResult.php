<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator;

use cebe\openapi\spec\OpenApi;
use League\OpenAPIValidation\PSR7\OperationAddress;
use Throwable;

class RequestValidatorResult
{
    /**
     * @param array<string, mixed> $pathParameters
     */
    public function __construct(
        private readonly OpenApi $openApi,
        private readonly OperationAddress $operationAddress,
        private readonly ?Throwable $throwable = null,
        private readonly array $pathParameters = []
    ) {
    }

    public function getSchema(): OpenApi
    {
        return $this->openApi;
    }

    public function getOperationAddress(): OperationAddress
    {
        return $this->operationAddress;
    }

    public function getException(): ?Throwable
    {
        return $this->throwable;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPathParameters(): array
    {
        return $this->pathParameters;
    }

    public function isValid(): bool
    {
        return !$this->throwable instanceof Throwable;
    }

    public function equals(self $other): bool
    {
        $exceptionMatches = ($this->throwable === $other->throwable);
        if (!$exceptionMatches && $this->throwable instanceof Throwable && $other->throwable instanceof Throwable) {
            $exceptionMatches = ($this->throwable::class === $other->throwable::class)
                && ($this->throwable->getMessage() === $other->throwable->getMessage());
        }

        return $this->openApi                     === $other->openApi
            && $this->operationAddress->path()   === $other->operationAddress->path()
            && $this->operationAddress->method() === $other->operationAddress->method()
            && $exceptionMatches;
    }
}
