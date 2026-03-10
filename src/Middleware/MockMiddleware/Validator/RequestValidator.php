<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Validator;

use cebe\openapi\spec\OpenApi;
use League\OpenAPIValidation\PSR7\Exception\NoPath;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\PathFinder;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception\RoutingException;

class RequestValidator
{
    public function __construct(
        private readonly ValidatorBuilder $validatorBuilder
    ) {
    }

    public function parse(ServerRequestInterface $serverRequest, bool $validate): RequestValidatorResult
    {
        $serverRequestValidator = $this->validatorBuilder->getServerRequestValidator();

        if ($validate) {
            try {
                $operationAddress = $serverRequestValidator->validate($serverRequest);
                $schema           = $serverRequestValidator->getSchema();

                if (0 === $schema->paths->count()) {
                    return new RequestValidatorResult(
                        $schema,
                        $operationAddress,
                        RoutingException::forNoResourceProvided(NoPath::fromPath($serverRequest->getUri()->getPath()))
                    );
                }

                $pathParams = $operationAddress->parseParams($serverRequest->getUri()->getPath());

                return new RequestValidatorResult($schema, $operationAddress, null, $pathParams);
            } catch (Throwable $th) {
                $schema           = $serverRequestValidator->getSchema();
                $operationAddress = $this->findOperationAddress($schema, $serverRequest);
                $pathParams       = [];
                try {
                    $pathParams = $operationAddress->parseParams($serverRequest->getUri()->getPath());
                } catch (Throwable) {
                }

                return new RequestValidatorResult($schema, $operationAddress, $th, $pathParams);
            }
        }

        $schema           = $serverRequestValidator->getSchema();
        $operationAddress = $this->findOperationAddress($schema, $serverRequest);
        $pathParams       = [];
        try {
            $pathParams = $operationAddress->parseParams($serverRequest->getUri()->getPath());
        } catch (Throwable) {
        }

        return new RequestValidatorResult($schema, $operationAddress, null, $pathParams);
    }

    private function findOperationAddress(OpenApi $openApi, ServerRequestInterface $serverRequest): OperationAddress
    {
        $pathFinder = new PathFinder($openApi, $serverRequest->getUri()->getPath(), $serverRequest->getMethod());
        $paths      = $pathFinder->search();

        return $paths[0] ?? new OperationAddress($serverRequest->getUri()->getPath(), $serverRequest->getMethod());
    }
}
