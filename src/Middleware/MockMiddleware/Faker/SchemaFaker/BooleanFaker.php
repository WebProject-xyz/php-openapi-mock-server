<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

use cebe\openapi\spec\Schema;
use Faker\Provider\Base;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;

use function random_int;
use function reset;

/** @internal */
final class BooleanFaker implements FakerInterface
{
    public function generate(Schema $schema, Options $options, FakerRegistry $fakerRegistry): bool|null
    {
        if ($options->getStrategy() === MockStrategy::STATIC) {
            return $this->generateStatic($schema);
        }

        return $this->generateDynamic($schema);
    }

    private function generateDynamic(Schema $schema): bool
    {
        if (! empty($schema->enum)) {
            return (bool) Base::randomElement($schema->enum);
        }

        return random_int(0, 1) < 0.5;
    }

    private function generateStatic(Schema $schema): bool|null
    {
        if (! empty($schema->default)) {
            return (bool) $schema->default;
        }

        if ($schema->example !== null) {
            return (bool) $schema->example;
        }

        if ($schema->nullable) {
            return null;
        }

        if (! empty($schema->enum)) {
            $enums = $schema->enum;

            return (bool) reset($enums);
        }

        return true;
    }
}
