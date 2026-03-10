<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

use cebe\openapi\spec\Schema;
use Faker\Provider\Base;
use function is_bool;
use function is_numeric;
use function mt_getrandmax;
use function mt_rand;
use function reset;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Utils\NumberUtils;

/** @internal */
final class NumberFaker implements FakerInterface
{
    public function generate(Schema $schema, Options $options, FakerRegistry $fakerRegistry, FakerContext $fakerContext): int|float|null
    {
        if (MockStrategy::STATIC === $options->getStrategy()) {
            return $this->generateStatic($schema);
        }

        return $this->generateDynamic($schema);
    }

    private function generateDynamic(Schema $schema): int|float
    {
        if (!empty($schema->enum)) {
            $value = Base::randomElement($schema->enum);

            return 'integer' === $schema->type ? (int) $value : (float) $value;
        }

        // Default to positive numbers if no minimum is specified
        $minimum    = $schema->minimum ?? 0;
        $maximum    = $schema->maximum ?? mt_getrandmax();
        $multipleOf = $schema->multipleOf ?? 1;

        if (is_numeric($schema->exclusiveMinimum)) {
            $minimum = (float) $schema->exclusiveMinimum;
            if ('integer' !== $schema->type) {
                $minimum += 0.0001;
            } else {
                $minimum = (int) $minimum + 1;
            }
        } elseif (true === $schema->exclusiveMinimum) {
            ++$minimum;
        }

        if (is_numeric($schema->exclusiveMaximum)) {
            $maximum = (float) $schema->exclusiveMaximum;
            if ('integer' !== $schema->type) {
                $maximum -= 0.0001;
            } else {
                $maximum = (int) $maximum - 1;
            }
        } elseif (true === $schema->exclusiveMaximum) {
            --$maximum;
        }

        if ('integer' === $schema->type) {
            // Base::numberBetween can return negative values if $min is not set or Faker version is old
            return mt_rand((int) $minimum, (int) $maximum) * $multipleOf;
        }

        return Base::randomFloat(11, (float) $minimum, (float) $maximum) * $multipleOf;
    }

    private function generateStatic(Schema $schema): int|float|null
    {
        if (!empty($schema->default)) {
            return 'integer' === $schema->type ? (int) $schema->default : (float) $schema->default;
        }

        if (null !== $schema->example) {
            return 'integer' === $schema->type ? (int) $schema->example : (float) $schema->example;
        }

        if ($schema->nullable) {
            return null;
        }

        if (!empty($schema->enum)) {
            $enums = $schema->enum;
            $value = reset($enums);

            return 'integer' === $schema->type ? (int) $value : (float) $value;
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

                return 'number' === $schema->type ? (float) $number : (int) $number;

            default:
                $number = NumberUtils::ensureRange(0, $minimum, $maximum, $exclusiveMinimum, $exclusiveMaximum, $multipleOf);

                return (int) $number;
        }
    }
}
