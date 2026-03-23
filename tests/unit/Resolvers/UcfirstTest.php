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
use Respect\Fluent\FluentNode;
use Respect\Fluent\Resolvers\Ucfirst;

#[CoversClass(Ucfirst::class)]
final class UcfirstTest extends TestCase
{
    #[Test]
    public function itShouldUcfirstTheName(): void
    {
        $resolver = new Ucfirst();

        self::assertSame('Foo', $resolver->resolve(new FluentNode('foo'))->name);
    }

    #[Test]
    public function itShouldPreserveAlreadyUppercase(): void
    {
        $resolver = new Ucfirst();

        self::assertSame('Foo', $resolver->resolve(new FluentNode('Foo'))->name);
    }

    #[Test]
    public function itShouldPreserveArguments(): void
    {
        $resolver = new Ucfirst();
        $result = $resolver->resolve(new FluentNode('foo', [1, 2]));

        self::assertSame('Foo', $result->name);
        self::assertSame([1, 2], $result->arguments);
    }

    #[Test]
    public function itShouldPreserveWrapper(): void
    {
        $resolver = new Ucfirst();
        $wrapper = new FluentNode('bar');
        $result = $resolver->resolve(new FluentNode('foo', [], $wrapper));

        self::assertSame('Foo', $result->name);
        self::assertSame($wrapper, $result->wrapper);
    }
}
