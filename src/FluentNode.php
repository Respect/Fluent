<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent;

final readonly class FluentNode
{
    /** @param array<int|string, mixed> $arguments */
    public function __construct(
        public string $name,
        public array $arguments = [],
        public FluentNode|null $wrapper = null,
    ) {
    }
}
