<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Test\Fixtures;

use Respect\Fluent\Builders\Append;
use Respect\Fluent\Factories\NamespaceLookup;
use Respect\Fluent\Resolvers\Ucfirst;

final readonly class SimpleBuilder extends Append
{
    public function __construct(object ...$nodes)
    {
        parent::__construct(
            new NamespaceLookup(
                new Ucfirst(),
                null,
                'Respect\\Fluent\\Test\\Fixtures\\NsA',
            ),
            ...$nodes,
        );
    }
}
