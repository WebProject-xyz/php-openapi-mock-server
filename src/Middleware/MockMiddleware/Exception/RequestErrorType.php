<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception;

enum RequestErrorType: string
{
    case NO_RESOURCE_PROVIDED_ERROR                         = 'NO_RESOURCE_PROVIDED_ERROR';
    case NO_PATH_MATCHED_ERROR                              = 'NO_PATH_MATCHED_ERROR';
    case NO_PATH_AND_METHOD_MATCHED_ERROR                   = 'NO_PATH_AND_METHOD_MATCHED_ERROR';
    case NO_PATH_AND_METHOD_AND_RESPONSE_CODE_MATCHED_ERROR = 'NO_PATH_AND_METHOD_AND_RESPONSE_CODE_MATCHED_ERROR';
    case UNAUTHORIZED                                       = 'UNAUTHORIZED';
    case UNPROCESSABLE_ENTITY                               = 'UNPROCESSABLE_ENTITY';
    case NOT_ACCEPTABLE                                     = 'NOT_ACCEPTABLE';
    case NOT_FOUND                                          = 'NOT_FOUND';
    case VIOLATIONS                                         = 'VIOLATIONS';
    case UNEXPECTED_ERROR_OCCURRED                          = 'UNEXPECTED_ERROR_OCCURRED';
    case CONFIG_ERROR                                       = 'CONFIG_ERROR';
}
