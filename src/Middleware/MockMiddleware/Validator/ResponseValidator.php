<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator;

use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ResponseValidator
{
    public function __construct(
        private readonly ValidatorBuilder $validatorBuilder
    ) {
    }

    public function parse(ResponseInterface $response, OperationAddress $operationAddress, bool $validate): ResponseValidatorResult
    {
        $responseValidator = $this->validatorBuilder->getResponseValidator();

        if ($validate) {
            try {
                $responseValidator->validate($operationAddress, $response);
            } catch (Throwable $th) {
                return new ResponseValidatorResult($th);
            }
        }

        return new ResponseValidatorResult();
    }
}
