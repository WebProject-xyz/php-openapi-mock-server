<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

use cebe\openapi\spec\Schema;
use Faker\Provider\Base;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;

use function array_key_exists;
use function array_keys;
use function array_reverse;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function md5;
use function reset;

/** @internal */
final class SchemaFaker
{
    /** @var array<string, array<mixed>> */
    private static array $resolvedCache = [];

    public function __construct(private readonly FakerRegistry $fakerRegistry)
    {
    }

    public function generate(Schema $schema, Options $options, FakerContext $fakerContext = FakerContext::RESPONSE): mixed
    {
        $encoded = json_encode($schema->getSerializableData());
        if ($encoded === false) {
            return [];
        }

        /** @var array<mixed> $schemaData */
        $schemaData = json_decode($encoded, true);
        $cacheKey   = md5($encoded . $fakerContext->value);

        if (! isset(self::$resolvedCache[$cacheKey])) {
            self::$resolvedCache[$cacheKey] = $this->resolveOfConstraints($schemaData, $options);
        }

        $resolvedSchema = new Schema(self::$resolvedCache[$cacheKey]);

        // If it's still complex after resolution (should not happen if resolveOfConstraints is thorough)
        // or if it's a known base type, we delegate back to the registry.
        // BUT we must avoid calling ourselves again if the registry dispatches back to us.
        
        $type = $resolvedSchema->type;
        if (is_array($type)) {
            $type = reset($type);
        }

        if (is_string($type) && $type !== 'unknown') {
            // This will dispatch to StringFaker, NumberFaker, etc.
            return $this->fakerRegistry->generate($resolvedSchema, $options, $fakerContext);
        }

        // Fallback for objects with properties but no type
        if (! empty($resolvedSchema->properties)) {
            $data = self::$resolvedCache[$cacheKey];
            $data['type'] = 'object';
            return $this->fakerRegistry->generate(new Schema($data), $options, $fakerContext);
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
        $useStaticStrategy = $options->getStrategy() === MockStrategy::STATIC;
        $copy              = $schema;

        foreach (array_keys($copy) as $key) {
            if ($key === 'oneOf') {
                /** @var array<mixed> $subSchema */
                $subSchema = $useStaticStrategy ? reset($copy[$key]) : Base::randomElement($copy[$key]);
                unset($schema['oneOf'], $copy['oneOf']);
                $resolvedSubSchema = $this->resolveOfConstraints($subSchema, $options);

                $schema = $this->merge($schema, $resolvedSubSchema);
            } elseif ($key === 'allOf') {
                /** @var array<array<mixed>> $allSubSchemas */
                $allSubSchemas = $copy[$key];
                unset($schema['allOf'], $copy['allOf']);
                foreach (array_reverse($allSubSchemas) as $subSchema) {
                    $resolvedSubSchema = $this->resolveOfConstraints($subSchema, $options);

                    $schema = $this->merge($schema, $resolvedSubSchema);
                }
            } elseif ($key === 'anyOf') {
                /** @var array<mixed> $subSchema */
                $subSchema = $useStaticStrategy ? reset($copy[$key]) : Base::randomElement($copy[$key]);
                unset($schema['anyOf'], $copy['anyOf']);
                $resolvedSubSchema = $this->resolveOfConstraints($subSchema, $options);

                $schema = $this->merge($schema, $resolvedSubSchema);
            } elseif (is_array($copy[$key])) {
                /** @var array<mixed> $subSchemaArray */
                $subSchemaArray = $copy[$key];
                $schema[$key]   = $this->merge($this->resolveOfConstraints($subSchemaArray, $options), (array) ($schema[$key] ?? []));
            }
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
            if (! is_array($secondArray[$key])) {
                $firstArray[$key] = $secondArray[$key];

                continue;
            }

            if (! array_key_exists($key, $firstArray) || ! is_array($firstArray[$key])) {
                $firstArray[$key] = [];
            }

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
