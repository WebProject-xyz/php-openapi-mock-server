<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator;

use Throwable;

class ResponseValidatorResult
{
    public function __construct(
        private readonly ?Throwable $throwable = null
    ) {
    }

    public function getException(): ?Throwable
    {
        return $this->throwable;
    }

    public function isValid(): bool
    {
        return !$this->throwable instanceof Throwable;
    }

    public function equals(self $other): bool
    {
        $exceptionMatches = ($this->throwable === $other->throwable);
        if (!$exceptionMatches && $this->throwable instanceof Throwable && $other->throwable instanceof Throwable) {
            return ($this->throwable::class === $other->throwable::class)
                && ($this->throwable->getMessage() === $other->throwable->getMessage());
        }

        return $exceptionMatches;
    }
}
