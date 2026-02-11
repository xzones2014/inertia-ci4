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
 * Deferred properties are excluded from the initial page load
 * and loaded asynchronously by the frontend, improving initial
 * page performance.
 *
 * @psalm-api
 */
class DeferProp implements Deferrable, IgnoreFirstLoad, Mergeable
{
    use DefersProps;
    use MergesProps;
    use ResolvesCallables;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * Create a new deferred property instance.
     */
    public function __construct(callable $callback, ?string $group = null)
    {
        $this->callback = $callback;
        $this->deferred = true;
        $this->deferGroup = $group;
    }

    /**
     * Resolve the property value.
     */
    public function __invoke(): mixed
    {
        return $this->resolveCallable($this->callback);
    }
}
