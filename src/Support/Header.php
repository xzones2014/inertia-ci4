<?php

/**
 * This file is part of Inertia.js Codeigniter 4.
 *
 * (c) 2023 Fab IT Hub <hello@fabithub.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Inertia\Support;

/**
 * Inertia.js HTTP header constants.
 */
class Header
{
    /**
     * The main Inertia request header.
     */
    public const INERTIA = 'X-Inertia';

    /**
     * Header for specifying which error bag to use for validation errors.
     */
    public const ERROR_BAG = 'X-Inertia-Error-Bag';

    /**
     * Header for external redirects.
     */
    public const LOCATION = 'X-Inertia-Location';

    /**
     * Header for the current asset version.
     */
    public const VERSION = 'X-Inertia-Version';

    /**
     * Header specifying the component for partial reloads.
     */
    public const PARTIAL_COMPONENT = 'X-Inertia-Partial-Component';

    /**
     * Header specifying which props to include in partial reloads.
     */
    public const PARTIAL_ONLY = 'X-Inertia-Partial-Data';

    /**
     * Header specifying which props to exclude from partial reloads.
     */
    public const PARTIAL_EXCEPT = 'X-Inertia-Partial-Except';

    /**
     * Header for resetting specific props during partial reloads.
     */
    public const RESET = 'X-Inertia-Reset';
}
