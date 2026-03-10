<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Request;

use cebe\openapi\spec\OpenApi;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception\RoutingException;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception\SecurityException;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception\ValidationException;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Response\ResponseFaker;
use Exception;
use InvalidArgumentException;
use League\OpenAPIValidation\PSR7\Exception\NoOperation;
use League\OpenAPIValidation\PSR7\Exception\NoPath;
use League\OpenAPIValidation\PSR7\Exception\NoResponseCode;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidSecurity;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\OperationAddress;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Exception\NoPath as FakerNoPath;

class RequestHandler
{
    public function __construct(private readonly ResponseFaker $responseFaker)
    {
    }

    /**
     * @param list<string> $acceptedContentTypes
     *
     * @throws InvalidArgumentException
     */
    public function handleValidRequest(
        OpenApi $openApi,
        OperationAddress $operationAddress,
        array $acceptedContentTypes,
        ?string $statusCode = null,
        ?string $exampleName = null
    ): ResponseInterface {
        return $this->responseFaker->mock($openApi, $operationAddress, $statusCode ?? ['200', '201'], $acceptedContentTypes, $exampleName);
    }

    /**
     * @param list<string> $acceptedContentTypes
     *
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function handleInvalidRequest(
        Throwable $exception,
        ?OpenApi $openApi,
        ?OperationAddress $operationAddress,
        array $acceptedContentTypes
    ): ResponseInterface {
        $errorContentType = $acceptedContentTypes[0] ?? 'application/json';

        if ($openApi === null || $operationAddress === null) {
            return $this->responseFaker->handleException(ValidationException::forViolations($exception), $errorContentType);
        }

        return match (true) {
            $exception instanceof NoPath,
            $exception instanceof FakerNoPath => $this->handleNoPathMatchedRequest($exception, $openApi, $operationAddress, $acceptedContentTypes),

            $exception instanceof InvalidSecurity => $this->handleInvalidSecurityRequest($exception, $openApi, $operationAddress, $acceptedContentTypes),

            $exception instanceof ValidationFailed => $this->handleValidationFailedRequest($exception, $openApi, $operationAddress, $acceptedContentTypes),

            default => $this->responseFaker->handleException(ValidationException::forViolations($exception), $errorContentType),
        };
    }

    /**
     * @param list<string> $acceptedContentTypes
     *
     * @throws InvalidArgumentException
     */
    public function handleNoPathMatchedRequest(
        Throwable $throwable,
        OpenApi $openApi,
        OperationAddress $operationAddress,
        array $acceptedContentTypes
    ): ResponseInterface {
        try {
            return $this->responseFaker->mock($openApi, $operationAddress, ['404', '400', '500', 'default'], $acceptedContentTypes);
        } catch (Throwable $th) {
            $th = match (true) {
                $throwable instanceof NoResponseCode => RoutingException::forNoPathAndMethodAndResponseCodeMatched($throwable),
                $throwable instanceof NoOperation    => RoutingException::forNoPathAndMethodMatched($throwable),
                $throwable instanceof NoPath,
                $throwable instanceof FakerNoPath    => RoutingException::forNoPathMatched($throwable),
                default                             => ValidationException::forViolations($throwable),
            };

            return $this->responseFaker->handleException($th, $acceptedContentTypes[0] ?? 'application/json');
        }
    }

    /**
     * @param list<string> $acceptedContentTypes
     *
     * @throws InvalidArgumentException
     */
    public function handleInvalidSecurityRequest(
        Throwable $throwable,
        OpenApi $openApi,
        OperationAddress $operationAddress,
        array $acceptedContentTypes
    ): ResponseInterface {
        try {
            return $this->responseFaker->mock($openApi, $operationAddress, ['401', '500', 'default'], $acceptedContentTypes);
        } catch (Throwable) {
            return $this->responseFaker->handleException(SecurityException::forUnauthorized($throwable), $acceptedContentTypes[0] ?? 'application/json');
        }
    }

    /**
     * @param list<string> $acceptedContentTypes
     *
     * @throws InvalidArgumentException
     */
    public function handleValidationFailedRequest(
        Throwable $throwable,
        OpenApi $openApi,
        OperationAddress $operationAddress,
        array $acceptedContentTypes
    ): ResponseInterface {
        try {
            return $this->responseFaker->mock($openApi, $operationAddress, ['422', '400', '500', 'default'], $acceptedContentTypes);
        } catch (Throwable) {
            return $this->responseFaker->handleException(ValidationException::forUnprocessableEntity($throwable), $acceptedContentTypes[0] ?? 'application/json');
        }
    }
}
