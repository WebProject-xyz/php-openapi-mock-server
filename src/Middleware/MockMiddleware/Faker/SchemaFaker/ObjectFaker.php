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
use function is_array;

/** @internal */
final class ObjectFaker implements FakerInterface
{
    /** @return array<string, mixed> */
    public function generate(Schema $schema, Options $options, FakerRegistry $fakerRegistry, FakerContext $fakerContext): array
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
            // Ensure at least one property is returned if possible, avoiding empty objects
            $minToSelect = ($requiredKeys === []) ? 1 : 0;
            $count     = random_int($minToSelect, $countKeys);
            if ($count > 0) {
                $indices = (array) array_rand($optionalKeys, $count);
                foreach ($indices as $index) {
                    $selectedOptionalKeys[] = $optionalKeys[$index];
                }
            }
        }

        $allPropertyKeys = array_merge($requiredKeys, $selectedOptionalKeys);
        
        $pathParameters = $fakerContext->getPathParameters();
        // Path parameters should ALWAYS be included if they match a property
        foreach (array_keys($pathParameters) as $paramName) {
            if (in_array($paramName, $propertyKeys, true) && !in_array($paramName, $allPropertyKeys, true)) {
                $allPropertyKeys[] = $paramName;
            }
        }

        $fakeData       = [];

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

            // If we have a path parameter for this key, use it (if it's not a request context)
            if (! $fakerContext->isRequest() && isset($pathParameters[$key])) {
                $val = $pathParameters[$key];
                $type = $property->type;
                if (is_array($type)) {
                    $type = reset($type);
                }
                
                if ($type === 'integer') {
                    $val = (int) $val;
                } elseif ($type === 'number') {
                    $val = (float) $val;
                } elseif ($type === 'boolean') {
                    $val = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                }
                
                $fakeData[$key] = $val;
                continue;
            }

            $fakeData[$key] = $fakerRegistry->generate($property, $options, $fakerContext);
        }

        return $fakeData;
    }
}
