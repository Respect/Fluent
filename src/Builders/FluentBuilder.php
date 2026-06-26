<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Builders;

use ReflectionClass;
use Respect\Fluent\Attributes\FluentNamespace;
use Respect\Fluent\FluentFactory;

use function array_values;

/**
 * @template TNodes of list<object>
 * @template TSure
 * @template TSureNot
 * @template TExact of bool = true
 */
abstract readonly class FluentBuilder
{
    /** @var list<object> */
    protected array $nodes;

    public function __construct(
        protected FluentFactory $factory,
        object ...$nodes,
    ) {
        $this->nodes = array_values($nodes);
    }

    abstract public function attach(object ...$nodes): static;

    /** @return list<object> */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function withNamespace(string $namespace): static
    {
        return clone ($this, ['factory' => $this->factory->withNamespace($namespace)]);
    }

    protected static function factoryFromAttribute(): FluentFactory
    {
        return (new ReflectionClass(static::class))
            ->getAttributes(FluentNamespace::class)[0]
            ->newInstance()
            ->factory;
    }

    /** @param array<int|string, mixed> $arguments */
    public function __call(string $name, array $arguments): static
    {
        return $this->attach($this->factory->create($name, $arguments));
    }

    /** @param array<int|string, mixed> $arguments */
    public static function __callStatic(string $name, array $arguments): static
    {
        /** @phpstan-ignore new.static (subclasses with required args override this) */
        return (new static())->__call($name, $arguments);
    }
}
