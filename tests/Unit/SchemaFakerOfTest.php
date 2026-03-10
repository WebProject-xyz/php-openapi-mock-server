<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\Schema;
use Codeception\Test\Unit;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerContext;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerRegistry;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\SchemaFaker;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class SchemaFakerOfTest extends Unit
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

    public function testResolveOfConstraintsNested(): void
    {
        $schema = new Schema([
            'allOf' => [
                ['type' => 'object', 'properties' => ['foo' => ['type' => 'string']]],
                ['type' => 'object', 'properties' => ['bar' => ['type' => 'integer']]],
            ],
        ]);
        $options = new Options();
        $options->setAlwaysFakeOptionals(true);

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());

        self::assertIsArray($result);
        self::assertArrayHasKey('foo', $result);
        self::assertArrayHasKey('bar', $result);
    }

    public function testResolveOfConstraintsAnyOf(): void
    {
        $schema = new Schema([
            'anyOf' => [
                ['type' => 'object', 'properties' => ['foo' => ['type' => 'string']]],
                ['type' => 'object', 'properties' => ['bar' => ['type' => 'integer']]],
            ],
        ]);
        $options = new Options();
        $options->setAlwaysFakeOptionals(true);

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());

        self::assertIsArray($result);
        self::assertTrue(isset($result['foo']) || isset($result['bar']));
    }
}
