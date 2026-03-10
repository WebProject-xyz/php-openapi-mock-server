<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use Codeception\Test\Unit;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddleware;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddlewareBuilder;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddlewareConfig;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class OpenApiMockMiddlewareBuilderTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testCreateFromYaml(): void
    {
        $yaml = <<<YAML
            openapi: 3.0.0
            info:
              title: Test
              version: 1.0.0
            paths: {}
            YAML;
        $openApiMockMiddlewareConfig          = new OpenApiMockMiddlewareConfig();
        $responseFactory                      = $this->createMock(ResponseFactoryInterface::class);
        $streamFactory                        = $this->createMock(StreamFactoryInterface::class);

        $openApiMockMiddleware = OpenApiMockMiddlewareBuilder::createFromYaml($yaml, $openApiMockMiddlewareConfig, $responseFactory, $streamFactory);

        self::assertInstanceOf(OpenApiMockMiddleware::class, $openApiMockMiddleware);
    }

    public function testCreateFromJson(): void
    {
        $json                                 = '{"openapi": "3.0.0", "info": {"title": "Test", "version": "1.0.0"}, "paths": {}}';
        $openApiMockMiddlewareConfig          = new OpenApiMockMiddlewareConfig();
        $responseFactory                      = $this->createMock(ResponseFactoryInterface::class);
        $streamFactory                        = $this->createMock(StreamFactoryInterface::class);

        $openApiMockMiddleware = OpenApiMockMiddlewareBuilder::createFromJson($json, $openApiMockMiddlewareConfig, $responseFactory, $streamFactory);

        self::assertInstanceOf(OpenApiMockMiddleware::class, $openApiMockMiddleware);
    }

    public function testCreateFromYamlFile(): void
    {
        $file                                 = __DIR__ . '/../../data/openapi.yaml';
        $openApiMockMiddlewareConfig          = new OpenApiMockMiddlewareConfig();
        $responseFactory                      = $this->createMock(ResponseFactoryInterface::class);
        $streamFactory                        = $this->createMock(StreamFactoryInterface::class);

        $openApiMockMiddleware = OpenApiMockMiddlewareBuilder::createFromYamlFile($file, $openApiMockMiddlewareConfig, $responseFactory, $streamFactory);

        self::assertInstanceOf(OpenApiMockMiddleware::class, $openApiMockMiddleware);
    }

    public function testCreateFromJsonFile(): void
    {
        $file                                 = __DIR__ . '/../../data/openapi.json';
        $openApiMockMiddlewareConfig          = new OpenApiMockMiddlewareConfig();
        $responseFactory                      = $this->createMock(ResponseFactoryInterface::class);
        $streamFactory                        = $this->createMock(StreamFactoryInterface::class);

        $openApiMockMiddleware = OpenApiMockMiddlewareBuilder::createFromJsonFile($file, $openApiMockMiddlewareConfig, $responseFactory, $streamFactory);

        self::assertInstanceOf(OpenApiMockMiddleware::class, $openApiMockMiddleware);
    }
}
