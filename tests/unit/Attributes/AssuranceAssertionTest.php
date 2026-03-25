<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\Fluent\Test\Unit\Attributes;

use Attribute;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Respect\Fluent\Attributes\AssuranceAssertion;

#[CoversClass(AssuranceAssertion::class)]
final class AssuranceAssertionTest extends TestCase
{
    #[Test]
    public function canBeInstantiated(): void
    {
        $attr = new AssuranceAssertion();

        self::assertInstanceOf(AssuranceAssertion::class, $attr);
    }

    #[Test]
    public function targetsMethodsOnly(): void
    {
        $reflection = new ReflectionClass(AssuranceAssertion::class);
        $attrs = $reflection->getAttributes(Attribute::class);

        self::assertCount(1, $attrs);
        self::assertSame(Attribute::TARGET_METHOD, $attrs[0]->newInstance()->flags);
    }
}
