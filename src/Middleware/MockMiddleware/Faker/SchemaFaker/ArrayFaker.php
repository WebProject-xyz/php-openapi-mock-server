<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

use cebe\openapi\spec\Schema;
use Faker\Provider\Base;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use Webmozart\Assert\Assert;

use function array_unique;
use function count;

use const SORT_REGULAR;

/** @internal */
final class ArrayFaker implements FakerInterface
{
    /** @return array<mixed> */
    public function generate(Schema $schema, Options $options, FakerRegistry $fakerRegistry): array
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

        $minimum = $schema->minItems ?? ($useStaticStrategy ? 1 : 0);
        $maximum = $schema->maxItems ?? ($useStaticStrategy ? $minimum : 10);

        if ($options->getMinItems() && $minimum < $options->getMinItems()) {
            $minimum = $options->getMinItems();
        }

        if ($options->getMaxItems() && $maximum > $options->getMaxItems()) {
            $maximum = $options->getMaxItems();

            if ($minimum > $maximum) {
                $minimum = $maximum;
            }
        }

        $itemSize = $useStaticStrategy ? $minimum : Base::numberBetween($minimum, $maximum);

        $fakeData = [];
        $itemSchema = $schema->items;
        Assert::isInstanceOf($itemSchema, Schema::class);

        for ($i = 0; $i < $itemSize; ++$i) {
            $fakeData[] = $fakerRegistry->generate($itemSchema, $options);

            if (! $schema->uniqueItems) {
                continue;
            }

            /** @var array<int, string|int|float|bool|array<mixed>|null> $fakeData */
            $uniqueData = array_unique($fakeData, SORT_REGULAR);

            if (count($uniqueData) > count($fakeData)) {
                continue;
            }

            $i -= count($fakeData) - count($uniqueData);
            $fakeData = $uniqueData;
        }

        return $fakeData;
    }
}
