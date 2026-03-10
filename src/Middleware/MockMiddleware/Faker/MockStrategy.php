<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker;

enum MockStrategy: string
{
    case STATIC  = 'static';
    case DYNAMIC = 'dynamic';
}
