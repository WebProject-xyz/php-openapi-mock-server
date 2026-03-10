<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Utils;

use function base64_encode;
use function explode;
use function getenv;
use function implode;
use function is_string;
use function sprintf;
use function stream_context_create;
use function trim;

class RemoteSpecificationLoader
{
    /**
     * @return resource
     */
    public static function createStreamContext()
    {
        $headers = [
            'User-Agent: PHP-OpenAPI-Mock-Server',
        ];

        $bearer = getenv('OPENAPI_SPEC_AUTH_BEARER');
        if (is_string($bearer) && $bearer !== '') {
            $headers[] = sprintf('Authorization: Bearer %s', $bearer);
        }

        $basic = getenv('OPENAPI_SPEC_AUTH_BASIC');
        if (is_string($basic) && $basic !== '') {
            $headers[] = sprintf('Authorization: Basic %s', base64_encode($basic));
        }

        $customHeaders = getenv('OPENAPI_SPEC_HEADERS');
        if (is_string($customHeaders) && $customHeaders !== '') {
            // Assume semicolon separated
            foreach (explode(';', $customHeaders) as $header) {
                if (trim($header) !== '') {
                    $headers[] = trim($header);
                }
            }
        }

        return stream_context_create([
            'http' => [
                'header' => implode("\r\n", $headers) . "\r\n",
            ],
        ]);
    }
}
