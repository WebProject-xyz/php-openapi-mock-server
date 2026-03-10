<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use Codeception\Test\Unit;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ResponseValidator as PsrResponseValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\ResponseValidator;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\ResponseValidatorResult;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class ResponseValidatorTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    private MockObject&ValidatorBuilder $validatorBuilder;

    private MockObject&PsrResponseValidator $psrResponseValidator;

    private ResponseValidator $responseValidator;

    protected function _before(): void
    {
        $this->validatorBuilder     = $this->createMock(ValidatorBuilder::class);
        $this->psrResponseValidator = $this->createMock(PsrResponseValidator::class);
        $this->validatorBuilder->method('getResponseValidator')->willReturn($this->psrResponseValidator);

        $this->responseValidator = new ResponseValidator($this->validatorBuilder);
    }

    public function testParseReturnsValidResultWithoutValidation(): void
    {
        $response         = $this->createMock(ResponseInterface::class);
        $operationAddress = new OperationAddress('/test', 'get');

        $responseValidatorResult = $this->responseValidator->parse($response, $operationAddress, false);

        self::assertInstanceOf(ResponseValidatorResult::class, $responseValidatorResult);
        self::assertTrue($responseValidatorResult->isValid());
    }

    public function testParseReturnsErrorWhenValidationFails(): void
    {
        $response         = $this->createMock(ResponseInterface::class);
        $operationAddress = new OperationAddress('/test', 'get');

        $runtimeException = new RuntimeException('Response Validation Error');
        $this->psrResponseValidator->method('validate')->willThrowException($runtimeException);

        $responseValidatorResult = $this->responseValidator->parse($response, $operationAddress, true);

        self::assertInstanceOf(ResponseValidatorResult::class, $responseValidatorResult);
        self::assertFalse($responseValidatorResult->isValid());
        self::assertSame($runtimeException, $responseValidatorResult->getException());
    }
}
