<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

/** @internal */
enum FakerContext: string
{
    case REQUEST  = 'request';
    case RESPONSE = 'response';

    public function isRequest(): bool
    {
        return $this === self::REQUEST;
    }
}
