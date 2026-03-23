<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent;

use ReflectionClass;

interface ReflectiveFactory extends FluentFactory
{
    /** @return ReflectionClass<object> */
    public function resolve(string $name): ReflectionClass;
}
