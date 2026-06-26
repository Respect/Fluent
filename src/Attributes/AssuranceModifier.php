<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Attributes;

enum AssuranceModifier: string
{
    /** The wrapped node's assurance is negated: passing implies NOT the type */
    case Exclude = 'exclude';
}
