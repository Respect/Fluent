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
use Respect\Fluent\Resolvers\Suffix;

#[CoversClass(Suffix::class)]
final class SuffixTest extends TestCase
{
    #[Test]
    public function itShouldStripPrefixAndAppendSuffix(): void
    {
        $resolver = new Suffix('of', 'Handler');

        self::assertSame('FooHandler', $resolver->resolve(new FluentNode('ofFoo'))->name);
    }

    #[Test]
    public function itShouldWorkWithEmptyPrefix(): void
    {
        $resolver = new Suffix('', 'Handler');

        self::assertSame('FooHandler', $resolver->resolve(new FluentNode('foo'))->name);
    }

    #[Test]
    public function itShouldWorkWithEmptySuffix(): void
    {
        $resolver = new Suffix('of', '');

        self::assertSame('Foo', $resolver->resolve(new FluentNode('ofFoo'))->name);
    }

    #[Test]
    public function itShouldPreserveArguments(): void
    {
        $resolver = new Suffix('of', 'Handler');
        $result = $resolver->resolve(new FluentNode('ofFoo', [42]));

        self::assertSame('FooHandler', $result->name);
        self::assertSame([42], $result->arguments);
    }
}
