<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\OpenApi;
use Codeception\Test\Unit;
use Exception;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidSecurity;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\OperationAddress;
use Psr\Http\Message\ResponseInterface;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception\SecurityException;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception\ValidationException;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Request\RequestHandler;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Response\ResponseFaker;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class SchemaFakerExtraTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testHandleInvalidSecurityRequestCatchBlock(): void
    {
        $responseFaker        = $this->createMock(ResponseFaker::class);
        $requestHandler       = new RequestHandler($responseFaker);

        $openApi            = $this->createMock(OpenApi::class);
        $operationAddress   = new OperationAddress('/test', 'get');
        $exception          = $this->createMock(InvalidSecurity::class);

        // Force an exception inside handleInvalidSecurityRequest to hit the catch block
        $responseFaker->method('mock')->willThrowException(new Exception('Inner failure'));
        $responseFaker->expects(self::once())
            ->method('handleException')
            ->with(self::callback(static fn ($e): bool => $e instanceof SecurityException), 'application/json')
            ->willReturn($this->createMock(ResponseInterface::class));

        $requestHandler->handleInvalidRequest($exception, $openApi, $operationAddress, ['application/json']);
    }

    public function testHandleValidationFailedRequestCatchBlock(): void
    {
        $responseFaker        = $this->createMock(ResponseFaker::class);
        $requestHandler       = new RequestHandler($responseFaker);

        $openApi            = $this->createMock(OpenApi::class);
        $operationAddress   = new OperationAddress('/test', 'get');
        $exception          = $this->createMock(ValidationFailed::class);

        $responseFaker->method('mock')->willThrowException(new Exception('Inner failure'));
        $responseFaker->expects(self::once())
            ->method('handleException')
            ->with(self::callback(static fn ($e): bool => $e instanceof ValidationException), 'application/json')
            ->willReturn($this->createMock(ResponseInterface::class));

        $requestHandler->handleInvalidRequest($exception, $openApi, $operationAddress, ['application/json']);
    }
}
