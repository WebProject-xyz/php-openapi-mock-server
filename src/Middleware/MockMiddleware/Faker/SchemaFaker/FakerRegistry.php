<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

use cebe\openapi\spec\Schema;
use function is_array;
use function is_string;
use function reset;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;

/** @internal */
final class FakerRegistry
{
    /** @var array<string, FakerInterface|SchemaFaker|RequestFaker|ResponseFaker> */
    private array $fakers;

    public function __construct()
    {
        // Stateless service instances
        $this->fakers = [
            FakerType::STRING->value   => new StringFaker(),
            FakerType::NUMBER->value   => new NumberFaker(),
            FakerType::INTEGER->value  => new NumberFaker(),
            FakerType::BOOLEAN->value  => new BooleanFaker(),
            FakerType::ARRAY->value    => new ArrayFaker(),
            FakerType::OBJECT->value   => new ObjectFaker(),
            FakerType::SCHEMA->value   => new SchemaFaker($this),
            FakerType::REQUEST->value  => new RequestFaker(),
            FakerType::RESPONSE->value => new ResponseFaker(),
        ];
    }

    public function generate(Schema $schema, Options $options, FakerContext $fakerContext): mixed
    {
        $fakerType = $this->resolveType($schema);

        if (isset($this->fakers[$fakerType->value])) {
            $faker = $this->fakers[$fakerType->value];

            if ($faker instanceof FakerInterface) {
                return $faker->generate($schema, $options, $this, $fakerContext);
            }
        }

        // Fallback to SchemaFaker for complex schemas (oneOf, anyOf, allOf) or unknown types
        return $this->getSchemaFaker()->generate($schema, $options, $fakerContext);
    }

    public function getSchemaFaker(): SchemaFaker
    {
        /** @var SchemaFaker $faker */
        $faker = $this->fakers[FakerType::SCHEMA->value];

        return $faker;
    }

    public function getRequestFaker(): RequestFaker
    {
        /** @var RequestFaker $faker */
        $faker = $this->fakers[FakerType::REQUEST->value];

        return $faker;
    }

    public function getResponseFaker(): ResponseFaker
    {
        /** @var ResponseFaker $faker */
        $faker = $this->fakers[FakerType::RESPONSE->value];

        return $faker;
    }

    private function resolveType(Schema $schema): FakerType
    {
        $type = $schema->type;

        if (is_array($type)) {
            $type = reset($type);
        }

        if (is_string($type) && '' !== $type) {
            return FakerType::tryFrom($type) ?? FakerType::UNKNOWN;
        }

        if (!empty($schema->properties)) {
            return FakerType::OBJECT;
        }

        if (null !== $schema->items) {
            return FakerType::ARRAY;
        }

        return FakerType::UNKNOWN;
    }
}
