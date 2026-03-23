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
use Respect\Fluent\Exceptions\CouldNotResolve;
use Respect\Fluent\Factories\ComposingLookup;
use Respect\Fluent\Factories\NamespaceLookup;
use Respect\Fluent\FluentNode;
use Respect\Fluent\FluentResolver;
use Respect\Fluent\Resolvers\ComposableMap;
use Respect\Fluent\Resolvers\Ucfirst;
use Respect\Fluent\Test\Fixtures\NsA\Foo;
use Respect\Fluent\Test\Fixtures\Prefixed\Key;
use Respect\Fluent\Test\Fixtures\Prefixed\Not;

#[CoversClass(ComposingLookup::class)]
final class ComposingLookupTest extends TestCase
{
    #[Test]
    public function itShouldCreateWithoutResolution(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $nested = new ComposingLookup($lookup, new ComposableMap(['not' => true]));

        $instance = $nested->create('foo');

        self::assertInstanceOf(Foo::class, $instance);
    }

    #[Test]
    public function itShouldSupportWithNamespace(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $nested = new ComposingLookup($lookup, new ComposableMap([]));
        $extended = $nested->withNamespace('Respect\\Fluent\\Test\\Fixtures\\NsB');

        self::assertSame(
            'Respect\\Fluent\\Test\\Fixtures\\NsB\\Foo',
            $extended->create('foo')::class,
        );
    }

    #[Test]
    public function itShouldCreateWithSingleWrapper(): void
    {
        $lookup = new NamespaceLookup(
            new Ucfirst(),
            null,
            'Respect\\Fluent\\Test\\Fixtures\\Prefixed',
        );
        $nested = new ComposingLookup($lookup, new ComposableMap(['not' => true]));

        $instance = $nested->create('notFoo');

        self::assertInstanceOf(Not::class, $instance);
        self::assertInstanceOf(\Respect\Fluent\Test\Fixtures\Prefixed\Foo::class, $instance->inner);
    }

    #[Test]
    public function itShouldCreateWithPrefixParameter(): void
    {
        $lookup = new NamespaceLookup(
            new Ucfirst(),
            null,
            'Respect\\Fluent\\Test\\Fixtures\\Prefixed',
        );
        $nested = new ComposingLookup(
            $lookup,
            new ComposableMap(['key' => true], ['key' => true]),
        );

        $instance = $nested->create('keyFoo', ['myKey']);

        self::assertInstanceOf(Key::class, $instance);
        self::assertSame('myKey', $instance->key);
        self::assertInstanceOf(\Respect\Fluent\Test\Fixtures\Prefixed\Foo::class, $instance->inner);
    }

    #[Test]
    public function itShouldUnwrapRecursiveWrappers(): void
    {
        $lookup = new NamespaceLookup(
            new Ucfirst(),
            null,
            'Respect\\Fluent\\Test\\Fixtures\\Prefixed',
        );

        // Create a resolver that produces nested wrappers: Not(Not(Foo()))
        $resolver = new class implements FluentResolver {
            public function resolve(FluentNode $nodeSpec): FluentNode
            {
                if ($nodeSpec->name === 'doubleNotFoo') {
                    return new FluentNode(
                        'Foo',
                        [],
                        new FluentNode('Not', [], new FluentNode('Not')),
                    );
                }

                return $nodeSpec;
            }
        };

        $nested = new ComposingLookup($lookup, $resolver);

        $instance = $nested->create('doubleNotFoo');

        self::assertInstanceOf(Not::class, $instance);
        self::assertInstanceOf(Not::class, $instance->inner);
        self::assertInstanceOf(\Respect\Fluent\Test\Fixtures\Prefixed\Foo::class, $instance->inner->inner);
    }

    #[Test]
    public function itShouldThrowWhenNameCannotBeResolved(): void
    {
        $this->expectException(CouldNotResolve::class);

        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $nested = new ComposingLookup($lookup, new ComposableMap([]));

        $nested->create('nonExistent');
    }

    #[Test]
    public function itShouldPassArgumentsToCreatedInstance(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $nested = new ComposingLookup($lookup, new ComposableMap([]));

        $instance = $nested->create('foo', ['hello']);

        self::assertInstanceOf(Foo::class, $instance);
        self::assertSame('hello', $instance->value);
    }
}
