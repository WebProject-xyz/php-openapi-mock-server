<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Unit;

use Codeception\Test\Unit;
use function putenv;
use function stream_context_get_options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Utils\RemoteSpecificationLoader;

class RemoteSpecificationLoaderTest extends Unit
{
    protected function _after(): void
    {
        putenv('OPENAPI_SPEC_AUTH_BEARER');
        putenv('OPENAPI_SPEC_AUTH_BASIC');
        putenv('OPENAPI_SPEC_HEADERS');
    }

    public function testDefaultHeaders(): void
    {
        $context = RemoteSpecificationLoader::createStreamContext();
        $options = stream_context_get_options($context);

        self::assertArrayHasKey('http', $options);
        self::assertStringContainsString('User-Agent: PHP-OpenAPI-Mock-Server', $options['http']['header']);
    }

    public function testBearerToken(): void
    {
        putenv('OPENAPI_SPEC_AUTH_BEARER=my-token');
        $context = RemoteSpecificationLoader::createStreamContext();
        $options = stream_context_get_options($context);

        self::assertStringContainsString('Authorization: Bearer my-token', $options['http']['header']);
    }

    public function testBasicAuth(): void
    {
        putenv('OPENAPI_SPEC_AUTH_BASIC=user:pass');
        $context = RemoteSpecificationLoader::createStreamContext();
        $options = stream_context_get_options($context);

        // base64_encode('user:pass') === 'dXNlcjpwYXNz'
        self::assertStringContainsString('Authorization: Basic dXNlcjpwYXNz', $options['http']['header']);
    }

    public function testCustomHeaders(): void
    {
        putenv('OPENAPI_SPEC_HEADERS=X-Custom: Value; X-Another: One');
        $context = RemoteSpecificationLoader::createStreamContext();
        $options = stream_context_get_options($context);

        self::assertStringContainsString('X-Custom: Value', $options['http']['header']);
        self::assertStringContainsString('X-Another: One', $options['http']['header']);
    }

    public function testCombinedHeaders(): void
    {
        putenv('OPENAPI_SPEC_AUTH_BEARER=my-token');
        putenv('OPENAPI_SPEC_HEADERS=X-Custom: Value');
        $context = RemoteSpecificationLoader::createStreamContext();
        $options = stream_context_get_options($context);

        self::assertStringContainsString('Authorization: Bearer my-token', $options['http']['header']);
        self::assertStringContainsString('X-Custom: Value', $options['http']['header']);
    }

    public function testInvalidHeadersFormat(): void
    {
        putenv('OPENAPI_SPEC_HEADERS=InvalidHeaderFormat; ; ;;');
        $context = RemoteSpecificationLoader::createStreamContext();
        $options = stream_context_get_options($context);

        self::assertStringContainsString('User-Agent: PHP-OpenAPI-Mock-Server', $options['http']['header']);
        // Should not contain empty lines or malformed parts in a way that breaks things
        self::assertStringNotContainsString(';;', $options['http']['header']);
    }
}
