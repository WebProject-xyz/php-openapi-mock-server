<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

use cebe\openapi\spec\Schema;
use Faker\Provider\Base;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Utils\NumberUtils;

use function is_bool;
use function is_numeric;
use function mt_getrandmax;
use function mt_rand;
use function reset;

use const PHP_INT_MAX;

/** @internal */
final class NumberFaker implements FakerInterface
{
    public function generate(Schema $schema, Options $options, FakerRegistry $fakerRegistry, FakerContext $fakerContext): int|float|null
    {
        if ($options->getStrategy() === MockStrategy::STATIC) {
            return $this->generateStatic($schema);
        }

        return $this->generateDynamic($schema);
    }

    private function generateDynamic(Schema $schema): int|float
    {
        if (! empty($schema->enum)) {
            $value = Base::randomElement($schema->enum);

            return $schema->type === 'integer' ? (int) $value : (float) $value;
        }

        // Default to positive numbers if no minimum is specified
        $minimum    = $schema->minimum ?? 0;
        $maximum    = $schema->maximum ?? mt_getrandmax();
        $multipleOf = $schema->multipleOf ?? 1;

        if (is_numeric($schema->exclusiveMinimum)) {
            $minimum = (float) $schema->exclusiveMinimum;
            if ($schema->type !== 'integer') {
                $minimum += 0.0001;
            } else {
                $minimum = (int) $minimum + 1;
            }
        } elseif ($schema->exclusiveMinimum === true) {
            ++$minimum;
        }

        if (is_numeric($schema->exclusiveMaximum)) {
            $maximum = (float) $schema->exclusiveMaximum;
            if ($schema->type !== 'integer') {
                $maximum -= 0.0001;
            } else {
                $maximum = (int) $maximum - 1;
            }
        } elseif ($schema->exclusiveMaximum === true) {
            --$maximum;
        }

        if ($schema->type === 'integer') {
            // Base::numberBetween can return negative values if $min is not set or Faker version is old
            return mt_rand((int) $minimum, (int) $maximum) * $multipleOf;
        }

        return Base::randomFloat(11, (float) $minimum, (float) $maximum) * $multipleOf;
    }

    private function generateStatic(Schema $schema): int|float|null
    {
        if (! empty($schema->default)) {
            return $schema->type === 'integer' ? (int) $schema->default : (float) $schema->default;
        }

        if ($schema->example !== null) {
            return $schema->type === 'integer' ? (int) $schema->example : (float) $schema->example;
        }

        if ($schema->nullable) {
            return null;
        }

        if (! empty($schema->enum)) {
            $enums = $schema->enum;
            $value = reset($enums);

            return $schema->type === 'integer' ? (int) $value : (float) $value;
        }

        return $this->generateStaticFromFormat($schema);
    }

    private function generateStaticFromFormat(Schema $schema): int|float
    {
        $minimum          = $schema->minimum;
        $maximum          = $schema->maximum;
        $multipleOf       = $schema->multipleOf;
        
        $exclusiveMinimum = is_bool($schema->exclusiveMinimum) ? $schema->exclusiveMinimum : null;
        $exclusiveMaximum = is_bool($schema->exclusiveMaximum) ? $schema->exclusiveMaximum : null;

        switch ($schema->format) {
            case 'int32':
                return (int) NumberUtils::ensureRange(0, $minimum, $maximum, $exclusiveMinimum, $exclusiveMaximum, $multipleOf);

            case 'int64':
                return (int) NumberUtils::ensureRange(0, $minimum, $maximum, $exclusiveMinimum, $exclusiveMaximum, $multipleOf);

            case 'float':
            case 'double':
                return (float) NumberUtils::ensureRange(0, $minimum, $maximum, $exclusiveMinimum, $exclusiveMaximum, $multipleOf);

            case null:
                $number = NumberUtils::ensureRange(0, $minimum, $maximum, $exclusiveMinimum, $exclusiveMaximum, $multipleOf);

                return $schema->type === 'number' ? (float) $number : (int) $number;

            default:
                $number = NumberUtils::ensureRange(0, $minimum, $maximum, $exclusiveMinimum, $exclusiveMaximum, $multipleOf);

                return (int) $number;
        }
    }
}
