<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\Fluent\Attributes;

enum AssuranceSubjectMode: string
{
    /**
     * Same subject as the wrapped node: its assurance passes through, modified.
     *
     * A Wrap prefix's own #[Assurance(type:)] declares its bypass set — inputs
     * it admits itself, in union with whatever the wrapped node assures. It is
     * only meaningful in composition, never as a claim about direct calls;
     * `exact` on it means the bypass set is an exact characterization.
     */
    case Wrap = 'wrap';

    /** The wrapped node validates each element: assurance becomes iterable<T> */
    case Elements = 'elements';

    /** The wrapped node validates a derived subject: only the container type is assured */
    case Container = 'container';
}
