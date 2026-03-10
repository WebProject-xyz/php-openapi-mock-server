<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\OpenApi;
use Codeception\Test\Unit;
use League\OpenAPIValidation\PSR7\ServerRequestValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\RequestValidator;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\RequestValidatorResult;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class RequestValidatorTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    private MockObject&ValidatorBuilder $validatorBuilder;

    private MockObject&ServerRequestValidator $serverRequestValidator;

    private RequestValidator $requestValidator;

    protected function _before(): void
    {
        $this->validatorBuilder       = $this->createMock(ValidatorBuilder::class);
        $this->serverRequestValidator = $this->createMock(ServerRequestValidator::class);
        $this->validatorBuilder->method('getServerRequestValidator')->willReturn($this->serverRequestValidator);

        $this->requestValidator = new RequestValidator($this->validatorBuilder);
    }

    public function testParseReturnsResultWithoutValidation(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri     = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/test');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');

        $openApi = new OpenApi(['paths' => []]);
        $this->serverRequestValidator->method('getSchema')->willReturn($openApi);

        $requestValidatorResult = $this->requestValidator->parse($request, false);

        self::assertInstanceOf(RequestValidatorResult::class, $requestValidatorResult);
        self::assertTrue($requestValidatorResult->isValid());
        self::assertSame($openApi, $requestValidatorResult->getSchema());
        self::assertSame('/test', $requestValidatorResult->getOperationAddress()->path());
    }

    public function testParseReturnsErrorWhenValidationFails(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri     = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn('/test');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');

        $runtimeException = new RuntimeException('Validation Error');
        $this->serverRequestValidator->method('validate')->willThrowException($runtimeException);

        $openApi = new OpenApi(['paths' => []]);
        $this->serverRequestValidator->method('getSchema')->willReturn($openApi);

        $requestValidatorResult = $this->requestValidator->parse($request, true);

        self::assertInstanceOf(RequestValidatorResult::class, $requestValidatorResult);
        self::assertFalse($requestValidatorResult->isValid());
        self::assertSame($runtimeException, $requestValidatorResult->getException());
    }
}
