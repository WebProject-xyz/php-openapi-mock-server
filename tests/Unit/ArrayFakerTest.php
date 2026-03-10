<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use function array_unique;
use cebe\openapi\spec\Schema;
use Codeception\Test\Unit;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\ArrayFaker;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerRegistry;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class ArrayFakerTest extends Unit
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

    public function testGenerateStaticWithExample(): void
    {
        $example = ['foo', 'bar'];
        $schema  = new Schema([
            'type'    => 'array',
            'items'   => ['type' => 'string'],
            'example' => $example,
        ]);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $arrayFaker  = new ArrayFaker();
        $result      = $arrayFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertSame($example, $result);
    }

    public function testGenerateWithUniqueItems(): void
    {
        $schema = new Schema([
            'type'        => 'array',
            'items'       => ['type' => 'string', 'enum' => ['A', 'B', 'C']],
            'minItems'    => 3,
            'maxItems'    => 3,
            'uniqueItems' => true,
        ]);
        $options = new Options();

        $arrayFaker  = new ArrayFaker();
        $result      = $arrayFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertCount(3, $result);
        self::assertCount(3, array_unique($result));
    }

    public function testGenerateWithGlobalOptionsConstraints(): void
    {
        $schema = new Schema([
            'type'     => 'array',
            'items'    => ['type' => 'string'],
            'minItems' => 1,
            'maxItems' => 100,
        ]);
        $options = new Options();
        $options->setMinItems(10);
        $options->setMaxItems(10);

        $arrayFaker  = new ArrayFaker();
        $result      = $arrayFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertCount(10, $result);
    }

    public function testGenerateStaticNoExample(): void
    {
        $schema = new Schema([
            'type'     => 'array',
            'items'    => ['type' => 'string'],
            'minItems' => 5,
        ]);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $arrayFaker  = new ArrayFaker();
        $result      = $arrayFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertCount(5, $result);
    }
}
