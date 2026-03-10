<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\Schema;
use Codeception\Test\Unit;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
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
            'type'             => 'number',
            'minimum'          => 10,
            'maximum'          => 12,
            'exclusiveMinimum' => true,
            'exclusiveMaximum' => true,
        ]);
        $options       = new Options();
        $numberFaker   = new NumberFaker();
        $result        = $numberFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertIsNumeric($result);
        self::assertGreaterThan(10, $result);
        self::assertLessThan(12, $result);
    }

    public function testGenerateWithExclusiveNumbers(): void
    {
        $schema = new Schema([
            'type'             => 'number',
            'exclusiveMinimum' => 10,
            'exclusiveMaximum' => 13,
        ]);
        $options       = new Options();
        $numberFaker   = new NumberFaker();
        $result        = $numberFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertIsNumeric($result);
        self::assertGreaterThan(10, $result);
        self::assertLessThan(13, $result);
    }

    public function testGenerateWithMultipleOf(): void
    {
        $schema = new Schema([
            'type'       => 'integer',
            'multipleOf' => 7,
        ]);
        $options       = new Options();
        $numberFaker   = new NumberFaker();
        $result        = $numberFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertSame(0, (int) $result % 7);
    }

    public function testGenerateStatic(): void
    {
        $schema  = new Schema(['type' => 'integer', 'default' => 42]);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $numberFaker  = new NumberFaker();
        $result       = $numberFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertSame(42, $result);
    }

    public function testGenerateStaticExample(): void
    {
        $schema  = new Schema(['type' => 'number', 'example' => 3.14]);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $numberFaker  = new NumberFaker();
        $result       = $numberFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertSame(3.14, $result);
    }

    public function testGenerateStaticNullable(): void
    {
        $schema  = new Schema(['type' => 'number', 'nullable' => true]);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $numberFaker  = new NumberFaker();
        $result       = $numberFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertNull($result);
    }

    public function testGenerateDynamicEnum(): void
    {
        $schema  = new Schema(['type' => 'integer', 'enum' => [1, 2, 3]]);
        $options = new Options();

        $numberFaker  = new NumberFaker();
        $result       = $numberFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertContains($result, [1, 2, 3]);
    }

    public function testGenerateStaticFormats(): void
    {
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $numberFaker = new NumberFaker();

        $formats = ['int32', 'int64', 'float', 'double'];
        foreach ($formats as $format) {
            $schema = new Schema(['type' => 'number', 'format' => $format]);
            $result = $numberFaker->generate($schema, $options, $this->fakerRegistry);
            self::assertIsNumeric($result);
        }
    }
}
