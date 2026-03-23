<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent;

interface FluentResolver
{
    public function resolve(FluentNode $nodeSpec): FluentNode;

    public function unresolve(FluentNode $nodeSpec): FluentNode;
}
