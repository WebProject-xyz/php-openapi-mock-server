<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Response;

use function array_filter;
use function in_array;
use cebe\openapi\spec\OpenApi;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception\RequestException;
use InvalidArgumentException;
use function json_encode;
use League\OpenAPIValidation\PSR7\OperationAddress;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Exception\NoExample;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Exception\NoPath;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Exception\NoResponse;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\OpenAPIFaker;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;

class ResponseFaker
{
    private ?OpenAPIFaker $openAPIFaker = null;

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly Options $options
    ) {
    }

    /**
     * @param array<int, string>|string $statusCodes
     * @param list<string>              $acceptedContentTypes
     *
     * @throws NoPath
     * @throws NoResponse
     * @throws NoExample
     * @throws InvalidArgumentException
     */
    public function mock(
        OpenApi $openApi,
        OperationAddress $operationAddress,
        array|string $statusCodes,
        array $acceptedContentTypes = ['application/json'],
        ?string $exampleName = null
    ): ResponseInterface {
        $codes = (array) $statusCodes;
        $lastException = null;

        foreach ($codes as $code) {
            try {
                return $this->mockResponse($openApi, $operationAddress, (string) $code, $acceptedContentTypes, $exampleName);
            } catch (NoResponse|NoPath|NoExample $th) {
                $lastException = $th;
                continue;
            }
        }

        throw $lastException ?? new NoResponse();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function handleException(Throwable $throwable, ?string $contentType): ResponseInterface
    {
        if ($throwable instanceof RequestException) {
            $error = [
                'type'   => $throwable->getType(),
                'title'  => $throwable->getTitle(),
                'detail' => $throwable->getMessage(),
                'status' => $throwable->getCode(),
            ];
            $statusCode =  $throwable->getCode();
        } else {
            $error = [
                'type'   => 'ERROR',
                'title'  => 'Unexpected error occurred',
                'detail' => $throwable->getMessage(),
                'status' => 500,
            ];
            $statusCode = 500;
        }

        $response = $this->responseFactory->createResponse();
        $stream     = $this->streamFactory->createStream((string) json_encode($error));

        return $response->withBody($stream)->withStatus($statusCode)->withAddedHeader('Content-Type', $contentType ?? 'application/problem+json');
    }

    /**
     * @param list<string> $acceptedContentTypes
     *
     * @throws NoPath
     * @throws NoResponse
     * @throws NoExample
     * @throws InvalidArgumentException
     */
    private function mockResponse(
        OpenApi $openApi,
        OperationAddress $operationAddress,
        string $statusCode = '200',
        array $acceptedContentTypes = ['application/json'],
        ?string $exampleName = null
    ): ResponseInterface {
        $openAPIFaker = $this->createFaker($openApi);

        $path   = $operationAddress->path();
        $method = $operationAddress->method();

        if ($this->isNoContentResponse($openAPIFaker, $path, $method, $statusCode)) {
            return $this->responseFactory->createResponse((int) $statusCode);
        }

        $contentType = $this->negotiateContentType($openAPIFaker, $path, $method, $statusCode, $acceptedContentTypes);

        $fakeData = null !== $exampleName
            ? $openAPIFaker->mockResponseForExample($path, $method, $exampleName, $statusCode, $contentType)
            : $openAPIFaker->mockResponse($path, $method, $statusCode, $contentType);

        $response = $this->responseFactory->createResponse();
        $stream     = $this->streamFactory->createStream((string) json_encode($fakeData));

        return $response->withStatus((int) $statusCode)->withBody($stream)->withAddedHeader('Content-Type', $contentType);
    }

    /**
     * @param list<string> $acceptedContentTypes
     */
    private function negotiateContentType(
        OpenAPIFaker $openAPIFaker,
        string $path,
        string $method,
        string $statusCode,
        array $acceptedContentTypes
    ): string {
        $availableTypes = $openAPIFaker->getAvailableResponseContentTypes($path, $method, $statusCode);

        if ($availableTypes === []) {
            return $acceptedContentTypes[0] ?? 'application/json';
        }

        foreach ($acceptedContentTypes as $acceptedType) {
            if ($acceptedType === '*/*') {
                return $availableTypes[0];
            }

            if (in_array($acceptedType, $availableTypes, true)) {
                return $acceptedType;
            }
        }

        // No match found — use the first content type defined in the spec
        return $availableTypes[0];
    }

    /**
     * Check if the response exists in the spec but defines no content (e.g. 204 No Content).
     */
    private function isNoContentResponse(
        OpenAPIFaker $openAPIFaker,
        string $path,
        string $method,
        string $statusCode,
    ): bool {
        if (! $openAPIFaker->hasResponse($path, $method, $statusCode)) {
            return false;
        }

        return $openAPIFaker->getAvailableResponseContentTypes($path, $method, $statusCode) === [];
    }

    private function createFaker(OpenApi $openApi): OpenAPIFaker
    {
        if ($this->openAPIFaker instanceof OpenAPIFaker) {
            return $this->openAPIFaker;
        }

        $this->openAPIFaker = OpenAPIFaker::createFromSchema($openApi)->setOptions(array_filter([
            'minItems'            => $this->options->getMaxItems(),
            'maxItems'            => $this->options->getMaxItems(),
            'alwaysFakeOptionals' => $this->options->getAlwaysFakeOptionals(),
            'strategy'            => $this->options->getStrategy(),
        ], static fn (mixed $v): bool => null !== $v));

        return $this->openAPIFaker;
    }
}
