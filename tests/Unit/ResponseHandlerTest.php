<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use Codeception\Test\Unit;
use Exception;
use Psr\Http\Message\ResponseInterface;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception\ValidationException;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Response\ResponseFaker;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Response\ResponseHandler;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class ResponseHandlerTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testHandleInvalidResponseCallsFaker(): void
    {
        $responseFaker    = $this->createMock(ResponseFaker::class);
        $responseHandler  = new ResponseHandler($responseFaker);
        $exception        = new Exception('Response validation failed');
        $expectedResponse = $this->createMock(ResponseInterface::class);

        $responseFaker->expects(self::once())
            ->method('handleException')
            ->with(self::callback(static fn ($e): bool => $e instanceof ValidationException), 'application/json')
            ->willReturn($expectedResponse);

        $response = $responseHandler->handleInvalidResponse($exception, 'application/json');

        self::assertSame($expectedResponse, $response);
    }
}
