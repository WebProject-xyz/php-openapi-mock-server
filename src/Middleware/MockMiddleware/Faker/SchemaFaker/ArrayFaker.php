<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

use function array_map;
use function array_unique;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use function count;
use function range;
use Webmozart\Assert\Assert;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;

/** @internal */
final class ArrayFaker implements FakerInterface
{
    /** @return array<mixed>|null */
    public function generate(Schema $schema, Options $options, FakerRegistry $fakerRegistry, FakerContext $fakerContext): ?array
    {
        $useStaticStrategy = MockStrategy::STATIC === $options->getStrategy();

        if ($useStaticStrategy) {
            if (null !== $schema->example) {
                return (array) $schema->example;
            }

            if (null !== $schema->default) {
                return (array) $schema->default;
            }

            if ($schema->nullable) {
                return null;
            }
        }

        $minItems = $schema->minItems ?? ($useStaticStrategy ? 1 : 0);
        $maxItems = $schema->maxItems ?? ($useStaticStrategy ? $minItems : 10);

        if (null !== $options->getMinItems() && $minItems < $options->getMinItems()) {
            $minItems = $options->getMinItems();
        }

        if (null !== $options->getMaxItems() && $maxItems > $options->getMaxItems()) {
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
            static fn (): mixed => $fakerRegistry->generate($itemsSchema, $options, $fakerContext),
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
