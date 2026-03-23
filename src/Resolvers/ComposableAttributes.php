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
use function strlen;
use function substr;

final class ComposableAttributes implements FluentResolver
{
    /** @var array<string, Composable> */
    private array $discovered = [];

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

            return Decompose::nodeSpec($nodeSpec, $candidatePrefix, $candidateSuffix, $attr->prefixParameter);
        }

        return $nodeSpec;
    }

    private function discoverPrefix(string $candidatePrefix): Composable|null
    {
        if (isset($this->discovered[$candidatePrefix])) {
            return $this->discovered[$candidatePrefix];
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

        return null;
    }
}
