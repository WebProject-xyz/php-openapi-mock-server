<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use Codeception\Test\Unit;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddlewareConfig;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class OpenApiMockMiddlewareConfigTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testConfigReturnsCorrectValues(): void
    {
        $options                      = new Options();
        $openApiMockMiddlewareConfig  = new OpenApiMockMiddlewareConfig(true, true, $options);

        self::assertTrue($openApiMockMiddlewareConfig->validateRequest());
        self::assertTrue($openApiMockMiddlewareConfig->validateResponse());
        self::assertSame($options, $openApiMockMiddlewareConfig->getOptions());
    }

    public function testConfigHandlesDefaults(): void
    {
        $openApiMockMiddlewareConfig = new OpenApiMockMiddlewareConfig();

        self::assertFalse($openApiMockMiddlewareConfig->validateRequest());
        self::assertFalse($openApiMockMiddlewareConfig->validateResponse());
        self::assertInstanceOf(Options::class, $openApiMockMiddlewareConfig->getOptions());
    }
}
