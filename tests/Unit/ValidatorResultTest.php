<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\OpenApi;
use Codeception\Test\Unit;
use League\OpenAPIValidation\PSR7\OperationAddress;
use RuntimeException;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\RequestValidatorResult;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator\ResponseValidatorResult;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class ValidatorResultTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testRequestValidatorResult(): void
    {
        $openApi            = new OpenApi(['paths' => []]);
        $operationAddress   = new OperationAddress('/test', 'get');
        $runtimeException   = new RuntimeException('Error');

        $result = new RequestValidatorResult($openApi, $operationAddress, $runtimeException);

        self::assertSame($openApi, $result->getSchema());
        self::assertSame($operationAddress, $result->getOperationAddress());
        self::assertSame($runtimeException, $result->getException());
        self::assertFalse($result->isValid());

        $other = new RequestValidatorResult($openApi, $operationAddress, $runtimeException);
        self::assertTrue($result->equals($other));
    }

    public function testResponseValidatorResult(): void
    {
        $runtimeException = new RuntimeException('Error');
        $result           = new ResponseValidatorResult($runtimeException);

        self::assertSame($runtimeException, $result->getException());
        self::assertFalse($result->isValid());

        $other = new ResponseValidatorResult($runtimeException);
        self::assertTrue($result->equals($other));
    }
}
