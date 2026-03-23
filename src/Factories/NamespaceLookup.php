<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Factories;

use ReflectionClass;
use ReflectionException;
use Respect\Fluent\Exceptions\CouldNotCreate;
use Respect\Fluent\Exceptions\CouldNotResolve;
use Respect\Fluent\FluentNode;
use Respect\Fluent\FluentResolver;
use Respect\Fluent\ReflectiveFactory;

use function array_values;
use function class_exists;
use function sprintf;
use function trim;

final readonly class NamespaceLookup implements ReflectiveFactory
{
    /** @var array<int, string> */
    private array $namespaces;

    /** @param class-string|null $nodeType Expected interface for resolved nodes */
    public function __construct(
        private FluentResolver $resolver,
        private string|null $nodeType = null,
        string ...$namespaces,
    ) {
        $this->namespaces = array_values($namespaces);
    }

    public function withNamespace(string $namespace): self
    {
        return clone ($this, ['namespaces' => [trim($namespace, '\\'), ...$this->namespaces]]);
    }

    /** @param class-string $nodeType */
    public function withNodeType(string $nodeType): self
    {
        return clone ($this, ['nodeType' => $nodeType]);
    }

    /** @return ReflectionClass<object> */
    public function resolve(string $name): ReflectionClass
    {
        $className = $this->resolver->resolve(new FluentNode($name))->name;

        foreach ($this->namespaces as $namespace) {
            $fqcn = $namespace . '\\' . $className;
            if (!class_exists($fqcn)) {
                continue;
            }

            return new ReflectionClass($fqcn);
        }

        throw new CouldNotResolve(
            sprintf('Could not resolve "%s" in any registered namespace', $name),
        );
    }

    /**
     * Resolve, instantiate, and validate against nodeType.
     *
     * @param array<int|string, mixed> $arguments
     */
    public function create(string $name, array $arguments = []): object
    {
        try {
            $instance = $this->resolve($name)->newInstanceArgs($arguments);
        } catch (ReflectionException $exception) {
            throw new CouldNotCreate(
                sprintf('Could not instantiate "%s": %s', $name, $exception->getMessage()),
                previous: $exception,
            );
        }

        if ($this->nodeType !== null && !$instance instanceof $this->nodeType) {
            throw new CouldNotCreate(
                sprintf('"%s" does not implement %s', $name, $this->nodeType),
            );
        }

        return $instance;
    }
}
