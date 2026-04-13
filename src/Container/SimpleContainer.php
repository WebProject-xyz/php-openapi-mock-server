<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Container;

use function array_key_exists;
use function class_exists;
use Closure;
use function is_array;
use function is_callable;
use function is_int;
use function is_object;
use function is_string;
use Psr\Container\ContainerInterface;
use ReflectionFunction;
use ReflectionMethod;
use function str_contains;
use Webmozart\Assert\Assert;
use WebProject\PhpOpenApiMockServer\Container\Exception\ContainerException;
use WebProject\PhpOpenApiMockServer\Container\Exception\NotFoundException;

/**
 * Lightweight PSR-11 container that supports the Mezzio ConfigProvider format
 * (factories, aliases, invokables, delegators) without requiring laminas-servicemanager.
 *
 * @phpstan-type MezzioDependencyConfig array{
 *     services?: array<string, mixed>,
 *     factories?: array<string, (callable(): mixed)|string>,
 *     aliases?: array<string, string>,
 *     invokables?: array<int|string, string>,
 *     delegators?: array<string, array<int|string, (callable(): mixed)|string>>
 * }|array<string, string[]>
 */
final class SimpleContainer implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $services = [];

    /** @var array<string, callable|string> */
    private array $factories = [];

    /** @var array<string, string> */
    private array $aliases = [];

    /** @var array<string, array<int|string, callable|string>> */
    private array $delegators = [];

    public function get(string $id): mixed
    {
        $resolved = $this->resolveAlias($id, true);
        Assert::string($resolved);

        if (array_key_exists($resolved, $this->services)) {
            return $this->services[$resolved];
        }

        if (!isset($this->factories[$resolved])) {
            throw new NotFoundException("Service '{$id}' not found in container.");
        }

        $factory = $this->factories[$resolved];

        if (is_string($factory) && class_exists($factory)) {
            $factory = new $factory();
        }

        if (!is_callable($factory)) {
            throw new ContainerException("Factory for '{$resolved}' is not callable.");
        }

        $reflection = match (true) {
            $factory instanceof Closure                          => new ReflectionFunction($factory),
            is_string($factory) && !str_contains($factory, '::') => new ReflectionFunction($factory),
            is_string($factory) && str_contains($factory, '::')  => new ReflectionMethod($factory),
            is_array($factory)                                   => new ReflectionMethod($factory[0], $factory[1]),
            is_object($factory)                                  => new ReflectionMethod($factory, '__invoke'),
            default                                              => throw new ContainerException("Unsupported factory type for '{$resolved}'."),
        };

        $params   = $reflection->getNumberOfParameters();
        $instance = match (true) {
            0 === $params => $factory(),
            1 === $params => $factory($this),
            default       => $factory($this, $resolved),
        };

        if (isset($this->delegators[$resolved])) {
            foreach ($this->delegators[$resolved] as $delegator) {
                $current = $instance;

                if (is_string($delegator) && class_exists($delegator)) {
                    $delegator = new $delegator();
                }

                if (!is_callable($delegator)) {
                    throw new ContainerException("Delegator for '{$resolved}' is not callable.");
                }

                $instance = $delegator($this, $resolved, static fn (): mixed => $current);
            }
        }

        $this->services[$resolved] = $instance;

        return $instance;
    }

    public function has(string $id): bool
    {
        $resolved = $this->resolveAlias($id, false);

        if (null === $resolved) {
            return false;
        }

        return array_key_exists($resolved, $this->services) || isset($this->factories[$resolved]);
    }

    /**
     * Process a Mezzio-style dependency configuration array.
     *
     * @param MezzioDependencyConfig $config
     */
    public function configure(array $config): void
    {
        if (isset($config['services']) && is_array($config['services'])) {
            foreach ($config['services'] as $name => $service) {
                $this->setService($name, $service);
            }
        }

        if (isset($config['invokables']) && is_array($config['invokables'])) {
            foreach ($config['invokables'] as $name => $class) {
                $this->setInvokableClass(is_int($name) ? $class : $name, $class);
            }
        }

        if (isset($config['factories']) && is_array($config['factories'])) {
            foreach ($config['factories'] as $name => $factory) {
                $this->setFactory($name, $factory);
            }
        }

        if (isset($config['aliases']) && is_array($config['aliases'])) {
            foreach ($config['aliases'] as $alias => $target) {
                $this->setAlias($alias, $target);
            }
        }

        if (isset($config['delegators']) && is_array($config['delegators'])) {
            foreach ($config['delegators'] as $name => $delegatorList) {
                foreach ((array) $delegatorList as $delegator) {
                    $this->delegators[$name][] = $delegator;
                }
            }
        }
    }

    public function setService(string $name, mixed $service): void
    {
        unset($this->aliases[$name], $this->factories[$name]);
        $this->services[$name] = $service;
    }

    public function setFactory(string $name, callable|string $factory): void
    {
        unset($this->aliases[$name], $this->services[$name]);
        $this->factories[$name] = $factory;
    }

    public function setAlias(string $alias, string $target): void
    {
        unset($this->services[$alias], $this->factories[$alias]);
        $this->aliases[$alias] = $target;
    }

    public function setInvokableClass(string $name, string $class): void
    {
        $this->setFactory($name, static fn (): object => new $class());
    }

    private function resolveAlias(string $id, bool $throwOnCircular = true): ?string
    {
        $seen = [];

        while (isset($this->aliases[$id])) {
            if (isset($seen[$id])) {
                if ($throwOnCircular) {
                    throw new ContainerException("Circular alias detected for '{$id}'.");
                }

                return null;
            }

            $seen[$id] = true;
            $id        = $this->aliases[$id];
        }

        return $id;
    }
}
