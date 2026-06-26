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
use Respect\Fluent\Attributes\Assurance;
use Respect\Fluent\Attributes\AssuranceModifier;

#[CoversClass(Assurance::class)]
final class AssuranceTest extends TestCase
{
    #[Test]
    public function exactDefaultsToFalse(): void
    {
        $assurance = new Assurance(type: 'int');

        self::assertFalse($assurance->exact);
    }

    #[Test]
    public function exactCanBeDeclared(): void
    {
        $assurance = new Assurance(type: 'int', exact: true);

        self::assertTrue($assurance->exact);
    }

    #[Test]
    public function excludeModifierIsDeclarable(): void
    {
        $assurance = new Assurance(modifier: AssuranceModifier::Exclude);

        self::assertSame('exclude', $assurance->modifier?->value);
    }
}
