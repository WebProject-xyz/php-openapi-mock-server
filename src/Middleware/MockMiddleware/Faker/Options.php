<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker;

use function is_string;

final class Options
{
    public function __construct(
        private ?int $minItems = null,
        private ?int $maxItems = null,
        private bool $alwaysFakeOptionals = false,
        private MockStrategy $mockStrategy = MockStrategy::DYNAMIC,
    ) {
    }

    public function getMinItems(): ?int
    {
        return $this->minItems;
    }

    public function setMinItems(?int $minItems): self
    {
        $this->minItems = $minItems;

        return $this;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    public function setMaxItems(?int $maxItems): self
    {
        $this->maxItems = $maxItems;

        return $this;
    }

    public function getAlwaysFakeOptionals(): bool
    {
        return $this->alwaysFakeOptionals;
    }

    public function setAlwaysFakeOptionals(bool $alwaysFakeOptionals): self
    {
        $this->alwaysFakeOptionals = $alwaysFakeOptionals;

        return $this;
    }

    public function getStrategy(): MockStrategy
    {
        return $this->mockStrategy;
    }

    public function setStrategy(MockStrategy|string $strategy): self
    {
        if (is_string($strategy)) {
            $strategy = MockStrategy::from($strategy);
        }

        $this->mockStrategy = $strategy;

        return $this;
    }
}
