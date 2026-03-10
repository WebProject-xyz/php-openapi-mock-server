<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\OpenApi;
use Codeception\Test\Unit;
use ReflectionClass;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Exception\NoPath;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Exception\NoRequest;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Exception\NoResponse;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Exception\NoSchema;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\OpenAPIFaker;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class OpenAPIFakerTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testCreateFromJson(): void
    {
        $json         = '{"openapi": "3.0.0", "info": {"title": "Test", "version": "1.0.0"}, "paths": {}}';
        $openAPIFaker = OpenAPIFaker::createFromJson($json);
        self::assertInstanceOf(OpenAPIFaker::class, $openAPIFaker);
    }

    public function testCreateFromYaml(): void
    {
        $yaml         = "openapi: 3.0.0\ninfo:\n  title: Test\n  version: 1.0.0\npaths: {}";
        $openAPIFaker = OpenAPIFaker::createFromYaml($yaml);
        self::assertInstanceOf(OpenAPIFaker::class, $openAPIFaker);
    }

    public function testMockResponseThrowsNoPath(): void
    {
        $openApi = new OpenApi([
            'openapi' => '3.0.0',
            'info'    => ['title' => 'Test', 'version' => '1.0'],
            'paths'   => [],
        ]);
        $openAPIFaker = OpenAPIFaker::createFromSchema($openApi);

        $this->expectException(NoPath::class);
        $openAPIFaker->mockResponse('/non-existent', 'get');
    }

    public function testMockResponseThrowsNoResponse(): void
    {
        $openApi = new OpenApi([
            'openapi' => '3.0.0',
            'info'    => ['title' => 'Test', 'version' => '1.0'],
            'paths'   => [
                '/test' => [
                    'get' => [
                        'responses' => [],
                    ],
                ],
            ],
        ]);
        $openAPIFaker = OpenAPIFaker::createFromSchema($openApi);

        $this->expectException(NoResponse::class);
        $openAPIFaker->mockResponse('/test', 'get', '200');
    }

    public function testMockRequestThrowsNoRequest(): void
    {
        $openApi = new OpenApi([
            'openapi' => '3.0.0',
            'info'    => ['title' => 'Test', 'version' => '1.0'],
            'paths'   => [
                '/test' => [
                    'post' => [
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                ],
            ],
        ]);
        $openAPIFaker = OpenAPIFaker::createFromSchema($openApi);

        $this->expectException(NoRequest::class);
        $openAPIFaker->mockRequest('/test', 'post');
    }

    public function testMockComponentSchemaThrowsNoSchema(): void
    {
        $openApi = new OpenApi([
            'openapi' => '3.0.0',
            'info'    => ['title' => 'Test', 'version' => '1.0'],
            'paths'   => [],
        ]);
        $openAPIFaker = OpenAPIFaker::createFromSchema($openApi);

        $this->expectException(NoSchema::class);
        $openAPIFaker->mockComponentSchema('NonExistent');
    }

    public function testMockResponseSuccess(): void
    {
        $openApi = new OpenApi([
            'openapi' => '3.0.0',
            'info'    => ['title' => 'Test', 'version' => '1.0'],
            'paths'   => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                                'content'     => [
                                    'application/json' => [
                                        'schema' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $openAPIFaker = OpenAPIFaker::createFromSchema($openApi);

        $result = $openAPIFaker->mockResponse('/test', 'get');
        self::assertIsString($result);
    }

    public function testMockRequestSuccess(): void
    {
        $openApi = new OpenApi([
            'openapi' => '3.0.0',
            'info'    => ['title' => 'Test', 'version' => '1.0'],
            'paths'   => [
                '/test' => [
                    'post' => [
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => ['type' => 'object'],
                                ],
                            ],
                        ],
                        'responses' => ['200' => ['description' => 'OK']],
                    ],
                ],
            ],
        ]);
        $openAPIFaker = OpenAPIFaker::createFromSchema($openApi);

        $result = $openAPIFaker->mockRequest('/test', 'post');
        self::assertIsArray($result);
    }

    public function testMockComponentSchemaSuccess(): void
    {
        $openApi = new OpenApi([
            'openapi'    => '3.0.0',
            'info'       => ['title' => 'Test', 'version' => '1.0'],
            'paths'      => [],
            'components' => [
                'schemas' => [
                    'User' => ['type' => 'string'],
                ],
            ],
        ]);
        $openAPIFaker = OpenAPIFaker::createFromSchema($openApi);

        $result = $openAPIFaker->mockComponentSchema('User');
        self::assertIsString($result);
    }

    public function testMockComponentSchemaForExampleSuccess(): void
    {
        $openApi = new OpenApi([
            'openapi'    => '3.0.0',
            'info'       => ['title' => 'Test', 'version' => '1.0'],
            'paths'      => [],
            'components' => [
                'schemas' => [
                    'User' => ['type' => 'string', 'example' => 'John Doe'],
                ],
            ],
        ]);
        $openAPIFaker = OpenAPIFaker::createFromSchema($openApi);

        $result = $openAPIFaker->mockComponentSchemaForExample('User');
        self::assertSame('John Doe', $result);
    }

    public function testSetOptions(): void
    {
        $openApi = new OpenApi([
            'openapi' => '3.0.0',
            'info'    => ['title' => 'Test', 'version' => '1.0'],
            'paths'   => [],
        ]);
        $openAPIFaker = OpenAPIFaker::createFromSchema($openApi);

        $openAPIFaker->setOptions([
            'minItems'            => 5,
            'maxItems'            => 10,
            'alwaysFakeOptionals' => true,
            'strategy'            => MockStrategy::STATIC,
            'nonExistent'         => 'value', // Should be ignored
        ]);

        $reflectionClass    = new ReflectionClass($openAPIFaker);
        $reflectionProperty = $reflectionClass->getProperty('options');

        $options = $reflectionProperty->getValue($openAPIFaker);

        self::assertSame(5, $options->getMinItems());
        self::assertSame(10, $options->getMaxItems());
        self::assertTrue($options->getAlwaysFakeOptionals());
        self::assertSame(MockStrategy::STATIC, $options->getStrategy());
    }
}
