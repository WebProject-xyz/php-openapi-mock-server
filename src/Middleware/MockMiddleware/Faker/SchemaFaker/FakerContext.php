<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

/** @internal */
final readonly class FakerContext
{
    public const string REQUEST  = 'request';

    public const string RESPONSE = 'response';

    /**
     * @param array<string, mixed> $pathParameters
     */
    public function __construct(
        private string $context = self::RESPONSE,
        private array $pathParameters = []
    ) {
    }

    /**
     * @param array<string, mixed> $pathParameters
     */
    public static function request(array $pathParameters = []): self
    {
        return new self(self::REQUEST, $pathParameters);
    }

    /**
     * @param array<string, mixed> $pathParameters
     */
    public static function response(array $pathParameters = []): self
    {
        return new self(self::RESPONSE, $pathParameters);
    }

    public function isRequest(): bool
    {
        return self::REQUEST === $this->context;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPathParameters(): array
    {
        return $this->pathParameters;
    }
}
