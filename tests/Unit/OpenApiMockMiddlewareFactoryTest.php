<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionClass;
use Webmozart\Assert\Assert;
use WebProject\PhpOpenApiMockServer\Factory\OpenApiMockMiddlewareFactory;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddleware;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddlewareConfig;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class OpenApiMockMiddlewareFactoryTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /** @var MockObject&ContainerInterface */
    private ?MockObject $container = null;

    /** @var MockObject&ResponseFactoryInterface */
    private ?MockObject $responseFactory = null;

    /** @var MockObject&StreamFactoryInterface */
    private ?MockObject $streamFactory = null;

    public function testInvokeUsesPsr17InterfacesFromContainer(): void
    {
        $this->container       = $this->createMock(ContainerInterface::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $this->streamFactory   = $this->createMock(StreamFactoryInterface::class);

        $this->container->method('has')->willReturnMap([
            ['config', false],
            [CacheItemPoolInterface::class, false],
        ]);
        $this->container->method('get')->willReturnMap([
            [ResponseFactoryInterface::class, $this->responseFactory],
            [StreamFactoryInterface::class, $this->streamFactory],
        ]);

        $openApiMockMiddlewareFactory    = new OpenApiMockMiddlewareFactory();
        $middleware                      = $openApiMockMiddlewareFactory($this->container);

        self::assertInstanceOf(MiddlewareInterface::class, $middleware);
        self::assertInstanceOf(OpenApiMockMiddleware::class, $middleware);
    }

    public function testInvokeRespectsConfigOverrides(): void
    {
        $this->container       = $this->createMock(ContainerInterface::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $this->streamFactory   = $this->createMock(StreamFactoryInterface::class);

        $customSpec = __DIR__ . '/../../data/openapi.json';

        $this->container->method('has')->willReturnMap([
            ['config', true],
            [CacheItemPoolInterface::class, false],
        ]);
        $this->container->method('get')->willReturnMap([
            ['config', [
                'openapi_mock' => [
                    'spec'              => $customSpec,
                    'validate_request'  => true,
                    'validate_response' => true,
                ],
            ]],
            [ResponseFactoryInterface::class, $this->responseFactory],
            [StreamFactoryInterface::class, $this->streamFactory],
        ]);

        $openApiMockMiddlewareFactory    = new OpenApiMockMiddlewareFactory();
        $middleware                      = $openApiMockMiddlewareFactory($this->container);

        self::assertInstanceOf(OpenApiMockMiddleware::class, $middleware);

        // Use reflection to check if config was applied correctly
        $reflectionClass    = new ReflectionClass($middleware);
        $reflectionProperty = $reflectionClass->getProperty('openApiMockMiddlewareConfig');

        $config = $reflectionProperty->getValue($middleware);
        Assert::isInstanceOf($config, OpenApiMockMiddlewareConfig::class);

        self::assertTrue($config->validateRequest());
        self::assertTrue($config->validateResponse());
    }
}
