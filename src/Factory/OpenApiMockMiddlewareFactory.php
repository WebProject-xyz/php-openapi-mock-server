<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Factory;

use Cschindl\OpenApiMockMiddleware\OpenApiMockMiddleware;
use Cschindl\OpenApiMockMiddleware\OpenApiMockMiddlewareConfig;
use Cschindl\OpenApiMockMiddleware\OpenApiMockMiddlewareFactory as BaseFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
        $specPath    = getenv('OPENAPI_SPEC') ?: $projectRoot . '/data/openapi.yaml';

        // Ensure absolute path if relative and not a URL
        if (! str_starts_with($specPath, '/') && ! str_starts_with($specPath, 'http')) {
            $specPath = $projectRoot . DIRECTORY_SEPARATOR . $specPath;
        }

        $config = new OpenApiMockMiddlewareConfig();

        try {
            if (str_starts_with($specPath, 'http')) {
                $context = stream_context_create([
                    'http' => [
                        'header' => 'User-Agent: PHP-OpenAPI-Mock-Server',
                    ],
                ]);
                $content = file_get_contents($specPath, false, $context);
                if ($content === false) {
                    throw new RuntimeException(sprintf('Failed to fetch remote spec from "%s"', $specPath));
                }

                if (str_ends_with($specPath, '.json')) {
                    return BaseFactory::createFromJson(
                        $content,
                        $config,
                        new ResponseFactory(),
                        new StreamFactory()
                    );
                }

                return BaseFactory::createFromYaml(
                    $content,
                    $config,
                    new ResponseFactory(),
                    new StreamFactory()
                );
            }

            if (str_ends_with($specPath, '.json')) {
                return BaseFactory::createFromJsonFile(
                    $specPath,
                    $config,
                    new ResponseFactory(),
                    new StreamFactory()
                );
            }

            return BaseFactory::createFromYamlFile(
                $specPath,
                $config,
                new ResponseFactory(),
                new StreamFactory()
            );
        } catch (Throwable $e) {
            // Return an anonymous middleware that reports the error
            return new class ($e, $specPath, $container) implements MiddlewareInterface {
                public function __construct(
                    private readonly Throwable $e,
                    private readonly string $specPath,
                    private readonly ContainerInterface $container
                ) {
                }

                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    /** @var ProblemDetailsResponseFactory $problemDetailsFactory */
                    $problemDetailsFactory = $this->container->get(ProblemDetailsResponseFactory::class);

                    return $problemDetailsFactory->createResponse(
                        $request,
                        500,
                        $this->e->getMessage(),
                        'Failed to load OpenAPI specification',
                        'CONFIG_ERROR',
                        [
                            'spec_path' => $this->specPath,
                        ]
                    );
                }
            };
        }
    }
}
