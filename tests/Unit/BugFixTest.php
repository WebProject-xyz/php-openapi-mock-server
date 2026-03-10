<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\Schema;
use Codeception\Test\Unit;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerContext;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerRegistry;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\ObjectFaker;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\SchemaFaker;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class BugFixTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    private FakerRegistry $fakerRegistry;

    protected function _before(): void
    {
        $this->fakerRegistry = new FakerRegistry();
    }

    public function testSchemaFakerHandlesStdClassFromSerializableData(): void
    {
        // This schema has properties but no type, triggering the fallback in SchemaFaker
        $schema = new Schema([
            'properties' => [
                'foo' => ['type' => 'string'],
            ],
        ]);

        $options = new Options();
        $options->setAlwaysFakeOptionals(true);

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());

        self::assertArrayHasKey('foo', $result);
    }

    public function testObjectFakerHandlesStdClassProperties(): void
    {
        // Create a schema and manually inject stdClass properties
        $schema = new Schema([
            'type'       => 'object',
            'properties' => (object) [
                'foo' => (object) ['type' => 'string'],
            ],
        ]);

        $options = new Options();
        $options->setAlwaysFakeOptionals(true);

        $objectFaker = new ObjectFaker();
        $result      = $objectFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());

        self::assertArrayHasKey('foo', $result);
    }
}
