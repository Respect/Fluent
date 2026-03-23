<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Helpers;

use Respect\Fluent\FluentNode;

use function array_slice;

final class Decompose
{
    public static function nodeSpec(
        FluentNode $nodeSpec,
        string $prefix,
        string $suffix,
        bool $prefixParameter,
    ): FluentNode {
        if (!$prefixParameter) {
            return new FluentNode(
                $suffix,
                $nodeSpec->arguments,
                new FluentNode($prefix),
            );
        }

        return new FluentNode(
            $suffix,
            array_slice($nodeSpec->arguments, 1),
            new FluentNode($prefix, [$nodeSpec->arguments[0]]),
        );
    }
}
