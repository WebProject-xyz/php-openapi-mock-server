<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

use function array_key_exists;
use function array_keys;
use function array_reverse;
use cebe\openapi\spec\Schema;
use Faker\Provider\Base;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function md5;
use function reset;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;

/** @internal */
final class SchemaFaker
{
    /** @var array<string, array<mixed>> */
    private static array $resolvedCache = [];

    public function __construct(private readonly FakerRegistry $fakerRegistry)
    {
    }

    public function generate(Schema $schema, Options $options, FakerContext $fakerContext): mixed
    {
        $serializable = $schema->getSerializableData();
        $encoded      = json_encode($serializable);
        if (false === $encoded) {
            return [];
        }

        $cacheKey = md5($encoded . $fakerContext->getContext());

        if (!isset(self::$resolvedCache[$cacheKey])) {
            /** @var array<mixed> $schemaData */
            $schemaData                     = json_decode($encoded, true);
            self::$resolvedCache[$cacheKey] = $this->resolveOfConstraints($schemaData, $options);
        }

        $resolvedData   = self::$resolvedCache[$cacheKey];
        $resolvedSchema = new Schema($resolvedData);

        // Directly dispatch to known fakers to avoid infinite loop via FakerRegistry::generate()
        $type = $resolvedSchema->type;
        if (is_array($type)) {
            $type = reset($type);
        }

        if (is_string($type)) {
            switch ($type) {
                case 'string':
                    return (new StringFaker())->generate($resolvedSchema, $options, $this->fakerRegistry, $fakerContext);
                case 'integer':
                case 'number':
                    return (new NumberFaker())->generate($resolvedSchema, $options, $this->fakerRegistry, $fakerContext);
                case 'boolean':
                    return (new BooleanFaker())->generate($resolvedSchema, $options, $this->fakerRegistry, $fakerContext);
                case 'array':
                    return (new ArrayFaker())->generate($resolvedSchema, $options, $this->fakerRegistry, $fakerContext);
                case 'object':
                    return (new ObjectFaker())->generate($resolvedSchema, $options, $this->fakerRegistry, $fakerContext);
            }
        }

        // Fallback for objects with properties but no type
        if (!empty($resolvedSchema->properties)) {
            return (new ObjectFaker())->generate($resolvedSchema, $options, $this->fakerRegistry, $fakerContext);
        }

        return [];
    }

    /**
     * @param array<mixed> $schema
     *
     * @return array<mixed>
     */
    private function resolveOfConstraints(array $schema, Options $options): array
    {
        $useStaticStrategy = MockStrategy::STATIC === $options->getStrategy();

        // Handle complex constraints at the current level
        if (isset($schema['oneOf'])) {
            $subSchemas = $schema['oneOf'];
            $subSchema  = $useStaticStrategy ? reset($subSchemas) : Base::randomElement($subSchemas);
            unset($schema['oneOf']);
            $schema = $this->merge($schema, $this->resolveOfConstraints($subSchema, $options));
        }

        if (isset($schema['anyOf'])) {
            $subSchemas = $schema['anyOf'];
            $subSchema  = $useStaticStrategy ? reset($subSchemas) : Base::randomElement($subSchemas);
            unset($schema['anyOf']);
            $schema = $this->merge($schema, $this->resolveOfConstraints($subSchema, $options));
        }

        if (isset($schema['allOf'])) {
            $allSubSchemas = $schema['allOf'];
            unset($schema['allOf']);
            foreach (array_reverse($allSubSchemas) as $subSchema) {
                $schema = $this->merge($schema, $this->resolveOfConstraints($subSchema, $options));
            }
        }

        // Recurse into properties and items if they exist
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $name => $property) {
                if (is_array($property)) {
                    $schema['properties'][$name] = $this->resolveOfConstraints($property, $options);
                }
            }
        }

        if (isset($schema['items']) && is_array($schema['items'])) {
            $schema['items'] = $this->resolveOfConstraints($schema['items'], $options);
        }

        return $schema;
    }

    /**
     * @param array<mixed> $firstArray
     * @param array<mixed> $secondArray
     *
     * @return array<mixed>
     */
    private function merge(array $firstArray, array $secondArray): array
    {
        foreach (array_keys($secondArray) as $key) {
            if (!is_array($secondArray[$key])) {
                $firstArray[$key] = $secondArray[$key];

                continue;
            }

            if (!array_key_exists($key, $firstArray) || !is_array($firstArray[$key])) {
                $firstArray[$key] = $secondArray[$key];
                continue;
            }

            // Merge nested arrays (like properties)
            /** @var array<mixed> $secondSubArray */
            $secondSubArray = $secondArray[$key];
            foreach ($secondSubArray as $bk => $bv) {
                if (is_string($bk)) {
                    $firstArray[$key][$bk] = $bv;
                } else {
                    $firstArray[$key][] = $bv;
                }
            }
        }

        return $firstArray;
    }
}
