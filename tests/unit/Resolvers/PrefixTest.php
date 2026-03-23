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
    public function discoveryShouldNotDecomposeWhenPrefixClassLacksAttribute(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\Prefixed');
        $sut = new ComposableAttributes($lookup);
        $spec = new FluentNode('emailFoo');

        $result = $sut->resolve($spec);

        self::assertSame('emailFoo', $result->name);
        self::assertNull($result->wrapper);
    }
}
