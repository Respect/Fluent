<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Attributes;

enum AssuranceFrom: string
{
    case Value = 'value';
    case Member = 'member';
    case Elements = 'elements';
}
