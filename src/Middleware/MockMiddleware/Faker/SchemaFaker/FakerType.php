<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

/** @internal */
enum FakerType: string
{
    case STRING   = 'string';
    case NUMBER   = 'number';
    case INTEGER  = 'integer';
    case BOOLEAN  = 'boolean';
    case ARRAY    = 'array';
    case OBJECT   = 'object';
    case SCHEMA   = 'schema';
    case REQUEST  = 'request';
    case RESPONSE = 'response';
    case UNKNOWN  = 'unknown';
}
