<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Builders;

use function array_values;

readonly class Prepend extends FluentBuilder
{
    public function attach(object ...$nodes): static
    {
        return clone ($this, ['nodes' => [...array_values($nodes), ...$this->nodes]]);
    }
}
