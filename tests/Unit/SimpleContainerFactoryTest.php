<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Unit;

use Codeception\Test\Unit;
use Psr\Container\ContainerInterface;
use stdClass;
use WebProject\PhpOpenApiMockServer\Container\SimpleContainer;

class SimpleContainerFactoryTest extends Unit
{
    public function test0ArgClosureFactory(): void
    {
        $container = new SimpleContainer();
        $container->setFactory('service', static function () {
            return new stdClass();
        });

        $instance = $container->get('service');
        self::assertInstanceOf(stdClass::class, $instance);
    }

    public function test1ArgClosureFactory(): void
    {
        $container = new SimpleContainer();
        $container->setFactory('service', function ($container) {
            $this->assertInstanceOf(ContainerInterface::class, $container);

            return new stdClass();
        });

        $instance = $container->get('service');
        self::assertInstanceOf(stdClass::class, $instance);
    }

    public function test2ArgClosureFactory(): void
    {
        $container = new SimpleContainer();
        $container->setFactory('service', function ($container, $id) {
            $this->assertInstanceOf(ContainerInterface::class, $container);
            $this->assertSame('service', $id);

            return new stdClass();
        });

        $instance = $container->get('service');
        self::assertInstanceOf(stdClass::class, $instance);
    }

    public function testNative0ArgFunction(): void
    {
        $container = new SimpleContainer();
        $container->setFactory('service', 'time');

        $instance = $container->get('service');
        self::assertIsInt($instance);
    }

    public function testInvokableClassFactory(): void
    {
        $container = new SimpleContainer();
        $container->setInvokableClass('service', stdClass::class);

        $instance = $container->get('service');
        self::assertInstanceOf(stdClass::class, $instance);
    }

    public function test1ArgInvokableObjectFactory(): void
    {
        $container = new SimpleContainer();
        $factory   = new class {
            public function __invoke(ContainerInterface $container): stdClass
            {
                return new stdClass();
            }
        };
        $container->setFactory('service', $factory);

        $instance = $container->get('service');
        self::assertInstanceOf(stdClass::class, $instance);
    }
}
