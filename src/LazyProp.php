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
 * @deprecated Use OptionalProp instead for clearer semantics.
 *
 * @psalm-api
 */
class LazyProp implements IgnoreFirstLoad
{
    use ResolvesCallables;

    /**
     * @var callable
     */
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Resolve the property value.
     */
    public function __invoke(): mixed
    {
        return $this->resolveCallable($this->callback);
    }
}
