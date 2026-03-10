<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware;

use function array_map;
use function array_slice;
use function explode;
use const FILTER_VALIDATE_BOOLEAN;
use function filter_var;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function str_contains;
use function str_starts_with;
use function substr;
use Throwable;
use function trim;
use function usort;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Request\RequestHandler;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Response\ResponseHandler;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\RequestValidator;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\ResponseValidator;

class OpenApiMockMiddleware implements MiddlewareInterface
{
    public const string HEADER_OPENAPI_MOCK_ACTIVE     = 'X-OpenApi-Mock-Active';

    public const string HEADER_OPENAPI_MOCK_STATUSCODE = 'X-OpenApi-Mock-StatusCode';

    public const string HEADER_OPENAPI_MOCK_EXAMPLE    = 'X-OpenApi-Mock-Example';

    public const string HEADER_ACCEPT = 'Accept';

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
        $isActive             = $this->isActive($request);
        $statusCode           = $this->getStatusCode($request);
        $acceptedContentTypes = $this->getAcceptedContentTypes($request);
        $exampleName          = $this->getExample($request);

        $path = $request->getUri()->getPath();
        if ('/' === $path || '/openapi.yaml' === $path || '/openapi.json' === $path) {
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
                $acceptedContentTypes,
                $statusCode,
                $exampleName,
                $requestValidatorResult->getPathParameters()
            );

            $responseResult = $this->responseValidator->parse(
                $response,
                $requestValidatorResult->getOperationAddress(),
                $this->openApiMockMiddlewareConfig->validateResponse()
            );

            if ($responseResult->getException() instanceof Throwable) {
                return $this->responseHandler->handleInvalidResponse(
                    $responseResult->getException(),
                    $acceptedContentTypes[0] ?? self::DEFAULT_CONTENT_TYPE
                );
            }

            return $response;
        } catch (Throwable $throwable) {
            return $this->requestHandler->handleInvalidRequest(
                $throwable,
                isset($requestValidatorResult) ? $requestValidatorResult->getSchema() : null,
                isset($requestValidatorResult) ? $requestValidatorResult->getOperationAddress() : null,
                $acceptedContentTypes,
                isset($requestValidatorResult) ? $requestValidatorResult->getPathParameters() : []
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

    /**
     * Parse the Accept header into a list of content types ordered by quality preference.
     *
     * @return list<string>
     */
    private function getAcceptedContentTypes(ServerRequestInterface $serverRequest): array
    {
        $accept = $serverRequest->getHeaderLine(self::HEADER_ACCEPT);

        if ('' === $accept || '*/*' === $accept) {
            return [self::DEFAULT_CONTENT_TYPE];
        }

        /** @var list<array{type: string, quality: float}> $types */
        $types = [];

        foreach (explode(',', $accept) as $part) {
            $part    = trim($part);
            $quality = 1.0;

            if (str_contains($part, ';')) {
                $segments = explode(';', $part);
                $part     = trim($segments[0]);
                foreach (array_slice($segments, 1) as $param) {
                    $param = trim($param);
                    if (str_starts_with($param, 'q=')) {
                        $quality = (float) substr($param, 2);
                    }
                }
            }

            if ('' !== $part) {
                $types[] = ['type' => $part, 'quality' => $quality];
            }
        }

        usort($types, static fn (array $a, array $b): int => $b['quality'] <=> $a['quality']);

        $result = array_map(static fn (array $t): string => $t['type'], $types);

        return [] !== $result ? $result : [self::DEFAULT_CONTENT_TYPE];
    }

    private function getExample(ServerRequestInterface $serverRequest): ?string
    {
        $example = $serverRequest->getHeader(self::HEADER_OPENAPI_MOCK_EXAMPLE)[0] ?? null;

        return empty($example) ? null : $example;
    }
}
