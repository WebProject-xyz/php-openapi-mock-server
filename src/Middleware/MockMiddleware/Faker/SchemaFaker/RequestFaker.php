<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

use function array_key_exists;
use cebe\openapi\spec\Example;
use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use function reset;
use Webmozart\Assert\Assert;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Exception\NoExample;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;

/** @internal */
final readonly class RequestFaker
{
    /**
     * @return array<mixed>|string|bool|int|float|null
     *
     * @throws NoExample
     */
    public function generate(
        MediaType $mediaType,
        Options $options,
        FakerRegistry $fakerRegistry,
        FakerContext $fakerContext,
        ?string $exampleName = null
    ): array|string|bool|int|float|null {
        $examples = $mediaType->examples;

        if (MockStrategy::STATIC === $options->getStrategy() && [] !== $examples) {
            if (null !== $exampleName) {
                if (!array_key_exists($exampleName, $examples)) {
                    throw NoExample::forRequest($exampleName);
                }

                /** @var Example $example */
                $example = $examples[$exampleName];
            } else {
                /** @var Example $example */
                $example = reset($examples);
            }

            return $example->value;
        }

        $schema = $mediaType->schema;
        if ($schema instanceof Reference) {
            $schema = $schema->resolve();
        }

        Assert::isInstanceOf($schema, Schema::class);

        return $fakerRegistry->getSchemaFaker()->generate($schema, $options, $fakerContext);
    }
}
