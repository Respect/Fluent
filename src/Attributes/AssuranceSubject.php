<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Attributes;

use Attribute;

/**
 * Declares how a composable prefix relates to its wrapped node's subject.
 *
 * A prefix without this attribute yields no assurance for composed names:
 * tools must drop, not copy, the wrapped node's assurance.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AssuranceSubject
{
    /** @param string|list<string>|null $type Container type assured about the input */
    public function __construct(
        public AssuranceSubjectMode $mode,
        public string|array|null $type = null,
    ) {
    }
}
