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
 * Merge properties are combined with existing client-side data
 * during partial reloads instead of replacing the property value.
 *
 * @psalm-api
 */
class MergeProp implements Mergeable
{
    use MergesProps;
    use ResolvesCallables;

    protected mixed $value;

    /**
     * Create a new merge property instance.
     */
    public function __construct(mixed $value)
    {
        $this->value = $value;
        $this->merge = true;
    }

    /**
     * Resolve the property value.
     */
    public function __invoke(): mixed
    {
        return $this->resolveCallable($this->value);
    }
}
