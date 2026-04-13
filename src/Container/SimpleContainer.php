<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Container;

use function array_key_exists;
use function is_array;
use function is_callable;
use function is_int;
use function is_string;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;
use WebProject\PhpOpenApiMockServer\Container\Exception\ContainerException;
use WebProject\PhpOpenApiMockServer\Container\Exception\NotFoundException;

/**
 * Lightweight PSR-11 container that supports the Mezzio ConfigProvider format
 * (factories, aliases, invokables, delegators) without requiring laminas-servicemanager.
 */
final class SimpleContainer implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $services = [];

    /** @var array<string, callable|string> */
    private array $factories = [];

    /** @var array<string, string> */
    private array $aliases = [];

    /** @var array<string, list<callable|string>> */
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

        $instance = $factory($this, $resolved);

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
     * @param array{
     *     factories?: array<string, callable|string>,
     *     aliases?: array<string, string>,
     *     invokables?: array<int|string, string>,
     *     delegators?: array<string, list<callable|string>>
     * } $config
     */
    public function configure(array $config): void
    {
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
        $this->services[$name] = $service;
    }

    public function setFactory(string $name, callable|string $factory): void
    {
        $this->factories[$name] = $factory;
    }

    public function setAlias(string $alias, string $target): void
    {
        $this->aliases[$alias] = $target;
    }

    public function setInvokableClass(string $name, string $class): void
    {
        $this->factories[$name] = static fn (): object => new $class();
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
