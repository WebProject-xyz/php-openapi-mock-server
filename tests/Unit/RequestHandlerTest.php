<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\OpenApi;
use Codeception\Test\Unit;
use Exception;
use League\OpenAPIValidation\PSR7\Exception\NoOperation;
use League\OpenAPIValidation\PSR7\Exception\NoPath;
use League\OpenAPIValidation\PSR7\Exception\NoResponseCode;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidSecurity;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\OperationAddress;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception\RequestErrorType;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception\RoutingException;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception\ValidationException;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Request\RequestHandler;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Response\ResponseFaker;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class RequestHandlerTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    private MockObject&ResponseFaker $responseFaker;

    private RequestHandler $requestHandler;

    protected function _before(): void
    {
        $this->responseFaker  = $this->createMock(ResponseFaker::class);
        $this->requestHandler = new RequestHandler($this->responseFaker);
    }

    public function testHandleValidRequestCallsMockWithCorrectParams(): void
    {
        $openApi          = $this->createMock(OpenApi::class);
        $operationAddress = new OperationAddress('/test', 'get');
        $expectedResponse = $this->createMock(ResponseInterface::class);

        $this->responseFaker->expects(self::once())
            ->method('mock')
            ->with($openApi, $operationAddress, ['200', '201'], ['application/json'], 'default')
            ->willReturn($expectedResponse);

        $response = $this->requestHandler->handleValidRequest($openApi, $operationAddress, ['application/json'], null, 'default');

        self::assertSame($expectedResponse, $response);
    }

    public function testHandleInvalidRequestMatchesNoPathException(): void
    {
        $openApi          = $this->createMock(OpenApi::class);
        $operationAddress = new OperationAddress('/test', 'get');
        $noPath           = NoPath::fromPath('/test');
        $expectedResponse = $this->createMock(ResponseInterface::class);

        // handleNoPathMatchedRequest is called, which calls mock with fallback codes
        $this->responseFaker->expects(self::once())
            ->method('mock')
            ->with($openApi, $operationAddress, ['404', '400', '500', 'default'], ['application/json'])
            ->willReturn($expectedResponse);

        $response = $this->requestHandler->handleInvalidRequest($noPath, $openApi, $operationAddress, ['application/json']);

        self::assertSame($expectedResponse, $response);
    }

    public function testHandleInvalidRequestMatchesInvalidSecurityException(): void
    {
        $openApi          = $this->createMock(OpenApi::class);
        $operationAddress = new OperationAddress('/test', 'get');
        $exception        = $this->createMock(InvalidSecurity::class);
        $expectedResponse = $this->createMock(ResponseInterface::class);

        $this->responseFaker->expects(self::once())
            ->method('mock')
            ->with($openApi, $operationAddress, ['401', '500', 'default'], ['application/json'])
            ->willReturn($expectedResponse);

        $response = $this->requestHandler->handleInvalidRequest($exception, $openApi, $operationAddress, ['application/json']);

        self::assertSame($expectedResponse, $response);
    }

    public function testHandleInvalidRequestMatchesValidationFailedException(): void
    {
        $openApi          = $this->createMock(OpenApi::class);
        $operationAddress = new OperationAddress('/test', 'get');
        $exception        = $this->createMock(ValidationFailed::class);
        $expectedResponse = $this->createMock(ResponseInterface::class);

        $this->responseFaker->expects(self::once())
            ->method('mock')
            ->with($openApi, $operationAddress, ['422', '400', '500', 'default'], ['application/json'])
            ->willReturn($expectedResponse);

        $response = $this->requestHandler->handleInvalidRequest($exception, $openApi, $operationAddress, ['application/json']);

        self::assertSame($expectedResponse, $response);
    }

    public function testHandleInvalidRequestFallsBackToDefaultExceptionHandling(): void
    {
        $openApi          = $this->createMock(OpenApi::class);
        $operationAddress = new OperationAddress('/test', 'get');
        $exception        = new Exception('Unknown error');
        $expectedResponse = $this->createMock(ResponseInterface::class);

        $this->responseFaker->expects(self::once())
            ->method('handleException')
            ->with(self::callback(static fn ($e): bool => $e instanceof ValidationException), 'application/json')
            ->willReturn($expectedResponse);

        $response = $this->requestHandler->handleInvalidRequest($exception, $openApi, $operationAddress, ['application/json']);

        self::assertSame($expectedResponse, $response);
    }

    public function testHandleInvalidRequestMatchesNoOperationException(): void
    {
        $openApi            = $this->createMock(OpenApi::class);
        $operationAddress   = new OperationAddress('/test', 'get');
        $noOperation        = NoOperation::fromPathAndMethod('/test', 'get');
        $expectedResponse   = $this->createMock(ResponseInterface::class);

        // handleNoPathMatchedRequest is called
        // Inside it, it tries mock and if it fails, it calls handleException with RoutingException
        $this->responseFaker->method('mock')->willThrowException(new Exception('Mock failed'));

        $this->responseFaker->expects(self::once())
            ->method('handleException')
            ->with(self::callback(static fn ($e): bool => $e instanceof RoutingException && RequestErrorType::NO_PATH_AND_METHOD_MATCHED_ERROR->value === $e->getType()), 'application/json')
            ->willReturn($expectedResponse);

        $response = $this->requestHandler->handleInvalidRequest($noOperation, $openApi, $operationAddress, ['application/json']);

        self::assertSame($expectedResponse, $response);
    }

    public function testHandleInvalidRequestMatchesNoResponseCodeException(): void
    {
        $openApi               = $this->createMock(OpenApi::class);
        $operationAddress      = new OperationAddress('/test', 'get');
        $noResponseCode        = NoResponseCode::fromPathAndMethodAndResponseCode('/test', 'get', 200);
        $expectedResponse      = $this->createMock(ResponseInterface::class);

        // handleNoPathMatchedRequest is called
        // Inside it, it tries mock and if it fails, it calls handleException with RoutingException
        $this->responseFaker->method('mock')->willThrowException(new Exception('Mock failed'));

        $this->responseFaker->expects(self::once())
            ->method('handleException')
            ->with(self::callback(static fn ($e): bool => $e instanceof RoutingException && RequestErrorType::NO_PATH_AND_METHOD_AND_RESPONSE_CODE_MATCHED_ERROR->value === $e->getType()), 'application/json')
            ->willReturn($expectedResponse);

        $response = $this->requestHandler->handleInvalidRequest($noResponseCode, $openApi, $operationAddress, ['application/json']);

        self::assertSame($expectedResponse, $response);
    }

    public function testHandleInvalidRequestWithNullSchema(): void
    {
        $exception        = new Exception('Fatal error during parsing');
        $expectedResponse = $this->createMock(ResponseInterface::class);

        $this->responseFaker->expects(self::once())
            ->method('handleException')
            ->with(self::callback(static fn ($e): bool => $e instanceof ValidationException), 'application/json')
            ->willReturn($expectedResponse);

        $response = $this->requestHandler->handleInvalidRequest($exception, null, null, ['application/json']);

        self::assertSame($expectedResponse, $response);
    }
}
