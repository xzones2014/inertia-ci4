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

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Middleware to enable history encryption for Inertia responses.
 * Register this filter in app/Config/Filters.php for routes
 * that should encrypt browser history state.
 *
 * @psalm-api
 */
class EncryptHistoryMiddleware implements FilterInterface
{
    /**
     * @param array<int|string, mixed> $arguments
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        Inertia::encryptHistory();

        return $request;
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
