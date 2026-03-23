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

use function strlen;
use function substr;
use function ucfirst;

final readonly class Suffix implements FluentResolver
{
    public function __construct(
        private string $prefix,
        private string $suffix,
    ) {
    }

    public function resolve(FluentNode $nodeSpec): FluentNode
    {
        $name = (substr($nodeSpec->name, strlen($this->prefix)) |> ucfirst(...)) . $this->suffix;

        return new FluentNode($name, $nodeSpec->arguments, $nodeSpec->wrapper);
    }
}
