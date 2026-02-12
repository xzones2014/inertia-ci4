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
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\View\View;
use Inertia\Extras\Arr;
use Inertia\Extras\Http;
use Inertia\Support\Header;

/**
 * @psalm-api
 */
class ResponseFactory
{
    /**
     * The root view template name.
     */
    protected string $rootView = 'app';

    /**
     * @var array<string, mixed>
     */
    protected array $sharedProps = [];

    /**
     * @var Closure|string|null
     */
    protected $version;

    /**
     * Whether the next response should clear the browser history.
     */
    protected bool $shouldClearHistory = false;

    /**
     * Whether the history should be encrypted. Null means use config default.
     */
    protected ?bool $encryptHistory = null;

    /**
     * Set the root view template name.
     *
     * @psalm-api
     */
    public function setRootView(string $name): void
    {
        $this->rootView = $name;
    }

    /**
     * @param array<string, mixed>|string $key
     * @param mixed                       $value
     *
     * @psalm-api
     */
    public function share(array|string $key, $value = null): void
    {
        if (is_array($key)) {
            $this->sharedProps = array_merge($this->sharedProps, $key);
        } else {
            Arr::set($this->sharedProps, $key, $value);
        }
    }

    /**
     * @param mixed $default
     *
     * @return array<string, mixed>
     *
     * @psalm-api
     */
    public function getShared(?string $key = null, $default = null)
    {
        if ($key) {
            return Arr::get($this->sharedProps, $key, $default);
        }

        return $this->sharedProps;
    }

    /**
     * @psalm-api
     */
    public function flushShared(): void
    {
        $this->sharedProps = [];
    }

    /**
     * @param Closure|string|null $version
     *
     * @psalm-api
     */
    public function version($version): void
    {
        $this->version = $version;
    }

    /**
     * @psalm-api
     */
    public function getVersion(): string
    {
        return (string) Arr::value($this->version);
    }

    /**
     * Mark the next response to clear the browser history.
     *
     * @psalm-api
     */
    public function clearHistory(): void
    {
        $this->shouldClearHistory = true;
    }

    /**
     * Enable or disable history encryption for subsequent responses.
     *
     * @psalm-api
     */
    public function encryptHistory(bool $encrypt = true): void
    {
        $this->encryptHistory = $encrypt;
    }

    /**
     * Create a lazy (deprecated) prop instance.
     *
     * @deprecated Use optional() instead.
     *
     * @psalm-api
     */
    public function lazy(callable $callback): LazyProp
    {
        return new LazyProp($callback);
    }

    /**
     * Create an optional prop instance.
     * Optional props are excluded from the initial page load
     * and only included when explicitly requested.
     *
     * @psalm-api
     */
    public function optional(callable $callback): OptionalProp
    {
        return new OptionalProp($callback);
    }

    /**
     * Create a deferred prop instance.
     * Deferred props are loaded asynchronously after the initial page load.
     *
     * @psalm-api
     */
    public function defer(callable $callback, string $group = 'default'): DeferProp
    {
        return new DeferProp($callback, $group);
    }

    /**
     * Create a merge prop instance.
     * Merge props are combined with existing client-side data instead of replacing.
     *
     * @psalm-api
     */
    public function merge(mixed $value): MergeProp
    {
        return new MergeProp($value);
    }

    /**
     * Create a deep merge prop instance.
     *
     * @psalm-api
     */
    public function deepMerge(mixed $value): MergeProp
    {
        return (new MergeProp($value))->deepMerge();
    }

    /**
     * Create an always prop instance.
     * Always props are included in every response, even during partial reloads.
     *
     * @psalm-api
     */
    public function always(mixed $value): AlwaysProp
    {
        return new AlwaysProp($value);
    }

    /**
     * @psalm-api
     *
     * @param array<string, mixed> $props
     */
    public function render(string $component, array $props = []): string
    {
        /** @var Config\Inertia */
        $config = \config('Inertia');

        $response = new Response(
            $component,
            array_merge($this->sharedProps, $props),
            $this->getVersion(),
            $this->rootView,
            $this->encryptHistory ?? $config->encryptHistory ?? false,
            $this->shouldClearHistory,
        );

        $this->shouldClearHistory = false;

        $result = $response->toResponse();

        if ($result instanceof View) {
            return $result->render($this->rootView);
        }

        return $result->getJSON();
    }

    /**
     * @psalm-api
     */
    public function location(RequestInterface|string $url): ResponseInterface
    {
        if ($url instanceof RequestInterface) {
            $url = $url->getUri();
        }

        if (Http::isInertiaRequest()) {
            session()->set('_ci_previous_url', $url);

            return \response()->setStatusCode(409)->setHeader(Header::LOCATION, $url);
        }

        return \redirect()->to($url, 303);
    }

    /**
     * @param array{component: string, version: string, url: string, props: array<string, mixed>} $page
     *
     * @psalm-api
     */
    public static function init(array $page, bool $isHead = false): string
    {
        if ($isHead) {
            return Directive::compileHead($page);
        }

        return Directive::compile($page);
    }
}
