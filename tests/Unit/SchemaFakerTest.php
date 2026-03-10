<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\Schema;
use Codeception\Test\Unit;
use function is_int;
use function is_string;
use Webmozart\Assert\Assert;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
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
        $schema       = new Schema(['type' => 'string']);
        $schemaFaker  = new SchemaFaker($this->fakerRegistry);
        $result       = $schemaFaker->generate($schema, new Options());
        self::assertIsString($result);
    }

    public function testGenerateStringWithEnum(): void
    {
        $schema       = new Schema(['type' => 'string', 'enum' => ['A', 'B']]);
        $schemaFaker  = new SchemaFaker($this->fakerRegistry);
        $result       = $schemaFaker->generate($schema, new Options());
        self::assertContains($result, ['A', 'B']);
    }

    public function testGenerateNumber(): void
    {
        $schema       = new Schema(['type' => 'number', 'minimum' => 10, 'maximum' => 20]);
        $schemaFaker  = new SchemaFaker($this->fakerRegistry);
        $result       = $schemaFaker->generate($schema, new Options());
        self::assertIsNumeric($result);
        self::assertGreaterThanOrEqual(10, $result);
        self::assertLessThanOrEqual(20, $result);
    }

    public function testGenerateInteger(): void
    {
        $schema       = new Schema(['type' => 'integer']);
        $schemaFaker  = new SchemaFaker($this->fakerRegistry);
        $result       = $schemaFaker->generate($schema, new Options());
        self::assertIsInt($result);
    }

    public function testGenerateBoolean(): void
    {
        $schema       = new Schema(['type' => 'boolean']);
        $schemaFaker  = new SchemaFaker($this->fakerRegistry);
        $result       = $schemaFaker->generate($schema, new Options());
        self::assertIsBool($result);
    }

    public function testGenerateArray(): void
    {
        $schema = new Schema([
            'type'     => 'array',
            'items'    => ['type' => 'string'],
            'minItems' => 2,
            'maxItems' => 2,
        ]);
        $schemaFaker  = new SchemaFaker($this->fakerRegistry);
        $result       = $schemaFaker->generate($schema, new Options());
        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertIsString($result[0]);
    }

    public function testGenerateStringWithFormats(): void
    {
        $formats = ['date', 'date-time', 'email', 'uuid', 'ipv4', 'ipv6', 'hostname'];
        foreach ($formats as $format) {
            $schema      = new Schema(['type' => 'string', 'format' => $format]);
            $schemaFaker = new SchemaFaker($this->fakerRegistry);
            $result      = $schemaFaker->generate($schema, new Options());
            self::assertIsString($result, 'Failed for format: ' . $format);
        }
    }

    public function testGenerateStringWithRegex(): void
    {
        $schema       = new Schema(['type' => 'string', 'pattern' => '^[0-9]{3}$']);
        $schemaFaker  = new SchemaFaker($this->fakerRegistry);
        $result       = $schemaFaker->generate($schema, new Options());
        Assert::string($result);
        self::assertMatchesRegularExpression('/^\d{3}$/', $result);
    }

    public function testGenerateNumberWithMultipleOf(): void
    {
        $schema       = new Schema(['type' => 'number', 'multipleOf' => 5]);
        $schemaFaker  = new SchemaFaker($this->fakerRegistry);
        $result       = $schemaFaker->generate($schema, new Options());
        Assert::numeric($result);
        self::assertSame(0, (int) $result % 5);
    }

    public function testGenerateBooleanWithAlwaysTrue(): void
    {
        $schema       = new Schema(['type' => 'boolean']);
        $schemaFaker  = new SchemaFaker($this->fakerRegistry);
        $result       = $schemaFaker->generate($schema, new Options());
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
        $schemaFaker  = new SchemaFaker($this->fakerRegistry);
        $result       = $schemaFaker->generate($schema, new Options());
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
        $schemaFaker  = new SchemaFaker($this->fakerRegistry);
        $result       = $schemaFaker->generate($schema, new Options());
        self::assertTrue(is_string($result) || is_int($result));
    }

    public function testGenerateAllOf(): void
    {
        $schema = new Schema([
            'allOf' => [
                ['type' => 'object', 'properties' => ['a' => ['type' => 'string']]],
                ['type' => 'object', 'properties' => ['b' => ['type' => 'integer']]],
            ],
        ]);
        $schemaFaker  = new SchemaFaker($this->fakerRegistry);
        $result       = $schemaFaker->generate($schema, new Options());
        self::assertIsArray($result);
    }

    public function testGenerateWithNoType(): void
    {
        $schema       = new Schema([]);
        $schemaFaker  = new SchemaFaker($this->fakerRegistry);
        $result       = $schemaFaker->generate($schema, new Options());
        self::assertSame([], $result);
    }
}
