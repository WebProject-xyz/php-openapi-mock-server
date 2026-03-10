<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\Schema;
use Codeception\Test\Unit;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerRegistry;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\ObjectFaker;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\SchemaFaker;

class BugFixTest extends Unit
{
    public function testSchemaFakerHandlesStdClassFromSerializableData(): void
    {
        $fakerRegistry  = new FakerRegistry();
        $schemaFaker    = new SchemaFaker($fakerRegistry);

        // This schema has properties but no type, triggering the fallback in SchemaFaker
        $schema = new Schema([
            'properties' => [
                'foo' => ['type' => 'string'],
            ],
        ]);

        $options = new Options();
        $options->setAlwaysFakeOptionals(true);

        $result = $schemaFaker->generate($schema, $options);

        self::assertArrayHasKey('foo', $result);
    }

    public function testObjectFakerHandlesStdClassProperties(): void
    {
        $fakerRegistry  = new FakerRegistry();
        $objectFaker    = new ObjectFaker();

        // Create a schema and manually inject stdClass properties
        $schema = new Schema([
            'type'       => 'object',
            'properties' => (object) [
                'foo' => (object) ['type' => 'string'],
            ],
        ]);

        $options = new Options();
        $options->setAlwaysFakeOptionals(true);

        $result = $objectFaker->generate($schema, $options, $fakerRegistry);

        self::assertArrayHasKey('foo', $result);
    }
}
