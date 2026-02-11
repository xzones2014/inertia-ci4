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
 * Trait for props that support deferred loading.
 */
trait DefersProps
{
    /**
     * Indicates if the property should be deferred.
     */
    protected bool $deferred = false;

    /**
     * The defer group.
     */
    protected ?string $deferGroup = null;

    /**
     * Mark this property as deferred.
     */
    public function defer(?string $group = null): static
    {
        $this->deferred = true;
        $this->deferGroup = $group;

        return $this;
    }

    /**
     * Determine if this property should be deferred.
     */
    public function shouldDefer(): bool
    {
        return $this->deferred;
    }

    /**
     * Get the defer group for this property.
     */
    public function group(): string
    {
        return $this->deferGroup ?? 'default';
    }
}
