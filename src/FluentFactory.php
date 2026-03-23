<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent;

interface FluentFactory
{
    /** @param array<int|string, mixed> $arguments */
    public function create(string $name, array $arguments = []): object;

    public function withNamespace(string $namespace): static;
}
