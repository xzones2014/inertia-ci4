<?php

/**
 * This file is part of Inertia.js Codeigniter 4.
 *
 * (c) 2023 Fab IT Hub <hello@fabithub.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

if (! function_exists('inertia')) {
    /**
     * Inertia helper.
     *
     * @param array<string, mixed> $props
     *
     * @return ($component is null ? \Inertia\ResponseFactory : string)
     */
    function inertia(?string $component = null, array $props = [])
    {
        $instance = \Inertia\Config\Services::inertia();

        if ($component) {
            return $instance->render($component, $props);
        }

        return $instance;
    }
}

if (! function_exists('inertia_location')) {
    /**
     * Inertia location helper.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    function inertia_location(string $url)
    {
        $instance = \Inertia\Config\Services::inertia();

        return $instance->location($url);
    }
}
