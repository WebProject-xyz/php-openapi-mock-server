<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use Webmozart\Assert\Assert;

use function array_map;
use function array_unique;
use function count;
use function range;

/** @internal */
final class ArrayFaker implements FakerInterface
{
    /** @return array<mixed>|null */
    public function generate(Schema $schema, Options $options, FakerRegistry $fakerRegistry, FakerContext $fakerContext): ?array
    {
        $useStaticStrategy = $options->getStrategy() === MockStrategy::STATIC;

        if ($useStaticStrategy) {
            if ($schema->example !== null) {
                return (array) $schema->example;
            }

            if ($schema->default !== null) {
                return (array) $schema->default;
            }

            if ($schema->nullable) {
                return null;
            }
        }

        $minItems = $schema->minItems ?? ($useStaticStrategy ? 1 : 0);
        $maxItems = $schema->maxItems ?? ($useStaticStrategy ? $minItems : 10);

        if ($options->getMinItems() !== null && $minItems < $options->getMinItems()) {
            $minItems = $options->getMinItems();
        }

        if ($options->getMaxItems() !== null && $maxItems > $options->getMaxItems()) {
            $maxItems = $options->getMaxItems();

            if ($minItems > $maxItems) {
                $minItems = $maxItems;
            }
        }

        $count = $useStaticStrategy ? $minItems : random_int($minItems, $maxItems);

        $itemsSchema = $schema->items;
        if ($itemsSchema instanceof Reference) {
            $itemsSchema = $itemsSchema->resolve();
        }

        Assert::isInstanceOf($itemsSchema, Schema::class);

        $fakeData = array_map(
            fn (): mixed => $fakerRegistry->generate($itemsSchema, $options, $fakerContext),
            $count > 0 ? range(1, $count) : []
        );

        if ($schema->uniqueItems ?? false) {
            $fakeData = array_unique($fakeData, SORT_REGULAR);
            while (count($fakeData) < $count) {
                $fakeData[] = $fakerRegistry->generate($itemsSchema, $options, $fakerContext);
                $fakeData   = array_unique($fakeData, SORT_REGULAR);
            }
        }

        return $fakeData;
    }
}
