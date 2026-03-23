<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Factories;

use Respect\Fluent\FluentFactory;
use Respect\Fluent\FluentNode;
use Respect\Fluent\FluentResolver;
use Respect\Fluent\Resolvers\ComposableAttributes;

final readonly class ComposingLookup implements FluentFactory
{
    private FluentResolver $resolver;

    public function __construct(
        public private(set) NamespaceLookup $lookup,
        FluentResolver|null $resolver = null,
    ) {
        $this->resolver = $resolver ?? new ComposableAttributes($lookup);
    }

    public function withNamespace(string $namespace): self
    {
        return clone ($this, ['lookup' => $this->lookup->withNamespace($namespace)]);
    }

    /**
     * Resolve the FluentNode, instantiate, and handle wrapper nesting.
     *
     * @param array<int|string, mixed> $arguments
     */
    public function create(string $name, array $arguments = []): object
    {
        $spec = $this->resolver->resolve(new FluentNode($name, $arguments));
        $instance = $this->lookup->create($spec->name, $spec->arguments);

        $wrapper = $spec->wrapper;
        while ($wrapper !== null) {
            $instance = $this->lookup->create(
                $wrapper->name,
                [...$wrapper->arguments, $instance],
            );
            $wrapper = $wrapper->wrapper;
        }

        return $instance;
    }
}
