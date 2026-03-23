<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Attributes;

use Attribute;
use Respect\Fluent\FluentFactory;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class FluentNamespace
{
    public function __construct(
        public FluentFactory $factory,
    ) {
    }
}
