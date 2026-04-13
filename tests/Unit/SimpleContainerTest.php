<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use Codeception\Test\Unit;
use WebProject\PhpOpenApiMockServer\Container\Exception\ContainerException;
use WebProject\PhpOpenApiMockServer\Container\SimpleContainer;
use WebProject\PhpOpenApiMockServer\Tests\Support\UnitTester;

class SimpleContainerTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testHasReturnsFalseOnCircularAlias(): void
    {
        $container = new SimpleContainer();
        $container->setAlias('a', 'b');
        $container->setAlias('b', 'a');

        self::assertFalse($container->has('a'));
    }

    public function testGetThrowsOnCircularAlias(): void
    {
        $container = new SimpleContainer();
        $container->setAlias('a', 'b');
        $container->setAlias('b', 'a');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Circular alias detected for 'a'.");
        $container->get('a');
    }
}
