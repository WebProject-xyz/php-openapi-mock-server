<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker;

use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Schema;
use League\OpenAPIValidation\PSR7 as LeagueOpenAPI;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Exception\NoPath;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Exception\NoRequest;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Exception\NoResponse;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Exception\NoSchema;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerContext;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerRegistry;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Utils\HttpMethod;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function array_keys;

final class OpenAPIFaker
{
    private OpenApi $openApi;

    private readonly Options $options;

    private readonly FakerRegistry $fakerRegistry;

    private function __construct()
    {
        $this->options       = new Options();
        $this->fakerRegistry = new FakerRegistry();
    }

    /**
     * @throws TypeErrorException
     * @throws UnresolvableReferenceException
     */
    public static function createFromJson(string $json): self
    {
        $instance          = new self();
        $instance->openApi = (new LeagueOpenAPI\SchemaFactory\JsonFactory($json))->createSchema();

        return $instance;
    }

    /**
     * @throws TypeErrorException
     * @throws UnresolvableReferenceException
     */
    public static function createFromYaml(string $yaml): self
    {
        $instance          = new self();
        $instance->openApi = (new LeagueOpenAPI\SchemaFactory\YamlFactory($yaml))->createSchema();

        return $instance;
    }

    public static function createFromSchema(OpenApi $openApi): self
    {
        $instance          = new self();
        $instance->openApi = $openApi;

        return $instance;
    }

    /**
     * @param array<string, mixed> $pathParameters
     */
    public function mockRequest(
        string $path,
        string $method,
        string $contentType = 'application/json',
        array $pathParameters = []
    ): mixed {
        $mediaType = $this->findContentForRequest($path, HttpMethod::fromString($method), $contentType);

        return $this->fakerRegistry->getRequestFaker()->generate(
            $mediaType, 
            $this->options, 
            $this->fakerRegistry, 
            FakerContext::request($pathParameters)
        );
    }

    /**
     * @param array<string, mixed> $pathParameters
     */
    public function mockRequestForExample(
        string $path,
        string $method,
        string $exampleName,
        string $contentType = 'application/json',
        array $pathParameters = []
    ): mixed {
        $mediaType = $this->findContentForRequest($path, HttpMethod::fromString($method), $contentType);

        return $this->fakerRegistry->getRequestFaker()->generate(
            $mediaType, 
            $this->options, 
            $this->fakerRegistry, 
            FakerContext::request($pathParameters),
            $exampleName
        );
    }

    /**
     * @param array<string, mixed> $pathParameters
     */
    public function mockResponse(
        string $path,
        string $method,
        string $statusCode = '200',
        string $contentType = 'application/json',
        array $pathParameters = []
    ): mixed {
        $mediaType = $this->findContentForResponse($path, HttpMethod::fromString($method), $statusCode, $contentType);

        return $this->fakerRegistry->getResponseFaker()->generate(
            $mediaType, 
            $this->options, 
            $this->fakerRegistry, 
            FakerContext::response($pathParameters)
        );
    }

    /**
     * @param array<string, mixed> $pathParameters
     */
    public function mockResponseForExample(
        string $path,
        string $method,
        string $exampleName,
        string $statusCode = '200',
        string $contentType = 'application/json',
        array $pathParameters = []
    ): mixed {
        $mediaType = $this->findContentForResponse($path, HttpMethod::fromString($method), $statusCode, $contentType);

        return $this->fakerRegistry->getResponseFaker()->generate(
            $mediaType, 
            $this->options, 
            $this->fakerRegistry, 
            FakerContext::response($pathParameters),
            $exampleName
        );
    }

    /** @throws NoSchema */
    public function mockComponentSchema(string $schemaName): mixed
    {
        $schema = $this->findComponentSchema($schemaName);

        return $this->fakerRegistry->getSchemaFaker()->generate($schema, $this->options, FakerContext::response());
    }

    /** @throws NoSchema */
    public function mockComponentSchemaForExample(string $schemaName): mixed
    {
        $schema = $this->findComponentSchema($schemaName);

        return $schema->example;
    }

