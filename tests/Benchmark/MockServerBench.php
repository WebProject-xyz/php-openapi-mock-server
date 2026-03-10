<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Benchmark;

use function assert;
use function dirname;
use function in_array;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use WebProject\PhpOpenApiMockServer\Factory\OpenApiMockMiddlewareFactory;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddleware;

#[Revs(10)]
#[Iterations(3)]
class MockServerBench
{
    private readonly ContainerInterface $container;

    private readonly OpenApiMockMiddleware $openApiMockMiddleware;

    private readonly ServerRequestInterface $serverRequest;

    private readonly RequestHandlerInterface $requestHandler;

    public function __construct()
    {
        $this->container                       = $this->createContainer(true);
        $openApiMockMiddlewareFactory          = new OpenApiMockMiddlewareFactory();
        $middleware                            = $openApiMockMiddlewareFactory($this->container);
        assert($middleware instanceof OpenApiMockMiddleware);
        $this->openApiMockMiddleware = $middleware;

        $this->serverRequest  = $this->createRequest();
        $this->requestHandler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new ResponseFactory())->createResponse();
            }
        };
    }

    #[Subject]
    public function benchMiddlewareCreation(): void
    {
        $openApiMockMiddlewareFactory = new OpenApiMockMiddlewareFactory();
        $openApiMockMiddlewareFactory($this->container);
    }

    #[Subject]
    public function benchRequestProcessing(): void
    {
        $this->openApiMockMiddleware->process($this->serverRequest, $this->requestHandler);
    }

    private function createContainer(bool $withCache): ContainerInterface
    {
        return new readonly class($withCache) implements ContainerInterface {
            public function __construct(private bool $withCache)
            {
            }

            public function get(string $id): mixed
            {
                if ('config' === $id) {
                    return [
                        'openapi_mock' => [
                            'spec'              => 'data/openapi.yaml',
                            'validate_request'  => true,
                            'validate_response' => true,
                        ],
                    ];
                }

                if (ResponseFactoryInterface::class === $id) {
                    return new ResponseFactory();
                }

                if (StreamFactoryInterface::class === $id) {
                    return new StreamFactory();
                }

                if (CacheItemPoolInterface::class === $id) {
                    return new FilesystemAdapter('openapi_mock', 0, dirname(__DIR__, 2) . '/tests/_output/cache_bench');
                }

                return null;
            }

            public function has(string $id): bool
            {
                if (CacheItemPoolInterface::class === $id) {
                    return $this->withCache;
                }

                return in_array($id, ['config', ResponseFactoryInterface::class, StreamFactoryInterface::class], true);
            }
        };
    }

    private function createRequest(): ServerRequestInterface
    {
        $uri = new class implements UriInterface {
            public function getScheme(): string
            {
                return 'http';
            }

            public function getAuthority(): string
            {
                return 'localhost';
            }

            public function getUserInfo(): string
            {
                return '';
            }

            public function getHost(): string
            {
                return 'localhost';
            }

            public function getPort(): int
            {
                return 8080;
            }

            public function getPath(): string
            {
                return '/users';
            }

            public function getQuery(): string
            {
                return '';
            }

            public function getFragment(): string
            {
                return '';
            }

            public function withScheme(string $scheme): UriInterface
            {
                return $this;
            }

            public function withUserInfo(string $user, ?string $password = null): UriInterface
            {
                return $this;
            }

            public function withHost(string $host): UriInterface
            {
                return $this;
            }

            public function withPort(?int $port): UriInterface
            {
                return $this;
            }

            public function withPath(string $path): UriInterface
            {
                return $this;
            }

            public function withQuery(string $query): UriInterface
            {
                return $this;
            }

            public function withFragment(string $fragment): UriInterface
            {
                return $this;
            }

            public function __toString(): string
            {
                return 'http://localhost:8080/users';
            }
        };

        return new readonly class($uri) implements ServerRequestInterface {
            public function __construct(private UriInterface $uri)
            {
            }

            public function getProtocolVersion(): string
            {
                return '1.1';
            }

            public function withProtocolVersion(string $version): ServerRequestInterface
            {
                return $this;
            }

            /** @return array<string, string[]> */
            public function getHeaders(): array
            {
                return ['X-OpenApi-Mock-Active' => ['true']];
            }

            public function hasHeader(string $name): bool
            {
                return 'X-OpenApi-Mock-Active' === $name;
            }

            /** @return string[] */
            public function getHeader(string $name): array
            {
                return 'X-OpenApi-Mock-Active' === $name ? ['true'] : [];
            }

            public function getHeaderLine(string $name): string
            {
                return 'X-OpenApi-Mock-Active' === $name ? 'true' : '';
            }

            public function withHeader(string $name, $value): ServerRequestInterface
            {
                return $this;
            }

            public function withAddedHeader(string $name, $value): ServerRequestInterface
            {
                return $this;
            }

            public function withoutHeader(string $name): ServerRequestInterface
            {
                return $this;
            }

            public function getBody(): StreamInterface
            {
                return (new StreamFactory())->createStream();
            }

            public function withBody(StreamInterface $body): ServerRequestInterface
            {
                return $this;
            }

            public function getRequestTarget(): string
            {
                return '/users';
            }

            public function withRequestTarget(string $requestTarget): ServerRequestInterface
            {
                return $this;
            }

            public function getMethod(): string
            {
                return 'GET';
            }

            public function withMethod(string $method): ServerRequestInterface
            {
                return $this;
            }

            public function getUri(): UriInterface
            {
                return $this->uri;
            }

            public function withUri(UriInterface $uri, bool $preserveHost = false): ServerRequestInterface
            {
                return $this;
            }

            /** @return array<string, mixed> */
            public function getServerParams(): array
            {
                return [];
            }

            /** @return array<string, mixed> */
            public function getCookieParams(): array
            {
                return [];
            }

            /** @param array<string, mixed> $cookies */
            public function withCookieParams(array $cookies): ServerRequestInterface
            {
                return $this;
            }

            /** @return array<string, mixed> */
            public function getQueryParams(): array
            {
                return [];
            }

            /** @param array<string, mixed> $query */
            public function withQueryParams(array $query): ServerRequestInterface
            {
                return $this;
            }

            /** @return array<int, UploadedFileInterface> */
            public function getUploadedFiles(): array
            {
                return [];
            }

            /** @param array<int, UploadedFileInterface> $uploadedFiles */
            public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
            {
                return $this;
            }

            /** @return array<string, mixed>|object|null */
            public function getParsedBody(): array|object|null
            {
                return null;
            }

            /** @param array<string, mixed>|object|null $data */
            public function withParsedBody($data): ServerRequestInterface
            {
                return $this;
            }

            /** @return array<string, mixed> */
            public function getAttributes(): array
            {
                return [];
            }

            public function getAttribute(string $name, $default = null): mixed
            {
                return null;
            }

            public function withAttribute(string $name, $value): ServerRequestInterface
            {
                return $this;
            }

            public function withoutAttribute(string $name): ServerRequestInterface
            {
                return $this;
            }
        };
    }
}
