<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Attributes;

/**
 * How the assured type is derived from the indexed argument (see #[AssuranceParameter],
 * which selects which argument; this enum selects the derivation).
 */
enum AssuranceFrom: string
{
    /** The argument's own value type is the assured type (e.g. identical($x)). */
    case Value = 'value';

    /** The argument is a haystack; the assured type is its member type (e.g. in($haystack)). */
    case Member = 'member';

    /** The argument validates elements; the assured type is iterable of them (e.g. each($v)). */
    case Elements = 'elements';

    /** The argument is a class-string; the assured type is an instance of it (e.g. instance($class)). */
    case TypeString = 'type-string';
}
