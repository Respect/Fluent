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

use function lcfirst;
use function str_ends_with;
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

    public function unresolve(FluentNode $nodeSpec): FluentNode
    {
        $name = $nodeSpec->name;

        if ($this->suffix !== '' && str_ends_with($name, $this->suffix)) {
            $name = substr($name, 0, -strlen($this->suffix));
        }

        $name = $this->prefix . lcfirst($name);

        return new FluentNode($name, $nodeSpec->arguments, $nodeSpec->wrapper);
    }
}
