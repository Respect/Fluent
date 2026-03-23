<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Test\Unit\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Respect\Fluent\Exceptions\CouldNotCreate;
use Respect\Fluent\Exceptions\CouldNotResolve;
use Respect\Fluent\Factories\NamespaceLookup;
use Respect\Fluent\Resolvers\Suffix;
use Respect\Fluent\Resolvers\Ucfirst;
use Respect\Fluent\Test\Fixtures\NsA\Foo as NsAFoo;
use Respect\Fluent\Test\Fixtures\NsA\FooHandler;
use Respect\Fluent\Test\Fixtures\NsB\Bar;

#[CoversClass(NamespaceLookup::class)]
final class NamespaceLookupTest extends TestCase
{
    #[Test]
    public function itShouldResolveInSingleNamespace(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');

        $reflection = $lookup->resolve('foo');

        self::assertSame(NsAFoo::class, $reflection->getName());
    }

    #[Test]
    public function itShouldResolveFirstMatchingNamespace(): void
    {
        $lookup = new NamespaceLookup(
            new Ucfirst(),
            null,
            'Respect\\Fluent\\Test\\Fixtures\\NsA',
            'Respect\\Fluent\\Test\\Fixtures\\NsB',
        );

        self::assertSame(NsAFoo::class, $lookup->resolve('foo')->getName());
    }

    #[Test]
    public function itShouldFallToSecondNamespace(): void
    {
        $lookup = new NamespaceLookup(
            new Ucfirst(),
            null,
            'Respect\\Fluent\\Test\\Fixtures\\NsA',
            'Respect\\Fluent\\Test\\Fixtures\\NsB',
        );

        self::assertSame(Bar::class, $lookup->resolve('bar')->getName());
    }

    #[Test]
    public function itShouldPrependWithNamespace(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $extended = $lookup->withNamespace('Respect\\Fluent\\Test\\Fixtures\\NsB');

        // NsB\Foo takes priority over NsA\Foo
        self::assertSame('Respect\\Fluent\\Test\\Fixtures\\NsB\\Foo', $extended->resolve('foo')->getName());
        // Original is unaffected
        self::assertSame(NsAFoo::class, $lookup->resolve('foo')->getName());
    }

    #[Test]
    public function itShouldThrowForUnresolvableName(): void
    {
        $this->expectException(CouldNotResolve::class);

        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $lookup->resolve('nonExistent');
    }

    #[Test]
    public function itShouldCreateInstance(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');

        $instance = $lookup->create('foo', ['hello']);

        self::assertInstanceOf(NsAFoo::class, $instance);
        self::assertSame('hello', $instance->value);
    }

    #[Test]
    public function itShouldWorkWithSuffixResolver(): void
    {
        $lookup = new NamespaceLookup(
            new Suffix('of', 'Handler'),
            null,
            'Respect\\Fluent\\Test\\Fixtures\\NsA',
        );

        self::assertSame(FooHandler::class, $lookup->resolve('ofFoo')->getName());
    }

    #[Test]
    public function itShouldValidateNodeType(): void
    {
        $lookup = new NamespaceLookup(
            new Ucfirst(),
            NsAFoo::class,
            'Respect\\Fluent\\Test\\Fixtures\\NsA',
        );

        $instance = $lookup->create('foo');

        self::assertInstanceOf(NsAFoo::class, $instance);
    }

    #[Test]
    public function itShouldThrowWhenNodeTypeDoesNotMatch(): void
    {
        $this->expectException(CouldNotCreate::class);

        $lookup = new NamespaceLookup(
            new Ucfirst(),
            Bar::class,
            'Respect\\Fluent\\Test\\Fixtures\\NsA',
        );

        $lookup->create('foo');
    }

    #[Test]
    public function itShouldSupportWithNodeType(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $typed = $lookup->withNodeType(NsAFoo::class);

        $instance = $typed->create('foo');

        self::assertInstanceOf(NsAFoo::class, $instance);
    }

    #[Test]
    public function itShouldThrowCouldNotCreateWhenInstantiationFails(): void
    {
        $this->expectException(CouldNotCreate::class);

        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $lookup->create('uninstantiable');
    }
}
