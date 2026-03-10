<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Factory;

use Psr\Cache\CacheItemPoolInterface;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddlewareBuilder;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddlewareConfig;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Utils\RemoteSpecificationLoader;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Throwable;

use function dirname;
use function file_get_contents;
use function getenv;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function stream_context_create;

use const DIRECTORY_SEPARATOR;

class OpenApiMockMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): MiddlewareInterface
    {
        $projectRoot = dirname(__DIR__, 2);
        /** @var array{openapi_mock?: array{spec?: string, validate_request?: bool, validate_response?: bool}} $config */
        $config      = $container->has('config') ? (array) $container->get('config') : [];
        $mockConfig  = (array) ($config['openapi_mock'] ?? []);
        $specPath    = $mockConfig['spec'] ?? getenv('OPENAPI_SPEC') ?: $projectRoot . '/data/openapi.yaml';

        // Ensure absolute path if relative and not a URL
        if (! str_starts_with((string) $specPath, '/') && ! str_starts_with((string) $specPath, 'http')) {
            $specPath = $projectRoot . DIRECTORY_SEPARATOR . $specPath;
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
                if ($content === false) {
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
            return new readonly class ($throwable, $specPath, $container) implements MiddlewareInterface {
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
