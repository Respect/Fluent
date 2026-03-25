<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\Fluent\Test\Unit\Attributes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Respect\Fluent\Attributes\Composable;
use Respect\Fluent\Test\Fixtures\Prefixed\Email;
use Respect\Fluent\Test\Fixtures\Prefixed\Key;
use Respect\Fluent\Test\Fixtures\Prefixed\Not;

#[CoversClass(Composable::class)]
final class ComposableTest extends TestCase
{
    #[Test]
    public function prefixStoresClassString(): void
    {
        $attr = new Composable(Not::class);

        self::assertSame(Not::class, $attr->prefix);
    }

    #[Test]
    public function nullPrefixByDefault(): void
    {
        $attr = new Composable();

        self::assertNull($attr->prefix);
    }

    #[Test]
    public function withoutStoresClassStrings(): void
    {
        $attr = new Composable(without: [Not::class, Email::class]);

        self::assertSame([Not::class, Email::class], $attr->without);
    }

    #[Test]
    public function withStoresClassStrings(): void
    {
        $attr = new Composable(with: [Key::class]);

        self::assertSame([Key::class], $attr->with);
    }

    #[Test]
    public function optInPassesThrough(): void
    {
        $attr = new Composable(Key::class, optIn: true);

        self::assertSame(Key::class, $attr->prefix);
        self::assertTrue($attr->optIn);
    }
}
