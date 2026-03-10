<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\OpenApi;
use Codeception\Test\Unit;
use League\OpenAPIValidation\PSR7\OperationAddress;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception\ValidationException;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Exception\NoResponse;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Response\ResponseFaker;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class ResponseFakerTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testMockTriesMultipleStatusCodes(): void
    {
        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $streamFactory   = $this->createMock(StreamFactoryInterface::class);
        $options         = new Options();

        $response = $this->createMock(ResponseInterface::class);
        $response->method('withStatus')->willReturnSelf();
        $response->method('withBody')->willReturnSelf();
        $response->method('withAddedHeader')->willReturnSelf();
        $responseFactory->method('createResponse')->willReturn($response);

        $stream = $this->createMock(StreamInterface::class);
        $streamFactory->method('createStream')->willReturn($stream);

        $openApi = new OpenApi([
            'openapi' => '3.0.0',
            'info'    => ['title' => 'Test', 'version' => '1.0'],
            'paths'   => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                                'content'     => [
                                    'application/json' => [
                                        'schema' => ['type' => 'object'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $operationAddress = new OperationAddress('/test', 'get');

        $responseFaker = new ResponseFaker($responseFactory, $streamFactory, $options);

        // Try a non-existent status code (404) then 200
        $result = $responseFaker->mock($openApi, $operationAddress, ['404', '200']);

        self::assertSame($response, $result);
    }

    public function testMockThrowsExceptionIfNoStatusCodeMatches(): void
    {
        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $streamFactory   = $this->createMock(StreamFactoryInterface::class);
        $options         = new Options();

        $openApi = new OpenApi([
            'openapi' => '3.0.0',
            'info'    => ['title' => 'Test', 'version' => '1.0'],
            'paths'   => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => ['description' => 'OK'],
                        ],
                    ],
                ],
            ],
        ]);

        $operationAddress = new OperationAddress('/test', 'get');

        $responseFaker = new ResponseFaker($responseFactory, $streamFactory, $options);

        $this->expectException(NoResponse::class);
        $responseFaker->mock($openApi, $operationAddress, ['404', '500']);
    }

    public function testHandleExceptionReturnsResponse(): void
    {
        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $streamFactory   = $this->createMock(StreamFactoryInterface::class);
        $options         = new Options();

        $response = $this->createMock(ResponseInterface::class);
        $response->method('withStatus')->willReturnSelf();
        $response->method('withBody')->willReturnSelf();
        $response->method('withAddedHeader')->willReturnSelf();
        $responseFactory->method('createResponse')->willReturn($response);

        $stream = $this->createMock(StreamInterface::class);
        $streamFactory->method('createStream')->willReturn($stream);

        $responseFaker       = new ResponseFaker($responseFactory, $streamFactory, $options);
        $validationException = new ValidationException('TYPE', 'TITLE', 'DETAIL', 400);

        $result = $responseFaker->handleException($validationException, 'application/json');
        self::assertSame($response, $result);
    }
}
