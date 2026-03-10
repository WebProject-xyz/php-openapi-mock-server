<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\Schema;
use Codeception\Test\Unit;
use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_IP;
use const FILTER_VALIDATE_URL;
use function filter_var;
use function strlen;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerContext;
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
        $schema  = new Schema(['type' => 'string', 'default' => 'bar']);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $stringFaker = new StringFaker();
        $result      = $stringFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertSame('bar', $result);
    }

    public function testGenerateStaticExample(): void
    {
        $schema  = new Schema(['type' => 'string', 'example' => 'foo']);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $stringFaker = new StringFaker();
        $result      = $stringFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertSame('foo', $result);
    }

    public function testGenerateDynamicWithFormat(): void
    {
        $options     = new Options();
        $stringFaker = new StringFaker();

        $schemaEmail = new Schema(['type' => 'string', 'format' => 'email']);
        self::assertNotFalse(filter_var($stringFaker->generate($schemaEmail, $options, $this->fakerRegistry, FakerContext::response()), FILTER_VALIDATE_EMAIL));

        $schemaUrl = new Schema(['type' => 'string', 'format' => 'uri']);
        self::assertNotFalse(filter_var($stringFaker->generate($schemaUrl, $options, $this->fakerRegistry, FakerContext::response()), FILTER_VALIDATE_URL));

        $schemaIpv4 = new Schema(['type' => 'string', 'format' => 'ipv4']);
        self::assertNotFalse(filter_var($stringFaker->generate($schemaIpv4, $options, $this->fakerRegistry, FakerContext::response()), FILTER_VALIDATE_IP));
    }

    public function testGenerateBinary(): void
    {
        $schema  = new Schema(['type' => 'string', 'format' => 'binary']);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $stringFaker = new StringFaker();
        $result      = $stringFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertSame('YmFzZTY0LWVudGl0eQ==', $result);
    }

    public function testGenerateByte(): void
    {
        $schema  = new Schema(['type' => 'string', 'format' => 'byte']);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $stringFaker = new StringFaker();
        $result      = $stringFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertSame('YmFzZTY0LWVudGl0eQ==', $result);
    }

    public function testGeneratePassword(): void
    {
        $schema  = new Schema(['type' => 'string', 'format' => 'password']);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $stringFaker = new StringFaker();
        $result      = $stringFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertSame('string', $result);
    }

    public function testGenerateWithMinLength(): void
    {
        $schema  = new Schema(['type' => 'string', 'minLength' => 10]);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $stringFaker = new StringFaker();
        $result      = $stringFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertSame('aaaaaaaaaa', $result);
    }

    public function testGenerateWithMaxLength(): void
    {
        $schema  = new Schema(['type' => 'string', 'maxLength' => 5]);
        $options = new Options();

        $stringFaker = new StringFaker();
        $result      = $stringFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertIsString($result);
        self::assertLessThanOrEqual(20, strlen($result)); // Default text length is 20 if maxLength <= 5
    }

    public function testGenerateDynamicNoFormat(): void
    {
        $schema  = new Schema(['type' => 'string']);
        $options = new Options();

        $stringFaker = new StringFaker();
        $result      = $stringFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertIsString($result);
    }

    public function testGenerateFromExample(): void
    {
        $example = 'test-example';
        $schema  = new Schema([
            'type'    => 'string',
            'example' => $example,
        ]);
        $options = new Options();
        $options->setStrategy(MockStrategy::STATIC);

        $stringFaker = new StringFaker();
        $result      = $stringFaker->generate($schema, $options, $this->fakerRegistry, FakerContext::response());
        self::assertSame($example, $result);
    }
}
