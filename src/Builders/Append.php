<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Builders;

use function array_values;

/** @extends FluentBuilder<list<object>, mixed, never> */
readonly class Append extends FluentBuilder
{
    public function attach(object ...$nodes): static
    {
        return clone ($this, ['nodes' => [...$this->nodes, ...array_values($nodes)]]);
    }
}
