<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Composable
{
    /**
     * @param array<string> $without
     * @param array<string> $with
     */
    public function __construct(
        public string $prefix = '',
        public bool $prefixParameter = false,
        public bool $optIn = false,
        public array $without = [],
        public array $with = [],
    ) {
    }
}
