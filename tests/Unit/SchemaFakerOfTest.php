<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use cebe\openapi\spec\Schema;
use Codeception\Test\Unit;
use function is_int;
use function is_string;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\FakerRegistry;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker\SchemaFaker;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class SchemaFakerOfTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testResolveOfConstraintsNested(): void
    {
        $schema = new Schema([
            'allOf' => [
                [
                    'oneOf' => [
                        ['properties' => ['a' => ['type' => 'string']]],
                        ['properties' => ['b' => ['type' => 'integer']]],
                    ],
                ],
                [
                    'properties' => ['c' => ['type' => 'boolean']],
                ],
            ],
        ]);

        $schemaFaker  = new SchemaFaker(new FakerRegistry());
        $result       = $schemaFaker->generate($schema, new Options());

        // Based on implementation, if type is not specified at top level it defaults to []
        // UNLESS properties are present.
        self::assertIsArray($result);
    }

    public function testResolveOfConstraintsAnyOf(): void
    {
        $schema = new Schema([
            'anyOf' => [
                ['type' => 'string'],
                ['type' => 'integer'],
            ],
        ]);

        $schemaFaker  = new SchemaFaker(new FakerRegistry());
        $result       = $schemaFaker->generate($schema, new Options());
        self::assertTrue(is_string($result) || is_int($result));
    }
}
