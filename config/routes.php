<?php
declare(strict_types=1);

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return static function (Application $app, MiddlewareFactory $factory): void {
    // Fallback for routes not in the spec but defined in Mezzio
    $app->get('/', static function (ServerRequestInterface $request): ResponseInterface {
        return new Laminas\Diactoros\Response\JsonResponse([
            'message'      => 'OpenAPI Mock Server is running!',
            'instructions' => 'Point your requests to endpoints defined in your OpenAPI spec.',
            'spec_path'    => getenv('OPENAPI_SPEC') ?: 'data/openapi.yaml',
        ]);
    });
};
