<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Resolvers;

use Respect\Fluent\FluentNode;
use Respect\Fluent\FluentResolver;

use function ucfirst;

final readonly class Ucfirst implements FluentResolver
{
    public function resolve(FluentNode $nodeSpec): FluentNode
    {
        return new FluentNode(ucfirst($nodeSpec->name), $nodeSpec->arguments, $nodeSpec->wrapper);
    }
}
