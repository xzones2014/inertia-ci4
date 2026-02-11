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
 * Always props are included in every response,
 * even during partial reloads.
 *
 * @psalm-api
 */
class AlwaysProp
{
    use ResolvesCallables;

    protected mixed $value;

    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * Resolve the property value.
     */
    public function __invoke(): mixed
    {
        return $this->resolveCallable($this->value);
    }
}
