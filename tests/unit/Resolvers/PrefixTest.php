<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Test\Unit\Resolvers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Respect\Fluent\Factories\NamespaceLookup;
use Respect\Fluent\FluentNode;
use Respect\Fluent\Helpers\Decompose;
use Respect\Fluent\Resolvers\ComposableAttributes;
use Respect\Fluent\Resolvers\ComposableMap;
use Respect\Fluent\Resolvers\Ucfirst;

#[CoversClass(ComposableAttributes::class)]
#[CoversClass(ComposableMap::class)]
#[CoversClass(Decompose::class)]
final class PrefixTest extends TestCase
{
    #[Test]
    public function mapShouldNotResolveUnprefixedName(): void
    {
        $sut = new ComposableMap(
            ['not' => true, 'nullOr' => true, 'key' => true, 'all' => true],
            ['key' => true],
        );
        $spec = new FluentNode('email');

        $result = $sut->resolve($spec);

        self::assertSame('email', $result->name);
        self::assertNull($result->wrapper);
    }

    #[Test]
    public function mapShouldDecomposeSimplePrefix(): void
    {
        $sut = new ComposableMap(
            ['not' => true, 'nullOr' => true, 'key' => true],
            ['key' => true],
        );
        $spec = new FluentNode('notEmail');

        $result = $sut->resolve($spec);

        self::assertSame('Email', $result->name);
        self::assertSame([], $result->arguments);
        self::assertNotNull($result->wrapper);
        self::assertSame('not', $result->wrapper->name);
        self::assertSame([], $result->wrapper->arguments);
    }

    #[Test]
    public function mapShouldForwardArgumentsForSimplePrefix(): void
    {
        $sut = new ComposableMap(
            ['not' => true, 'nullOr' => true],
        );
        $spec = new FluentNode('nullOrString', [5]);

        $result = $sut->resolve($spec);

        self::assertSame('String', $result->name);
        self::assertSame([5], $result->arguments);
        self::assertNotNull($result->wrapper);
        self::assertSame('nullOr', $result->wrapper->name);
    }

    #[Test]
    public function mapShouldShiftArgumentForPrefixWithArgument(): void
    {
        $sut = new ComposableMap(
            ['not' => true, 'key' => true],
            ['key' => true],
        );
        $spec = new FluentNode('keyName', ['userId', 123]);

        $result = $sut->resolve($spec);

        self::assertSame('Name', $result->name);
        self::assertSame([123], $result->arguments);
        self::assertNotNull($result->wrapper);
        self::assertSame('key', $result->wrapper->name);
        self::assertSame(['userId'], $result->wrapper->arguments);
    }

    #[Test]
    public function mapShouldNotResolveAlreadyWrappedSpec(): void
    {
        $sut = new ComposableMap(['not' => true]);
        $spec = new FluentNode('notEmail', [], new FluentNode('existing'));

        $result = $sut->resolve($spec);

        self::assertSame('notEmail', $result->name);
        self::assertNotNull($result->wrapper);
        self::assertSame('existing', $result->wrapper->name);
    }

    #[Test]
    public function mapShouldNotResolveComposableNameItself(): void
    {
        $sut = new ComposableMap(['not' => true]);
        $spec = new FluentNode('not');

        $result = $sut->resolve($spec);

        self::assertSame('not', $result->name);
        self::assertNull($result->wrapper);
    }

    #[Test]
    public function discoveryShouldDecomposeSimplePrefix(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\Prefixed');
        $sut = new ComposableAttributes($lookup);
        $spec = new FluentNode('notFoo');

        $result = $sut->resolve($spec);

        self::assertSame('Foo', $result->name);
        self::assertNotNull($result->wrapper);
        self::assertSame('not', $result->wrapper->name);
    }

    #[Test]
    public function discoveryShouldShiftArgumentForPrefixWithArgument(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\Prefixed');
        $sut = new ComposableAttributes($lookup);
        $spec = new FluentNode('keyFoo', ['myKey', 'value']);

        $result = $sut->resolve($spec);

        self::assertSame('Foo', $result->name);
        self::assertSame(['value'], $result->arguments);
        self::assertNotNull($result->wrapper);
        self::assertSame('key', $result->wrapper->name);
        self::assertSame(['myKey'], $result->wrapper->arguments);
    }

    #[Test]
    public function discoveryShouldNotDecomposeWhenFullClassExists(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\Prefixed');
        $sut = new ComposableAttributes($lookup);
        $spec = new FluentNode('notEmail');

        $result = $sut->resolve($spec);

        self::assertSame('notEmail', $result->name);
        self::assertNull($result->wrapper);
    }

    #[Test]
    public function discoveryShouldNotResolveAlreadyWrappedSpec(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\Prefixed');
        $sut = new ComposableAttributes($lookup);
        $spec = new FluentNode('notFoo', [], new FluentNode('existing'));

        $result = $sut->resolve($spec);

        self::assertSame('notFoo', $result->name);
        self::assertNotNull($result->wrapper);
        self::assertSame('existing', $result->wrapper->name);
    }

