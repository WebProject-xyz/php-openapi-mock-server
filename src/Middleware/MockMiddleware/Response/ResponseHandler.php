<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Response;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Exception\ValidationException;

class ResponseHandler
{
    public function __construct(
        private readonly ResponseFaker $responseFaker
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function handleInvalidResponse(Throwable $throwable, string $contentType): ResponseInterface
    {
        return $this->responseFaker->handleException(ValidationException::forViolations($throwable), $contentType);
    }
}
