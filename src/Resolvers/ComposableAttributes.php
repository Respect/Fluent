<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Resolvers;

use ReflectionClass;
use Respect\Fluent\Attributes\Composable;
use Respect\Fluent\Attributes\ComposableParameter;
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
    /** @var array<string, array{Composable, class-string, bool}|false> */
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

            $discovered = $this->discoverAttribute($candidatePrefix);
            if ($discovered === null || $discovered[0]->prefix === null) {
                continue;
            }

            $prefixFqcn = $discovered[1];
            $hasComposableParameter = $discovered[2];

            $suffixDiscovered = $this->discoverAttribute($candidateSuffix);
            if ($suffixDiscovered !== null && !$this->isAllowed($prefixFqcn, $suffixDiscovered[0])) {
                continue;
            }

            return Decompose::nodeSpec($nodeSpec, $candidatePrefix, $candidateSuffix, $hasComposableParameter);
        }

        return $nodeSpec;
    }

    public function unresolve(FluentNode $nodeSpec): FluentNode
    {
        return Decompose::compose($nodeSpec);
    }

    /** @param class-string $prefixFqcn */
    private function isAllowed(string $prefixFqcn, Composable $suffixAttr): bool
    {
        if ($suffixAttr->optIn) {
            return in_array($prefixFqcn, $suffixAttr->with, true);
        }

        return !in_array($prefixFqcn, $suffixAttr->without, true);
    }

    /** @return array{Composable, class-string, bool}|null */
    private function discoverAttribute(string $name): array|null
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
                $fqcn = $reflection->getName();
                $hasComposableParameter = self::hasComposableParameter($reflection);
                $this->attributes[$name] = [$attr, $fqcn, $hasComposableParameter];

                return [$attr, $fqcn, $hasComposableParameter];
            }
        } catch (CouldNotResolve) {
            // Class not found in any namespace
        }

        $this->attributes[$name] = false;

        return null;
    }

    /** @param ReflectionClass<object> $reflection */
    private static function hasComposableParameter(ReflectionClass $reflection): bool
    {
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return false;
        }

        $parameters = $constructor->getParameters();
        if ($parameters === []) {
            return false;
        }

        $composableCount = 0;
        foreach ($parameters as $index => $parameter) {
            if ($parameter->getAttributes(ComposableParameter::class) === []) {
                continue;
            }

            $composableCount++;
            if ($index !== 0) {
                return false;
            }
        }

        return $composableCount === 1;
    }
}
