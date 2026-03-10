<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

use cebe\openapi\spec\Schema;
use Faker\Provider\Base;
use function reset;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;

/** @internal */
final class BooleanFaker implements FakerInterface
{
    public function generate(Schema $schema, Options $options, FakerRegistry $fakerRegistry, FakerContext $fakerContext): ?bool
    {
        if (MockStrategy::STATIC === $options->getStrategy()) {
            return $this->generateStatic($schema);
        }

        return $this->generateDynamic($schema);
    }

    private function generateDynamic(Schema $schema): bool
    {
        if (!empty($schema->enum)) {
            /** @var bool $value */
            $value = Base::randomElement($schema->enum);

            return $value;
        }

        return Base::randomElement([true, false]);
    }

    private function generateStatic(Schema $schema): ?bool
    {
        if (null !== $schema->default) {
            return (bool) $schema->default;
        }

        if (null !== $schema->example) {
            return (bool) $schema->example;
        }

        if ($schema->nullable) {
            return null;
        }

        if (!empty($schema->enum)) {
            /** @var array<bool> $enums */
            $enums = $schema->enum;

            return (bool) reset($enums);
        }

        return true;
    }
}
