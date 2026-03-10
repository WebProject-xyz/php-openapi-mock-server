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
     * @throws InvalidArgumentException
     */
    public function handleValidRequest(
        OpenApi $openApi,
        OperationAddress $operationAddress,
        string $contentType,
        ?string $statusCode = null,
        ?string $exampleName = null
    ): ResponseInterface {
        return $this->responseFaker->mock($openApi, $operationAddress, $statusCode ?? ['200', '201'], $contentType, $exampleName);
    }

    /**
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function handleInvalidRequest(
        Throwable $exception,
        ?OpenApi $openApi,
        ?OperationAddress $operationAddress,
        string $contentType
    ): ResponseInterface {
        // ValidationException::forNotAcceptable
        // Message: The server cannot produce a representation for your accept header
        // Returned Status Code: 406
        // Explanation: This error occurs when the current request has asked the response in a format that the current document
        // is not able to produce.

        if ($openApi === null || $operationAddress === null) {
            return $this->responseFaker->handleException(ValidationException::forViolations($exception), $contentType);
        }

        return match (true) {
            $exception instanceof NoPath,
            $exception instanceof FakerNoPath => $this->handleNoPathMatchedRequest($exception, $openApi, $operationAddress, $contentType),

            $exception instanceof InvalidSecurity => $this->handleInvalidSecurityRequest($exception, $openApi, $operationAddress, $contentType),

            $exception instanceof ValidationFailed => $this->handleValidationFailedRequest($exception, $openApi, $operationAddress, $contentType),

            default => $this->responseFaker->handleException(ValidationException::forViolations($exception), $contentType),
        };
    }

    /**
     * @throws InvalidArgumentException
     */
    public function handleNoPathMatchedRequest(
        Throwable $throwable,
        OpenApi $openApi,
        OperationAddress $operationAddress,
        string $contentType
    ): ResponseInterface {
        try {
            return $this->responseFaker->mock($openApi, $operationAddress, ['404', '400', '500', 'default'], $contentType);
        } catch (Throwable $th) {
            $th = match (true) {
                $throwable instanceof NoResponseCode => RoutingException::forNoPathAndMethodAndResponseCodeMatched($throwable),
                $throwable instanceof NoOperation    => RoutingException::forNoPathAndMethodMatched($throwable),
                $throwable instanceof NoPath,
                $throwable instanceof FakerNoPath    => RoutingException::forNoPathMatched($throwable),
                default                             => ValidationException::forViolations($throwable),
            };

            return $this->responseFaker->handleException($th, $contentType);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function handleInvalidSecurityRequest(
        Throwable $throwable,
        OpenApi $openApi,
        OperationAddress $operationAddress,
        string $contentType
    ): ResponseInterface {
        try {
            return $this->responseFaker->mock($openApi, $operationAddress, ['401', '500', 'default'], $contentType);
        } catch (Throwable) {
            return $this->responseFaker->handleException(SecurityException::forUnauthorized($throwable), $contentType);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function handleValidationFailedRequest(
        Throwable $throwable,
        OpenApi $openApi,
        OperationAddress $operationAddress,
        string $contentType
    ): ResponseInterface {
        try {
            return $this->responseFaker->mock($openApi, $operationAddress, ['422', '400', '500', 'default'], $contentType);
        } catch (Throwable) {
            return $this->responseFaker->handleException(ValidationException::forUnprocessableEntity($throwable), $contentType);
        }
    }
}
