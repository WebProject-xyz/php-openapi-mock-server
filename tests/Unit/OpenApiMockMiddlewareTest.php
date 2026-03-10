<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\OpenApi;
use Codeception\Test\Unit;
use Exception;
use League\OpenAPIValidation\PSR7\OperationAddress;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddleware;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\OpenApiMockMiddlewareConfig;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Request\RequestHandler;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Response\ResponseHandler;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\RequestValidator;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\RequestValidatorResult;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\ResponseValidator;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\ResponseValidatorResult;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class OpenApiMockMiddlewareTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    private MockObject&RequestHandler $requestHandler;

    private MockObject&RequestValidator $requestValidator;

    private MockObject&ResponseHandler $responseHandler;

    private MockObject&ResponseValidator $responseValidator;

    private MockObject&OpenApiMockMiddlewareConfig $openApiMockMiddlewareConfig;

    private OpenApiMockMiddleware $openApiMockMiddleware;

    protected function _before(): void
    {
        $this->requestHandler              = $this->createMock(RequestHandler::class);
        $this->requestValidator            = $this->createMock(RequestValidator::class);
        $this->responseHandler             = $this->createMock(ResponseHandler::class);
        $this->responseValidator           = $this->createMock(ResponseValidator::class);
        $this->openApiMockMiddlewareConfig = $this->createMock(OpenApiMockMiddlewareConfig::class);

        $this->openApiMockMiddleware = new OpenApiMockMiddleware(
            $this->requestHandler,
            $this->requestValidator,
            $this->responseHandler,
            $this->responseValidator,
            $this->openApiMockMiddlewareConfig
        );
    }

    /**
     * @description Should pass to the next handler if mock is not active.
     */
    public function testShouldPassToHandlerIfMockIsNotActive(): void
    {
        // Arrange
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeader')->willReturnMap([
            [OpenApiMockMiddleware::HEADER_OPENAPI_MOCK_ACTIVE, []],
            [OpenApiMockMiddleware::HEADER_OPENAPI_MOCK_STATUSCODE, []],
            [OpenApiMockMiddleware::HEADER_OPENAPI_MOCK_EXAMPLE, []],
        ]);
        $request->method('getHeaderLine')->willReturnMap([
            [OpenApiMockMiddleware::HEADER_ACCEPT, ''],
        ]);

        $nextHandler      = $this->createMock(RequestHandlerInterface::class);
        $expectedResponse = $this->createMock(ResponseInterface::class);
        $nextHandler->expects(self::once())->method('handle')->with($request)->willReturn($expectedResponse);

        // Act
        $response = $this->openApiMockMiddleware->process($request, $nextHandler);

        // Assert
        self::assertSame($expectedResponse, $response);
    }

    /**
     * @description Should return mock response for a valid request.
     */
    public function testShouldReturnMockResponseForValidRequest(): void
    {
        // Arrange
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeader')->willReturnMap([
            [OpenApiMockMiddleware::HEADER_OPENAPI_MOCK_ACTIVE, ['true']],
            [OpenApiMockMiddleware::HEADER_OPENAPI_MOCK_STATUSCODE, ['200']],
            [OpenApiMockMiddleware::HEADER_OPENAPI_MOCK_EXAMPLE, ['default']],
        ]);
        $request->method('getHeaderLine')->willReturnMap([
            [OpenApiMockMiddleware::HEADER_ACCEPT, 'application/json'],
        ]);

        $schema                  = $this->createMock(OpenApi::class);
        $operationAddress        = new OperationAddress('/test', 'get');
        $requestValidatorResult  = new RequestValidatorResult($schema, $operationAddress);

        $this->requestValidator->method('parse')->willReturn($requestValidatorResult);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->requestHandler->expects(self::once())->method('handleValidRequest')
            ->with($schema, $operationAddress, ['application/json'], '200', 'default')
            ->willReturn($mockResponse);

        $responseResult = $this->createMock(ResponseValidatorResult::class);
        $responseResult->method('getException')->willReturn(null);
        $this->responseValidator->method('parse')->willReturn($responseResult);

        $nextHandler = $this->createMock(RequestHandlerInterface::class);

        // Act
        $response = $this->openApiMockMiddleware->process($request, $nextHandler);

        // Assert
        self::assertSame($mockResponse, $response);
    }

    /**
     * @description Should handle invalid request if validator returns an exception.
     */
    public function testShouldHandleInvalidRequestOnValidationFailure(): void
    {
        // Arrange
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeader')->willReturnMap([
            [OpenApiMockMiddleware::HEADER_OPENAPI_MOCK_ACTIVE, ['true']],
            [OpenApiMockMiddleware::HEADER_OPENAPI_MOCK_STATUSCODE, []],
            [OpenApiMockMiddleware::HEADER_OPENAPI_MOCK_EXAMPLE, []],
        ]);
        $request->method('getHeaderLine')->willReturnMap([
            [OpenApiMockMiddleware::HEADER_ACCEPT, 'application/json'],
        ]);

        $schema                  = $this->createMock(OpenApi::class);
        $operationAddress        = new OperationAddress('/test', 'get');
        $runtimeException        = new RuntimeException('Validation Failed');
        $requestValidatorResult  = new RequestValidatorResult($schema, $operationAddress, $runtimeException);

        $this->requestValidator->method('parse')->willReturn($requestValidatorResult);

        $errorResponse = $this->createMock(ResponseInterface::class);
        $this->requestHandler->expects(self::once())->method('handleInvalidRequest')
            ->with($runtimeException, $schema, $operationAddress, ['application/json'])
            ->willReturn($errorResponse);

        $nextHandler = $this->createMock(RequestHandlerInterface::class);

        // Act
        $response = $this->openApiMockMiddleware->process($request, $nextHandler);

        // Assert
        self::assertSame($errorResponse, $response);
    }

    /**
     * @description Should handle invalid response if response validator fails.
     */
    public function testShouldHandleInvalidResponseOnResponseValidationFailure(): void
    {
        // Arrange
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeader')->willReturnMap([
            [OpenApiMockMiddleware::HEADER_OPENAPI_MOCK_ACTIVE, ['true']],
            [OpenApiMockMiddleware::HEADER_OPENAPI_MOCK_STATUSCODE, []],
            [OpenApiMockMiddleware::HEADER_OPENAPI_MOCK_EXAMPLE, []],
        ]);
        $request->method('getHeaderLine')->willReturnMap([
            [OpenApiMockMiddleware::HEADER_ACCEPT, 'application/json'],
        ]);

        $schema                 = $this->createMock(OpenApi::class);
        $operationAddress       = new OperationAddress('/test', 'get');
        $requestValidatorResult = new RequestValidatorResult($schema, $operationAddress);
        $this->requestValidator->method('parse')->willReturn($requestValidatorResult);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->requestHandler->method('handleValidRequest')->willReturn($mockResponse);

        $responseException = new Exception('Invalid Response Content');
        $responseResult    = $this->createMock(ResponseValidatorResult::class);
        $responseResult->method('getException')->willReturn($responseException);
        $this->responseValidator->method('parse')->willReturn($responseResult);

        $errorResponse = $this->createMock(ResponseInterface::class);
        $this->responseHandler->expects(self::once())->method('handleInvalidResponse')
            ->with($responseException, 'application/json')
            ->willReturn($errorResponse);

        $nextHandler = $this->createMock(RequestHandlerInterface::class);

        // Act
        $response = $this->openApiMockMiddleware->process($request, $nextHandler);

        // Assert
        self::assertSame($errorResponse, $response);
    }
}
