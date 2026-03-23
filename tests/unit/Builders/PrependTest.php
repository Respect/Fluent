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
use Respect\Fluent\Builders\FluentBuilder;
use Respect\Fluent\Builders\Prepend;
use Respect\Fluent\Factories\NamespaceLookup;
use Respect\Fluent\Resolvers\Ucfirst;
use Respect\Fluent\Test\Fixtures\NsA\Foo as NsAFoo;
use Respect\Fluent\Test\Fixtures\NsB\Bar;

#[CoversClass(Prepend::class)]
#[CoversClass(FluentBuilder::class)]
final class PrependTest extends TestCase
{
    #[Test]
    public function itShouldPrependNodes(): void
    {
        $lookup = new NamespaceLookup(
            new Ucfirst(),
            null,
            'Respect\\Fluent\\Test\\Fixtures\\NsA',
            'Respect\\Fluent\\Test\\Fixtures\\NsB',
        );
        $builder = new Prepend($lookup);

        $result = $builder->__call('foo', [])->__call('bar', []);

        self::assertCount(2, $result->getNodes());
        self::assertInstanceOf(Bar::class, $result->getNodes()[0]);
        self::assertInstanceOf(NsAFoo::class, $result->getNodes()[1]);
    }

    #[Test]
    public function itShouldBeImmutable(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $builder = new Prepend($lookup);

        $builder->__call('foo', []);

        self::assertSame([], $builder->getNodes());
    }

    #[Test]
    public function itShouldPrependToInitialNodes(): void
    {
        $lookup = new NamespaceLookup(
            new Ucfirst(),
            null,
            'Respect\\Fluent\\Test\\Fixtures\\NsA',
            'Respect\\Fluent\\Test\\Fixtures\\NsB',
        );
        $existing = new NsAFoo('existing');
        $builder = new Prepend($lookup, $existing);

        $result = $builder->__call('bar', []);

        self::assertCount(2, $result->getNodes());
        self::assertInstanceOf(Bar::class, $result->getNodes()[0]);
        self::assertSame($existing, $result->getNodes()[1]);
    }

    #[Test]
    public function itShouldAttachDirectly(): void
    {
        $lookup = new NamespaceLookup(new Ucfirst(), null, 'Respect\\Fluent\\Test\\Fixtures\\NsA');
        $builder = new Prepend($lookup);
        $first = new NsAFoo('first');
        $second = new NsAFoo('second');

        $result = $builder->attach($first)->attach($second);

        self::assertInstanceOf(NsAFoo::class, $result->getNodes()[0]);
        self::assertSame('second', $result->getNodes()[0]->value);
        self::assertInstanceOf(NsAFoo::class, $result->getNodes()[1]);
        self::assertSame('first', $result->getNodes()[1]->value);
    }
}
