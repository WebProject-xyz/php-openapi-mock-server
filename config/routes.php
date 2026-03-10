<?php
declare(strict_types=1);

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Utils\RemoteSpecificationLoader;

return static function (Application $application, MiddlewareFactory $middlewareFactory): void {
    $specPath = getenv('OPENAPI_SPEC') ?: null;
    $packageRoot = realpath(__DIR__ . '/..') ?: '/app';

    if ($specPath === null || $specPath === false) {
        $specPath = $packageRoot . '/data/openapi.yaml';
    } elseif (!str_starts_with((string) $specPath, '/') && !str_starts_with((string) $specPath, 'http')) {
        $resolveBase = str_contains($packageRoot, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)
            ? (getcwd() ?: '.')
            : $packageRoot;
        $specPath = $resolveBase . DIRECTORY_SEPARATOR . $specPath;
    }

    $specHandler = static function (ServerRequestInterface $serverRequest) use ($specPath): ResponseInterface {
        try {
            $path = (string) $specPath;

            if (str_starts_with($path, 'http')) {
                $content = file_get_contents($path, false, RemoteSpecificationLoader::createStreamContext());
            } else {
                $content = file_exists($path) ? file_get_contents($path) : null;
            }

            if ($content === false || $content === null) {
                return new TextResponse('OpenAPI spec not found at ' . $path, 404);
            }

            $requestedPath = $serverRequest->getUri()->getPath();
            $isJsonRequest = str_ends_with($requestedPath, '.json');

            // Serve the correct Content-Type for the requested route.
            $contentType = $isJsonRequest ? 'application/json' : 'text/yaml';

            return new TextResponse($content, 200, [
                'Content-Type' => $contentType,
            ]);
        } catch (Throwable $e) {
            return new TextResponse('Error loading spec: ' . $e->getMessage(), 500);
        }
    };

    // Route registrations
    $application->get('/openapi.yaml', $specHandler, 'openapi_yaml');
    $application->get('/openapi.json', $specHandler, 'openapi_json');

    // Swagger UI at root
    $application->get('/', static function (ServerRequestInterface $serverRequest) use ($specPath): ResponseInterface {
        $accept = $serverRequest->getHeaderLine('Accept');
        if (str_contains($accept, 'application/json')) {
            return new JsonResponse([
                'message'      => 'OpenAPI Mock Server is running!',
                'instructions' => 'Point your requests to endpoints defined in your OpenAPI spec.',
                'spec_path'    => $specPath,
            ]);
        }

        // Determine the correct spec URL for Swagger UI
        $path = (string) $specPath;
        $parsedPath = parse_url($path, PHP_URL_PATH) ?: $path;
        $extension = strtolower(pathinfo($parsedPath, PATHINFO_EXTENSION));
        $specUrl = $extension === 'json' ? '/openapi.json' : '/openapi.yaml';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="SwaggerUI" />
  <title>OpenAPI Mock Server - Swagger UI</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
  <style>
    html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
    *, *:before, *:after { box-sizing: inherit; }
    body { margin: 0; background: #fafafa; }
  </style>
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js" crossorigin></script>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js" crossorigin></script>
<script>
  window.onload = () => {
    window.ui = SwaggerUIBundle({
      url: '$specUrl',
      dom_id: '#swagger-ui',
      deepLinking: true,
      presets: [
        SwaggerUIBundle.presets.apis,
        SwaggerUIStandalonePreset
      ],
      plugins: [
        SwaggerUIBundle.plugins.DownloadUrl
      ],
      layout: "BaseLayout",
    });
  };
</script>
</body>
</html>
HTML;
        return new HtmlResponse($html);
    }, 'home');
};