    #[Test]
    public function discoveryShouldNotDecomposeUnprefixedName(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\Prefixed');
        $sut = new ComposableAttributes($lookup);
        $spec = new FluentNode('email');

        $result = $sut->resolve($spec);

        self::assertSame('email', $result->name);
        self::assertNull($result->wrapper);
    }

    #[Test]
    public function discoveryShouldCacheDiscoveredPrefixes(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\Prefixed');
        $sut = new ComposableAttributes($lookup);

        $result1 = $sut->resolve(new FluentNode('notFoo'));
        $result2 = $sut->resolve(new FluentNode('notBar'));

        self::assertNotNull($result1->wrapper);
        self::assertSame('not', $result1->wrapper->name);
        self::assertNotNull($result2->wrapper);
        self::assertSame('not', $result2->wrapper->name);
    }

    #[Test]
    public function discoveryShouldCacheSuffixConstraints(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\Prefixed');
        $sut = new ComposableAttributes($lookup);

        // Both resolve the same suffix 'Foo' with different prefixes, exercising the suffix cache
        $result1 = $sut->resolve(new FluentNode('notFoo'));
        $result2 = $sut->resolve(new FluentNode('keyFoo', ['myKey']));

        self::assertNotNull($result1->wrapper);
        self::assertSame('not', $result1->wrapper->name);
        self::assertNotNull($result2->wrapper);
        self::assertSame('key', $result2->wrapper->name);
    }

    #[Test]
    public function discoveryShouldNotDecomposeWhenPrefixClassLacksAttribute(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\Prefixed');
        $sut = new ComposableAttributes($lookup);
        $spec = new FluentNode('emailFoo');

        $result = $sut->resolve($spec);

        self::assertSame('emailFoo', $result->name);
        self::assertNull($result->wrapper);
    }

    #[Test]
    public function discoveryShouldRejectCompositionForbiddenByWithout(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\Prefixed');
        $sut = new ComposableAttributes($lookup);
        $spec = new FluentNode('notNot');

        $result = $sut->resolve($spec);

        // Not has without: ['not'], so notNot should not decompose
        self::assertSame('notNot', $result->name);
        self::assertNull($result->wrapper);
    }

    #[Test]
    public function mapShouldRejectForbiddenComposition(): void
    {
        $sut = new ComposableMap(
            ['not' => true],
            [],
            ['Not' => ['not' => true]],
        );
        $spec = new FluentNode('notNot');

        $result = $sut->resolve($spec);

        // Forbidden map blocks not+Not composition
        self::assertSame('notNot', $result->name);
        self::assertNull($result->wrapper);
    }

    #[Test]
    public function discoveryShouldRejectCompositionForbiddenByOptIn(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\Prefixed');
        $sut = new ComposableAttributes($lookup);
        // OptIn has optIn: true, with: ['key'] — only 'key' prefix is allowed
        $spec = new FluentNode('notOptIn');

        $result = $sut->resolve($spec);

        // 'not' is not in the 'with' list, so composition is rejected
        self::assertSame('notOptIn', $result->name);
        self::assertNull($result->wrapper);
    }

    #[Test]
    public function discoveryShouldAllowCompositionPermittedByOptIn(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\Prefixed');
        $sut = new ComposableAttributes($lookup);
        // OptIn has optIn: true, with: ['key'] — 'key' prefix is allowed
        $spec = new FluentNode('keyOptIn', ['myKey']);

        $result = $sut->resolve($spec);

        self::assertSame('OptIn', $result->name);
        self::assertNotNull($result->wrapper);
        self::assertSame('key', $result->wrapper->name);
    }

    #[Test]
    public function discoveryShouldHandleUnresolvableSuffix(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\Prefixed');
        $sut = new ComposableAttributes($lookup);
        // 'notZzz' — prefix 'not' is valid, but 'Zzz' doesn't exist as a class
        $spec = new FluentNode('notZzz');

        $result = $sut->resolve($spec);

        // Suffix class doesn't exist, but decomposition still works (no constraints to check)
        self::assertSame('Zzz', $result->name);
        self::assertNotNull($result->wrapper);
        self::assertSame('not', $result->wrapper->name);
    }

    #[Test]
    public function discoveryShouldSkipNonComposablePrefix(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\Prefixed');
        $sut = new ComposableAttributes($lookup);
        // 'barFoo' — 'Bar' exists but has no Composable attribute
        $spec = new FluentNode('barFoo');

        $result = $sut->resolve($spec);

        self::assertSame('barFoo', $result->name);
        self::assertNull($result->wrapper);
    }

    #[Test]
    public function mapShouldAllowNonForbiddenComposition(): void
    {
        $sut = new ComposableMap(
            ['not' => true],
            [],
            ['Not' => ['not' => true]],
        );
        $spec = new FluentNode('notEmail');

        $result = $sut->resolve($spec);

        // Email is not in the forbidden map, so notEmail decomposes normally
        self::assertSame('Email', $result->name);
        self::assertNotNull($result->wrapper);
        self::assertSame('not', $result->wrapper->name);
    }
}
