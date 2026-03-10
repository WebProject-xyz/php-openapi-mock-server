<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Factory;

use const DIRECTORY_SEPARATOR;
use function dirname;
use function file_get_contents;
use function getcwd;
use function getenv;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use Throwable;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddlewareBuilder;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddlewareConfig;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Utils\RemoteSpecificationLoader;

class OpenApiMockMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): MiddlewareInterface
    {
        $packageRoot = dirname(__DIR__, 2);
        /** @var array{openapi_mock?: array{spec?: string, validate_request?: bool, validate_response?: bool}} $config */
        $config      = $container->has('config') ? (array) $container->get('config') : [];
        $mockConfig  = (array) ($config['openapi_mock'] ?? []);
        $specPath    = $mockConfig['spec'] ?? getenv('OPENAPI_SPEC') ?: null;

        if (null === $specPath) {
            // No spec configured — use default from the package itself
            $specPath = $packageRoot . '/data/openapi.yaml';
        } elseif (!str_starts_with((string) $specPath, '/') && !str_starts_with((string) $specPath, 'http')) {
            // Relative paths: resolve from cwd when installed as dependency, package root otherwise
            $resolveBase = str_contains($packageRoot, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)
                ? (getcwd() ?: '.')
                : $packageRoot;
            $specPath = $resolveBase . DIRECTORY_SEPARATOR . $specPath;
        }

        $openApiMockMiddlewareConfig = new OpenApiMockMiddlewareConfig(
            (bool) ($mockConfig['validate_request'] ?? false),
            (bool) ($mockConfig['validate_response'] ?? false),
        );

        $responseFactory = $container->get(ResponseFactoryInterface::class);
        $streamFactory   = $container->get(StreamFactoryInterface::class);
        /** @var CacheItemPoolInterface|null $cache */
        $cache           = $container->has(CacheItemPoolInterface::class) ? $container->get(CacheItemPoolInterface::class) : null;

        try {
            if (str_starts_with((string) $specPath, 'http')) {
                $context = RemoteSpecificationLoader::createStreamContext();
                $content = file_get_contents($specPath, false, $context);
                if (false === $content) {
                    throw new RuntimeException(sprintf('Failed to fetch remote spec from "%s"', $specPath));
                }

                if (str_ends_with($specPath, '.json')) {
                    return OpenApiMockMiddlewareBuilder::createFromJson(
                        $content,
                        $openApiMockMiddlewareConfig,
                        $responseFactory,
                        $streamFactory,
                        $cache
                    );
                }

                return OpenApiMockMiddlewareBuilder::createFromYaml(
                    $content,
                    $openApiMockMiddlewareConfig,
                    $responseFactory,
                    $streamFactory,
                    $cache
                );
            }

            if (str_ends_with((string) $specPath, '.json')) {
                return OpenApiMockMiddlewareBuilder::createFromJsonFile(
                    $specPath,
                    $openApiMockMiddlewareConfig,
                    $responseFactory,
                    $streamFactory,
                    $cache
                );
            }

            return OpenApiMockMiddlewareBuilder::createFromYamlFile(
                $specPath,
                $openApiMockMiddlewareConfig,
                $responseFactory,
                $streamFactory,
                $cache
            );
        } catch (Throwable $throwable) {
            // Return an anonymous middleware that reports the error
            return new readonly class($throwable, $specPath, $container) implements MiddlewareInterface {
                public function __construct(
                    private Throwable $throwable,
                    private string $specPath,
                    private ContainerInterface $container
                ) {
                }

                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    /** @var ProblemDetailsResponseFactory $problemDetailsResponseFactory */
                    $problemDetailsResponseFactory = $this->container->get(ProblemDetailsResponseFactory::class);

                    return $problemDetailsResponseFactory->createResponse(
                        $request,
                        500,
                        $this->throwable->getMessage(),
                        'Failed to load OpenAPI specification',
                        'CONFIG_ERROR',
                        [
                            'spec_path'   => $this->specPath,
                            'stack_trace' => $this->throwable->getTraceAsString(),
                        ]
                    );
                }
            };
        }
    }
}
