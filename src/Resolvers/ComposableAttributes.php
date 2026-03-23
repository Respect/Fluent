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
    private array $discovered = [];

    /** @var array<string, Composable|false> */
    private array $suffixCache = [];

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

            $attr = $this->discoverPrefix($candidatePrefix);
            if ($attr === null) {
                continue;
            }

            $suffixConstraints = $this->discoverSuffixConstraints($candidateSuffix);
            if ($suffixConstraints !== null && !$this->isAllowed($attr->prefix, $suffixConstraints)) {
                continue;
            }

            return Decompose::nodeSpec($nodeSpec, $candidatePrefix, $candidateSuffix, $attr->prefixParameter);
        }

        return $nodeSpec;
    }

    private function discoverSuffixConstraints(string $suffix): Composable|null
    {
        if (isset($this->suffixCache[$suffix])) {
            $cached = $this->suffixCache[$suffix];

            return $cached === false ? null : $cached;
        }

        try {
            $reflection = $this->lookup->resolve($suffix);
            $attrs = $reflection->getAttributes(Composable::class);
            if ($attrs !== []) {
                $attr = $attrs[0]->newInstance();
                $this->suffixCache[$suffix] = $attr;

                return $attr;
            }
        } catch (CouldNotResolve) {
            // Suffix class not found, no constraints to check
        }

        $this->suffixCache[$suffix] = false;

        return null;
    }

    private function isAllowed(string $prefix, Composable $suffixAttr): bool
    {
        if ($suffixAttr->optIn) {
            return in_array($prefix, $suffixAttr->with, true);
        }

        return !in_array($prefix, $suffixAttr->without, true);
    }

    private function discoverPrefix(string $candidatePrefix): Composable|null
    {
        if (isset($this->discovered[$candidatePrefix])) {
            $cached = $this->discovered[$candidatePrefix];

            return $cached === false ? null : $cached;
        }

        try {
            $reflection = $this->lookup->resolve($candidatePrefix);
            $attrs = $reflection->getAttributes(Composable::class);
            if ($attrs !== []) {
                $attr = $attrs[0]->newInstance();
                if ($attr->prefix !== '') {
                    $this->discovered[$candidatePrefix] = $attr;

                    return $attr;
                }
            }
        } catch (CouldNotResolve) {
            // Not a valid class
        }

        $this->discovered[$candidatePrefix] = false;

        return null;
    }
}
