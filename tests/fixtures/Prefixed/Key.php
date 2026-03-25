<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Test\Fixtures\Prefixed;

use Respect\Fluent\Attributes\Composable;
use Respect\Fluent\Attributes\ComposableParameter;

#[Composable(self::class)]
final class Key
{
    public function __construct(
        #[ComposableParameter]
        public readonly string $key,
        public readonly object $inner,
    ) {
    }
}
