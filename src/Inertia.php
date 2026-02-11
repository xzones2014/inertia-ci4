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
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Inertia\Config\Services;

/**
 * Inertia.
 *
 * @method static void                               setRootView(string $name)
 * @method static void                               share(string|array $key, $value = null)
 * @method static mixed                              getShared(?string $key = null, $default = null)
 * @method static void                               flushShared()
 * @method static void                               version(Closure|string|null $version)
 * @method static string                             getVersion()
 * @method static void                               clearHistory()
 * @method static void                               encryptHistory(bool $encrypt = true)
 * @method static LazyProp                           lazy(callable $callback)
 * @method static OptionalProp                       optional(callable $callback)
 * @method static DeferProp                          defer(callable $callback, string $group = 'default')
 * @method static MergeProp                          merge(mixed $value)
 * @method static MergeProp                          deepMerge(mixed $value)
 * @method static AlwaysProp                         always(mixed $value)
 * @method static string                             render(string $component, array $props = [])
 * @method static RedirectResponse|ResponseInterface location(RequestInterface|string $url)
 * @method static string                             init(array $page, bool $isHead = false)
 *
 * @see ResponseFactory
 */
class Inertia
{
    /**
     * @param array<int|string, mixed> $arguments
     *
     * @psalm-api
     *
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments)
    {
        return Services::inertia()->{$method}(...$arguments);
    }
}
