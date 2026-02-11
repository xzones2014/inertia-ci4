<?php

/**
 * This file is part of Inertia.js Codeigniter 4.
 *
 * (c) 2023 Fab IT Hub <hello@fabithub.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Inertia;

/**
 * Interface for props that can be deferred for async loading.
 */
interface Deferrable
{
    public function shouldDefer(): bool;

    public function group(): string;
}
