<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\Schema;
use Codeception\Test\Unit;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerContext;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerRegistry;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\NumberFaker;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class NumberFakerTest extends Unit
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

    public function testGenerateWithConstraints(): void
    {
        $schema = new Schema([
            'type'    => 'integer',
            'minimum' => 10,
            'maximum' => 20,
        ]);
        $options = new Options();

        $numberFaker = new NumberFaker();
        $result      = $numberFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertGreaterThanOrEqual(10, $result);
        self::assertLessThanOrEqual(20, $result);
    }

    public function testGenerateWithExclusiveNumbers(): void
    {
        $schema = new Schema([
            'type'             => 'integer',
            'minimum'          => 10,
            'exclusiveMinimum' => true,
            'maximum'          => 12,
            'exclusiveMaximum' => true,
        ]);
        $options = new Options();

        $numberFaker = new NumberFaker();
        $result      = $numberFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertSame(11, $result);
    }

    public function testGenerateWithMultipleOf(): void
    {
        $schema = new Schema([
            'type'       => 'integer',
            'minimum'    => 1,
            'maximum'    => 10,
            'multipleOf' => 5,
        ]);
        $options = new Options();

        $numberFaker = new NumberFaker();
        $result      = $numberFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertTrue(0 === $result % 5);
    }

    public function testGenerateStatic(): void
    {
        $schema  = new Schema(['type' => 'integer', 'default' => 42]);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $numberFaker = new NumberFaker();
        $result      = $numberFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertSame(42, $result);
    }

    public function testGenerateStaticExample(): void
    {
        $schema  = new Schema(['type' => 'integer', 'example' => 1337]);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $numberFaker = new NumberFaker();
        $result      = $numberFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertSame(1337, $result);
    }

    public function testGenerateStaticNullable(): void
    {
        $schema  = new Schema(['type' => 'integer', 'nullable' => true]);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $numberFaker = new NumberFaker();
        $result      = $numberFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertNull($result);
    }

    public function testGenerateDynamicEnum(): void
    {
        $schema  = new Schema(['type' => 'integer', 'enum' => [1, 2, 3]]);
        $options = new Options();

        $numberFaker = new NumberFaker();
        $result      = $numberFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertContains($result, [1, 2, 3]);
    }

    public function testGenerateStaticFormats(): void
    {
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);
        $numberFaker = new NumberFaker();

        $schemaInt32 = new Schema(['type' => 'integer', 'format' => 'int32']);
        self::assertIsInt($numberFaker->generate($schemaInt32, $options, $this->fakerRegistry, FakerContext::response()));

        $schemaFloat = new Schema(['type' => 'number', 'format' => 'float']);
        self::assertIsFloat($numberFaker->generate($schemaFloat, $options, $this->fakerRegistry, FakerContext::response()));
    }
}
