<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware;

use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Request\RequestHandler;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Response\ResponseFaker;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Response\ResponseHandler;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\RequestValidator;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\ResponseValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class OpenApiMockMiddlewareBuilder
{
    public static function createFromYaml(
        string $yaml,
        OpenApiMockMiddlewareConfig $openApiMockMiddlewareConfig,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ?CacheItemPoolInterface $cacheItemPool = null
    ): OpenApiMockMiddleware {
        $validatorBuilder = (new ValidatorBuilder())->fromYaml($yaml);
        if ($cacheItemPool instanceof CacheItemPoolInterface) {
            $validatorBuilder->setCache($cacheItemPool);
        }

        return self::createFromValidatorBuilder(
            $validatorBuilder,
            $openApiMockMiddlewareConfig,
            $responseFactory,
            $streamFactory
        );
    }

    public static function createFromYamlFile(
        string $pathToYaml,
        OpenApiMockMiddlewareConfig $openApiMockMiddlewareConfig,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ?CacheItemPoolInterface $cacheItemPool = null
    ): OpenApiMockMiddleware {
        $validatorBuilder = (new ValidatorBuilder())->fromYamlFile($pathToYaml);
        if ($cacheItemPool instanceof CacheItemPoolInterface) {
            $validatorBuilder->setCache($cacheItemPool);
        }

        return self::createFromValidatorBuilder(
            $validatorBuilder,
            $openApiMockMiddlewareConfig,
            $responseFactory,
            $streamFactory
        );
    }

    public static function createFromJson(
        string $json,
        OpenApiMockMiddlewareConfig $openApiMockMiddlewareConfig,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ?CacheItemPoolInterface $cacheItemPool = null
    ): OpenApiMockMiddleware {
        $validatorBuilder = (new ValidatorBuilder())->fromJson($json);
        if ($cacheItemPool instanceof CacheItemPoolInterface) {
            $validatorBuilder->setCache($cacheItemPool);
        }

        return self::createFromValidatorBuilder(
            $validatorBuilder,
            $openApiMockMiddlewareConfig,
            $responseFactory,
            $streamFactory
        );
    }

    public static function createFromJsonFile(
        string $pathToJson,
        OpenApiMockMiddlewareConfig $openApiMockMiddlewareConfig,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ?CacheItemPoolInterface $cacheItemPool = null
    ): OpenApiMockMiddleware {
        $validatorBuilder = (new ValidatorBuilder())->fromJsonFile($pathToJson);
        if ($cacheItemPool instanceof CacheItemPoolInterface) {
            $validatorBuilder->setCache($cacheItemPool);
        }

        return self::createFromValidatorBuilder(
            $validatorBuilder,
            $openApiMockMiddlewareConfig,
            $responseFactory,
            $streamFactory
        );
    }

    public static function createFromValidatorBuilder(
        ValidatorBuilder $validatorBuilder,
        OpenApiMockMiddlewareConfig $openApiMockMiddlewareConfig,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ): OpenApiMockMiddleware {
        $responseFaker = new ResponseFaker(
            $responseFactory,
            $streamFactory,
            $openApiMockMiddlewareConfig->getOptions()
        );

        return new OpenApiMockMiddleware(
            new RequestHandler($responseFaker),
            new RequestValidator($validatorBuilder),
            new ResponseHandler($responseFaker),
            new ResponseValidator($validatorBuilder),
            $openApiMockMiddlewareConfig
        );
    }
}
