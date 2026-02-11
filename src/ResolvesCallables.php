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

use Closure;

/**
 * Trait for resolving callable values.
 * String function names are not invoked for safety.
 */
trait ResolvesCallables
{
    /**
     * Resolve a callable value, invoking closures and callables
     * but leaving string function names untouched.
     */
    protected function resolveCallable(mixed $callable): mixed
    {
        if ($callable instanceof Closure) {
            return $callable();
        }

        if (is_callable($callable) && ! is_string($callable)) {
            return $callable();
        }

        return $callable;
    }
}
