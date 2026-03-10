<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use Codeception\Test\Unit;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class OptionsTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testOptionsSettersAndGetters(): void
    {
        $options = new Options();

        $options->setMinItems(5);
        self::assertSame(5, $options->getMinItems());

        $options->setMaxItems(10);
        self::assertSame(10, $options->getMaxItems());

        $options->setAlwaysFakeOptionals(true);
        self::assertTrue($options->getAlwaysFakeOptionals());

        $options->setStrategy(MockStrategy::STATIC);
        self::assertSame(MockStrategy::STATIC, $options->getStrategy());
    }
}
