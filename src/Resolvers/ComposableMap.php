<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Resolvers;

use Respect\Fluent\FluentNode;
use Respect\Fluent\FluentResolver;
use Respect\Fluent\Helpers\Decompose;

use function array_keys;
use function array_map;
use function implode;
use function preg_match;
use function preg_quote;
use function sprintf;

/**
 * A pre-built map resolver for prefix composition.
 */
final readonly class ComposableMap implements FluentResolver
{
    /**
     * @param array<string, true> $composable
     * @param array<string, true> $composableWithArgument
     * @param array<string, array<string, true>> $forbidden              suffix => [prefix => true]
     */
    public function __construct(
        private array $composable,
        private array $composableWithArgument = [],
        private array $forbidden = [],
    ) {
    }

    public function resolve(FluentNode $nodeSpec): FluentNode
    {
        if ($this->composable === [] || $nodeSpec->wrapper !== null || isset($this->composable[$nodeSpec->name])) {
            return $nodeSpec;
        }

        preg_match($this->getRegex(), $nodeSpec->name, $matches);

        if ($matches === [] || isset($this->forbidden[$matches['suffix']][$matches['prefix']])) {
            return $nodeSpec;
        }

        return Decompose::nodeSpec(
            $nodeSpec,
            $matches['prefix'],
            $matches['suffix'],
            isset($this->composableWithArgument[$matches['prefix']]),
        );
    }

    public function unresolve(FluentNode $nodeSpec): FluentNode
    {
        return Decompose::compose($nodeSpec);
    }

    private function getRegex(): string
    {
        $alternation = $this->composable
            |> array_keys(...)
            |> (static fn(array $keys) => array_map(static fn(string $key) => preg_quote($key, '/'), $keys))
            |> (static fn(array $quoted) => implode('|', $quoted));

        return sprintf('/^(?<prefix>%s)(?<suffix>.+)$/', $alternation);
    }
}
