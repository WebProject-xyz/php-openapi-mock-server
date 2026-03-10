<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use Codeception\Test\Unit;
use Exception;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebProject\PhpOpenApiMockServer\Factory\OpenApiMockMiddlewareFactory;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddleware;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class FactoryExtraTest extends Unit
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

    /** @var MockObject&ProblemDetailsResponseFactory */
    private ?MockObject $problemFactory = null;

    public function testAnonymousErrorMiddleware(): void
    {
        $this->container       = $this->createMock(ContainerInterface::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $this->streamFactory   = $this->createMock(StreamFactoryInterface::class);
        $this->problemFactory  = $this->createMock(ProblemDetailsResponseFactory::class);

        $this->container->method('has')->willReturnMap([
            ['config', true],
            [CacheItemPoolInterface::class, false],
        ]);

        // Define common callback for all get() calls
        $getCallback = (fn ($id): MockObject|array => match ($id) {
            'config'                             => ['openapi_mock' => ['spec' => 'non-existent.yaml']],
            ResponseFactoryInterface::class      => $this->responseFactory,
            StreamFactoryInterface::class        => $this->streamFactory,
            ProblemDetailsResponseFactory::class => $this->problemFactory,
            default                              => throw new Exception('Unexpected container call for ' . $id),
        });

        $this->container->method('get')->willReturnCallback($getCallback);

        $openApiMockMiddlewareFactory = new OpenApiMockMiddlewareFactory();
        // This will trigger the catch block because spec is missing
        $middleware = $openApiMockMiddlewareFactory($this->container);

        self::assertNotInstanceOf(OpenApiMockMiddleware::class, $middleware);

        // Test the anonymous middleware's process method
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->problemFactory->expects(self::once())
            ->method('createResponse')
            ->willReturn($expectedResponse);

        $response = $middleware->process($request, $handler);
        self::assertSame($expectedResponse, $response);
    }
}
