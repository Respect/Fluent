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
     * @param class-string|null $prefix
     * @param list<class-string> $without
     * @param list<class-string> $with
     */
    public function __construct(
        public string|null $prefix = null,
        public bool $optIn = false,
        public array $without = [],
        public array $with = [],
    ) {
    }
}
