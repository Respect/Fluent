<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Resolvers;

use Respect\Fluent\Attributes\Composable;
use Respect\Fluent\Exceptions\CouldNotResolve;
use Respect\Fluent\Factories\NamespaceLookup;
use Respect\Fluent\FluentNode;
use Respect\Fluent\FluentResolver;
use Respect\Fluent\Helpers\Decompose;

use function ctype_upper;
use function in_array;
use function strlen;
use function substr;

final class ComposableAttributes implements FluentResolver
{
    /** @var array<string, Composable|false> */
    private array $attributes = [];

    public function __construct(
        private readonly NamespaceLookup $lookup,
    ) {
    }

    public function resolve(FluentNode $nodeSpec): FluentNode
    {
        if ($nodeSpec->wrapper !== null) {
            return $nodeSpec;
        }

        // If the full name resolves to a class, use it directly
        try {
            $this->lookup->resolve($nodeSpec->name);

            return $nodeSpec;
        } catch (CouldNotResolve) {
            // Not a direct class, try prefix decomposition
        }

        $name = $nodeSpec->name;

        for ($i = 1; $i < strlen($name); $i++) {
            if (!ctype_upper($name[$i])) {
                continue;
            }

            $candidatePrefix = substr($name, 0, $i);
            $candidateSuffix = substr($name, $i);

            $attr = $this->discoverAttribute($candidatePrefix);
            if ($attr === null || $attr->prefix === '') {
                continue;
            }

            $suffixAttr = $this->discoverAttribute($candidateSuffix);
            if ($suffixAttr !== null && !$this->isAllowed($attr->prefix, $suffixAttr)) {
                continue;
            }

            return Decompose::nodeSpec($nodeSpec, $candidatePrefix, $candidateSuffix, $attr->prefixParameter);
        }

        return $nodeSpec;
    }

    private function isAllowed(string $prefix, Composable $suffixAttr): bool
    {
        if ($suffixAttr->optIn) {
            return in_array($prefix, $suffixAttr->with, true);
        }

        return !in_array($prefix, $suffixAttr->without, true);
    }

    private function discoverAttribute(string $name): Composable|null
    {
        if (isset($this->attributes[$name])) {
            $cached = $this->attributes[$name];

            return $cached === false ? null : $cached;
        }

        try {
            $reflection = $this->lookup->resolve($name);
            $attrs = $reflection->getAttributes(Composable::class);
            if ($attrs !== []) {
                $attr = $attrs[0]->newInstance();
                $this->attributes[$name] = $attr;

                return $attr;
            }
        } catch (CouldNotResolve) {
            // Class not found in any namespace
        }

        $this->attributes[$name] = false;

        return null;
    }
}
