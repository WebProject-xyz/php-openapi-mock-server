<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Utils;

/** @internal */
final class NumberUtils
{
    /** @return ($sample is int ? int : float) */
    public static function ensureRange(int|float $sample, int|float|null $minimum, int|float|null $maximum, ?bool $exclusiveMinimum = null, ?bool $exclusiveMaximum = null, int|float|null $multipleOf = null): int|float
    {
        if (null === $minimum) {
            $minimum = $sample;
        }

        if (null === $maximum) {
            $maximum = $minimum;
        }

        if ($minimum > $sample) {
            $sample = $minimum;
        }

        if ($maximum < $sample) {
            $sample = $maximum;
        }

        if ($sample === $minimum && true === $exclusiveMinimum) {
            ++$sample;
        }

        if ($sample === $maximum && true === $exclusiveMaximum) {
            --$sample;
        }

        if (null !== $multipleOf && 1 !== $multipleOf) {
            $sample -= $sample % $multipleOf;
        }

        return $sample;
    }
}
