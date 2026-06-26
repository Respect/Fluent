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
use Respect\Fluent\Attributes\AssuranceSubject;
use Respect\Fluent\Attributes\AssuranceSubjectMode;

#[CoversClass(AssuranceSubject::class)]
final class AssuranceSubjectTest extends TestCase
{
    #[Test]
    public function holdsModeAndOptionalType(): void
    {
        $wrap = new AssuranceSubject(AssuranceSubjectMode::Wrap);
        $container = new AssuranceSubject(AssuranceSubjectMode::Container, ['array', 'ArrayAccess']);

        self::assertSame(AssuranceSubjectMode::Wrap, $wrap->mode);
        self::assertNull($wrap->type);
        self::assertSame(AssuranceSubjectMode::Container, $container->mode);
        self::assertSame(['array', 'ArrayAccess'], $container->type);
    }

    #[Test]
    public function targetsClassesOnly(): void
    {
        $reflection = new ReflectionClass(AssuranceSubject::class);
        $attrs = $reflection->getAttributes(Attribute::class);

        self::assertCount(1, $attrs);
        self::assertSame(Attribute::TARGET_CLASS, $attrs[0]->newInstance()->flags);
    }
}
