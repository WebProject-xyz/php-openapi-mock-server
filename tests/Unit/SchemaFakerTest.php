<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\Schema;
use Codeception\Test\Unit;
use function is_int;
use function is_string;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerContext;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerRegistry;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\SchemaFaker;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class SchemaFakerTest extends Unit
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

    public function testGenerateString(): void
    {
        $schema  = new Schema(['type' => 'string']);
        $options = new Options();

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());
        self::assertIsString($result);
    }

    public function testGenerateStringWithEnum(): void
    {
        $schema  = new Schema(['type' => 'string', 'enum' => ['foo', 'bar']]);
        $options = new Options();

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());
        self::assertContains($result, ['foo', 'bar']);
    }

    public function testGenerateNumber(): void
    {
        $schema  = new Schema(['type' => 'number']);
        $options = new Options();

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());
        self::assertIsNumeric($result);
    }

    public function testGenerateInteger(): void
    {
        $schema  = new Schema(['type' => 'integer']);
        $options = new Options();

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());
        self::assertIsInt($result);
    }

    public function testGenerateBoolean(): void
    {
        $schema  = new Schema(['type' => 'boolean']);
        $options = new Options();

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());
        self::assertIsBool($result);
    }

    public function testGenerateArray(): void
    {
        $schema = new Schema([
            'type'  => 'array',
            'items' => ['type' => 'string'],
        ]);
        $options = new Options();

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());
        self::assertIsArray($result);
    }

    public function testGenerateStringWithFormats(): void
    {
        $schema  = new Schema(['type' => 'string', 'format' => 'email']);
        $options = new Options();

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());
        self::assertIsString($result);
    }

    public function testGenerateStringWithRegex(): void
    {
        $schema  = new Schema(['type' => 'string', 'pattern' => '^foo$']);
        $options = new Options();

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());
        self::assertSame('foo', $result);
    }

    public function testGenerateNumberWithMultipleOf(): void
    {
        $schema  = new Schema(['type' => 'integer', 'multipleOf' => 5]);
        $options = new Options();

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());
        self::assertTrue(0 === $result % 5);
    }

    public function testGenerateBooleanWithAlwaysTrue(): void
    {
        $schema  = new Schema(['type' => 'boolean', 'default' => true]);
        $options = new Options();

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());
        self::assertIsBool($result);
    }

    public function testGenerateAnyOf(): void
    {
        $schema = new Schema([
            'anyOf' => [
                ['type' => 'string'],
                ['type' => 'integer'],
            ],
        ]);
        $options = new Options();

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());
        self::assertTrue(is_string($result) || is_int($result));
    }

    public function testGenerateOneOf(): void
    {
        $schema = new Schema([
            'oneOf' => [
                ['type' => 'string'],
                ['type' => 'integer'],
            ],
        ]);
        $options = new Options();

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());
        self::assertTrue(is_string($result) || is_int($result));
    }

    public function testGenerateAllOf(): void
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

    public function testGenerateWithNoType(): void
    {
        $schema = new Schema([
            'properties' => [
                'foo' => ['type' => 'string'],
            ],
        ]);
        $options = new Options();
        $options->setAlwaysFakeOptionals(true);

        $schemaFaker = new SchemaFaker($this->fakerRegistry);
        $result      = $schemaFaker->generate($schema, $options, FakerContext::response());
        self::assertIsArray($result);
        self::assertArrayHasKey('foo', $result);
    }
}
