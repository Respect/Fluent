<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Assurance
{
    /**
     * @param string|list<string>|null $type
     * @param array{int, int|null}|null $composeRange
     */
    public function __construct(
        public string|array|null $type = null,
        public AssuranceFrom|null $from = null,
        public AssuranceCompose|null $compose = null,
        public array|null $composeRange = null,
        public AssuranceModifier|null $modifier = null,
        public bool $exact = false,
    ) {
    }
}
