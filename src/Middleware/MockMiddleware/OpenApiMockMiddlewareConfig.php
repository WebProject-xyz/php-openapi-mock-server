<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware;

use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;

class OpenApiMockMiddlewareConfig
{
    public function __construct(
        private readonly bool $validateRequest = false,
        private readonly bool $validateResponse = false,
        private readonly ?Options $options = null,
    ) {
    }

    public function validateRequest(): bool
    {
        return $this->validateRequest;
    }

    public function validateResponse(): bool
    {
        return $this->validateResponse;
    }

    public function getOptions(): Options
    {
        return $this->options ?? new Options();
    }
}
