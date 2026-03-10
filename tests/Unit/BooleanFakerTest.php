<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\Schema;
use Codeception\Test\Unit;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\BooleanFaker;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerContext;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerRegistry;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class BooleanFakerTest extends Unit
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

    public function testGenerate(): void
    {
        $schema  = new Schema(['type' => 'boolean']);
        $options = new Options();

        $booleanFaker = new BooleanFaker();
        $result       = $booleanFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertIsBool($result);
    }

    public function testGenerateStatic(): void
    {
        $schema  = new Schema(['type' => 'boolean', 'example' => false]);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $booleanFaker = new BooleanFaker();
        $result       = $booleanFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertFalse($result);
    }

    public function testGenerateStaticNullable(): void
    {
        $schema  = new Schema(['type' => 'boolean', 'nullable' => true]);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $booleanFaker = new BooleanFaker();
        $result       = $booleanFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertNull($result);
    }
}
