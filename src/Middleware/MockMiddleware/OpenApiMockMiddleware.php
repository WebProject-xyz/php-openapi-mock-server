<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware;

use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Request\RequestHandler;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Response\ResponseHandler;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\RequestValidator;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\ResponseValidator;
use Exception;
use const FILTER_VALIDATE_BOOLEAN;
use function filter_var;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class OpenApiMockMiddleware implements MiddlewareInterface
{
    public const string HEADER_OPENAPI_MOCK_ACTIVE     = 'X-OpenApi-Mock-Active';

    public const string HEADER_OPENAPI_MOCK_STATUSCODE = 'X-OpenApi-Mock-StatusCode';

    public const string HEADER_OPENAPI_MOCK_EXAMPLE    = 'X-OpenApi-Mock-Example';

    public const string HEADER_CONTENT_TYPE  = 'Content-Type';

    public const string DEFAULT_CONTENT_TYPE = 'application/json';

    public function __construct(
        private readonly RequestHandler $requestHandler,
        private readonly RequestValidator $requestValidator,
        private readonly ResponseHandler $responseHandler,
        private readonly ResponseValidator $responseValidator,
        private readonly OpenApiMockMiddlewareConfig $openApiMockMiddlewareConfig
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isActive    = $this->isActive($request);
        $statusCode  = $this->getStatusCode($request);
        $contentType = $this->getContentType($request);
        $exampleName = $this->getExample($request);

        $path = $request->getUri()->getPath();
        if ($path === '/' || $path === '/openapi.yaml' || $path === '/openapi.json') {
            return $handler->handle($request);
        }

        if (!$isActive) {
            return $handler->handle($request);
        }

        try {
            $requestValidatorResult = $this->requestValidator->parse($request, $this->openApiMockMiddlewareConfig->validateRequest());

            if ($requestValidatorResult->getException() instanceof Throwable) {
                throw $requestValidatorResult->getException();
            }

            $response = $this->requestHandler->handleValidRequest(
                $requestValidatorResult->getSchema(),
                $requestValidatorResult->getOperationAddress(),
                $contentType,
                $statusCode,
                $exampleName
            );

            $responseResult = $this->responseValidator->parse(
                $response,
                $requestValidatorResult->getOperationAddress(),
                $this->openApiMockMiddlewareConfig->validateResponse()
            );

            if ($responseResult->getException() instanceof Throwable) {
                return $this->responseHandler->handleInvalidResponse($responseResult->getException(), $contentType);
            }

            return $response;
        } catch (Throwable $exception) {
            return $this->requestHandler->handleInvalidRequest(
                $exception,
                isset($requestValidatorResult) ? $requestValidatorResult->getSchema() : null,
                isset($requestValidatorResult) ? $requestValidatorResult->getOperationAddress() : null,
                $contentType
            );
        }
    }

    private function isActive(ServerRequestInterface $serverRequest): bool
    {
        $isActive = $serverRequest->getHeader(self::HEADER_OPENAPI_MOCK_ACTIVE)[0] ?? false;

        return filter_var($isActive, FILTER_VALIDATE_BOOLEAN);
    }

    private function getStatusCode(ServerRequestInterface $serverRequest): ?string
    {
        $statusCode = $serverRequest->getHeader(self::HEADER_OPENAPI_MOCK_STATUSCODE)[0] ?? null;

        return empty($statusCode) ? null : $statusCode;
    }

    private function getContentType(ServerRequestInterface $serverRequest): string
    {
        $contentType = $serverRequest->getHeader(self::HEADER_CONTENT_TYPE)[0] ?? self::DEFAULT_CONTENT_TYPE;

        return empty($contentType) ? self::DEFAULT_CONTENT_TYPE : $contentType;
    }

    private function getExample(ServerRequestInterface $serverRequest): ?string
    {
        $example = $serverRequest->getHeader(self::HEADER_OPENAPI_MOCK_EXAMPLE)[0] ?? null;

        return empty($example) ? null : $example;
    }
}