    /** @param array{minItems?:?int, maxItems?:?int, alwaysFakeOptionals?:bool, strategy?:string|MockStrategy} $options */
    public function setOptions(array $options): self
    {
        if (array_key_exists('minItems', $options)) {
            $value = $options['minItems'];
            Assert::nullOrInteger($value);
            $this->options->setMinItems($value);
        }

        if (array_key_exists('maxItems', $options)) {
            $value = $options['maxItems'];
            Assert::nullOrInteger($value);
            $this->options->setMaxItems($value);
        }

        if (array_key_exists('alwaysFakeOptionals', $options)) {
            $value = $options['alwaysFakeOptionals'];
            Assert::boolean($value);
            $this->options->setAlwaysFakeOptionals($value);
        }

        if (array_key_exists('strategy', $options)) {
            $value = $options['strategy'];
            if ($value instanceof MockStrategy) {
                $this->options->setStrategy($value);
            } else {
                Assert::string($value);
                $this->options->setStrategy(MockStrategy::from($value));
            }
        }

        return $this;
    }

    public function hasResponse(
        string $path,
        string $method,
        string $statusCode = '200',
    ): bool {
        try {
            $operation = $this->findOperation($path, HttpMethod::fromString($method));
        } catch (NoPath) {
            return false;
        }

        return $operation->responses !== null && $operation->responses->hasResponse($statusCode);
    }

    /**
     * @return list<string>
     */
    public function getAvailableResponseContentTypes(
        string $path,
        string $method,
        string $statusCode = '200',
    ): array {
        $operation = $this->findOperation($path, HttpMethod::fromString($method));

        if ($operation->responses === null || ! $operation->responses->hasResponse($statusCode)) {
            return [];
        }

        /** @var Response $response */
        $response = $operation->responses->getResponse($statusCode);

        return array_keys($response->content);
    }

    private function findOperation(string $path, HttpMethod $httpMethod): Operation
    {
        try {
            $operation = (new LeagueOpenAPI\SpecFinder($this->openApi))
                ->findOperationSpec(new LeagueOpenAPI\OperationAddress($path, $httpMethod->value));
        } catch (LeagueOpenAPI\Exception\NoPath) {
            throw NoPath::forPathAndMethod($path, $httpMethod->value);
        }

        return $operation;
    }

    private function findContentForRequest(
        string $path,
        HttpMethod $httpMethod,
        string $contentType = 'application/json',
    ): MediaType {
        $operation = $this->findOperation($path, $httpMethod);

        if ($operation->requestBody === null) {
            throw NoRequest::forPathAndMethod($path, $httpMethod->value);
        }

        /** @var RequestBody $requestBody */
        $requestBody = $operation->requestBody;
        $contents    = $requestBody->content;

        if (! array_key_exists($contentType, $contents)) {
            throw NoRequest::forPathAndMethodAndContentType($path, $httpMethod->value, $contentType);
        }

        /** @var MediaType $content */
        $content = $contents[$contentType];

        return $content;
    }

    private function findContentForResponse(
        string $path,
        HttpMethod $httpMethod,
        string $statusCode = '200',
        string $contentType = 'application/json',
    ): MediaType {
        $operation = $this->findOperation($path, $httpMethod);

        if ($operation->responses === null) {
            throw NoResponse::forPathAndMethodAndStatusCode($path, $httpMethod->value, $statusCode);
        }

        if (! $operation->responses->hasResponse($statusCode)) {
            throw NoResponse::forPathAndMethodAndStatusCode($path, $httpMethod->value, $statusCode);
        }

        /** @var Response $response */
        $response = $operation->responses->getResponse($statusCode);
        $contents = $response->content;

        if (! array_key_exists($contentType, $contents)) {
            throw NoResponse::forPathAndMethodAndStatusCode($path, $httpMethod->value, $statusCode);
        }

        /** @var MediaType $content */
        $content = $contents[$contentType];

        return $content;
    }

    private function findComponentSchema(string $schemaName): Schema
    {
        if ($this->openApi->components === null) {
            throw NoSchema::forZeroComponents();
        }

        if (! array_key_exists($schemaName, $this->openApi->components->schemas)) {
            throw NoSchema::forComponentName($schemaName);
        }

        $schema = $this->openApi->components->schemas[$schemaName];

        if ($schema instanceof Reference) {
            $schema = $schema->resolve();
        }

        Assert::isInstanceOf($schema, Schema::class);

        return $schema;
    }
}
