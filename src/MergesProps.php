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
 * Trait for props that support client-side merging.
 */
trait MergesProps
{
    /**
     * Indicates if the property should be merged.
     */
    protected bool $merge = false;

    /**
     * Indicates if the property should be deep merged.
     */
    protected bool $deepMerge = false;

    /**
     * Mark this property for merging with existing client-side data.
     */
    public function merge(): static
    {
        $this->merge = true;

        return $this;
    }

    /**
     * Mark this property for deep merging with existing client-side data.
     */
    public function deepMerge(): static
    {
        $this->merge = true;
        $this->deepMerge = true;

        return $this;
    }

    /**
     * Determine if this property should be merged.
     */
    public function shouldMerge(): bool
    {
        return $this->merge;
    }

    /**
     * Determine if this property should be deep merged.
     */
    public function shouldDeepMerge(): bool
    {
        return $this->deepMerge;
    }
}
