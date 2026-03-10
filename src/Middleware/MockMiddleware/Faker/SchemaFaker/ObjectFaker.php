<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

use cebe\openapi\spec\Schema;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;

use function array_keys;
use function array_merge;
use function array_rand;
use function count;
use function in_array;

/** @internal */
final class ObjectFaker implements FakerInterface
{
    /** @return array<string, mixed> */
    public function generate(Schema $schema, Options $options, FakerRegistry $fakerRegistry, FakerContext $fakerContext = FakerContext::RESPONSE): array
    {
        $useStaticStrategy = $options->getStrategy() === MockStrategy::STATIC;

        if ($useStaticStrategy) {
            if ($schema->example !== null) {
                return (array) $schema->example;
            }

            if ($schema->default !== null) {
                return (array) $schema->default;
            }
        }

        $requiredKeys = $schema->required ?? [];
        $propertyKeys = array_keys((array) $schema->properties);

        $optionalKeys         = array_values(array_diff($propertyKeys, $requiredKeys));
        $selectedOptionalKeys = [];

        if ($options->getAlwaysFakeOptionals() || $useStaticStrategy) {
            $selectedOptionalKeys = $optionalKeys;
        } elseif ($optionalKeys !== []) {
            $countKeys = count($optionalKeys);
            $count     = random_int(0, $countKeys);
            if ($count > 0) {
                $selectedOptionalKeys = (array) array_rand(array_flip($optionalKeys), $count);
            }
        }

        $allPropertyKeys = array_merge($requiredKeys, $selectedOptionalKeys);

        $fakeData = [];
        /** @var Schema $property */
        foreach ($schema->properties as $key => $property) {
            if ($fakerContext->isRequest() && ($property->readOnly ?? false)) {
                continue;
            }

            if (! $fakerContext->isRequest() && ($property->writeOnly ?? false)) {
                continue;
            }

            if (! in_array($key, $allPropertyKeys, true)) {
                continue;
            }

            if (! $property instanceof Schema) {
                $property = new Schema((array) $property);
            }

            $fakeData[$key] = $fakerRegistry->generate($property, $options, $fakerContext);
        }

        return $fakeData;
    }
}
