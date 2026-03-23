<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Test\Unit\Builders;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Respect\Fluent\Builders\Append;
use Respect\Fluent\Builders\FluentBuilder;
use Respect\Fluent\Factories\ComposingLookup;
use Respect\Fluent\Factories\NamespaceLookup;
use Respect\Fluent\Resolvers\ComposableMap;
use Respect\Fluent\Resolvers\Ucfirst;
use Respect\Fluent\Test\Fixtures\NsA\Foo as NsAFoo;
use Respect\Fluent\Test\Fixtures\NsB\Bar;

#[CoversClass(Append::class)]
#[CoversClass(FluentBuilder::class)]
final class AppendTest extends TestCase
{
    #[Test]
    public function itShouldStartEmpty(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $builder = new Append($lookup);

        self::assertSame([], $builder->getNodes());
    }

    #[Test]
    public function itShouldCreateNodeViaCall(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $builder = new Append($lookup);

        $result = $builder->__call('foo', []);

        self::assertCount(1, $result->getNodes());
        self::assertInstanceOf(NsAFoo::class, $result->getNodes()[0]);
    }

    #[Test]
    public function itShouldPassArguments(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $builder = new Append($lookup);

        $result = $builder->__call('foo', ['hello']);

        self::assertInstanceOf(NsAFoo::class, $result->getNodes()[0]);
        self::assertSame('hello', $result->getNodes()[0]->value);
    }

    #[Test]
    public function itShouldAccumulateInOrder(): void
    {
        $lookup = new NamespaceLookup(
            new Ucfirst(),
            null,
            'Respect\\Fluent\\Test\\Fixtures\\NsA',
            'Respect\\Fluent\\Test\\Fixtures\\NsB',
        );
        $builder = new Append($lookup);

        $result = $builder->__call('foo', [])->__call('bar', []);

        self::assertCount(2, $result->getNodes());
        self::assertInstanceOf(NsAFoo::class, $result->getNodes()[0]);
        self::assertInstanceOf(Bar::class, $result->getNodes()[1]);
    }

    #[Test]
    public function itShouldBeImmutable(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $builder = new Append($lookup);

        $builder->__call('foo', []);

        self::assertSame([], $builder->getNodes());
    }

    #[Test]
    public function itShouldAttachDirectly(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $builder = new Append($lookup);
        $node = new NsAFoo('manual');

        $result = $builder->attach($node);

        self::assertCount(1, $result->getNodes());
        self::assertSame($node, $result->getNodes()[0]);
    }

    #[Test]
    public function itShouldDelegateWithNamespace(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $builder = new Append($lookup);

        $extended = $builder->withNamespace('Respect\\Fluent\\Test\\Fixtures\\NsB');
        $result = $extended->__call('bar', []);

        self::assertCount(1, $result->getNodes());
        self::assertInstanceOf(Bar::class, $result->getNodes()[0]);
    }

    #[Test]
    public function itShouldWorkWithComposingLookup(): void
    {
        $flat = new NamespaceLookup(
            new Ucfirst(),
            null,
            'Respect\\Fluent\\Test\\Fixtures\\Prefixed',
        );
        $nested = new ComposingLookup($flat, new ComposableMap([]));
        $builder = new Append($nested);

        $result = $builder->__call('foo', []);

        self::assertCount(1, $result->getNodes());
    }

    #[Test]
    public function itShouldAcceptInitialNodes(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $existing = new NsAFoo('existing');
        $builder = new Append($lookup, $existing);

        self::assertCount(1, $builder->getNodes());
        self::assertSame($existing, $builder->getNodes()[0]);

        $result = $builder->__call('foo', ['new']);

        self::assertCount(2, $result->getNodes());
        self::assertSame($existing, $result->getNodes()[0]);
    }
}
