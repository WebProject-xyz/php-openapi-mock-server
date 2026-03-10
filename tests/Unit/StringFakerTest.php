<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\Schema;
use Codeception\Test\Unit;
use function strlen;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerRegistry;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\StringFaker;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class StringFakerTest extends Unit
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

    public function testGenerateStatic(): void
    {
        $schema  = new Schema(['type' => 'string', 'default' => 'foo']);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $stringFaker  = new StringFaker();
        $result       = $stringFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertSame('foo', $result);
    }

    public function testGenerateStaticExample(): void
    {
        $schema  = new Schema(['type' => 'string', 'example' => 'bar']);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $stringFaker  = new StringFaker();
        $result       = $stringFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertSame('bar', $result);
    }

    public function testGenerateDynamicWithFormat(): void
    {
        $schema  = new Schema(['type' => 'string', 'format' => 'email']);
        $options = new Options();

        $stringFaker  = new StringFaker();
        $result       = $stringFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertStringContainsString('@', $result);
    }

    public function testGenerateBinary(): void
    {
        $schema        = new Schema(['type' => 'string', 'format' => 'binary']);
        $options       = new Options();
        $stringFaker   = new StringFaker();
        $result        = $stringFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertNotEmpty($result);
    }

    public function testGenerateByte(): void
    {
        $schema        = new Schema(['type' => 'string', 'format' => 'byte']);
        $options       = new Options();
        $stringFaker   = new StringFaker();
        $result        = $stringFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertNotFalse(base64_decode($result, true));
    }

    public function testGeneratePassword(): void
    {
        $schema        = new Schema(['type' => 'string', 'format' => 'password']);
        $options       = new Options();
        $stringFaker   = new StringFaker();
        $result        = $stringFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertNotEmpty($result);
    }

    public function testGenerateWithMinLength(): void
    {
        $schema        = new Schema(['type' => 'string', 'minLength' => 50]);
        $options       = new Options();
        $stringFaker   = new StringFaker();
        $result        = $stringFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertGreaterThanOrEqual(50, strlen($result));
    }

    public function testGenerateWithMaxLength(): void
    {
        $schema        = new Schema(['type' => 'string', 'maxLength' => 2]);
        $options       = new Options();
        $stringFaker   = new StringFaker();
        $result        = $stringFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertLessThanOrEqual(2, strlen($result));
    }

    public function testGenerateDynamicNoFormat(): void
    {
        $schema        = new Schema(['type' => 'string']);
        $options       = new Options();
        $stringFaker   = new StringFaker();
        $result        = $stringFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertNotEmpty($result);
    }

    public function testGenerateFromExample(): void
    {
        $schema  = new Schema(['type' => 'string', 'example' => 'fixed-example']);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $stringFaker  = new StringFaker();
        $result       = $stringFaker->generate($schema, $options, $this->fakerRegistry);
        self::assertSame('fixed-example', $result);
    }
}
