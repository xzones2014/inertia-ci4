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
use Config\View as ConfigView;
use Inertia\Extras\Arr;
use Inertia\Extras\Http;
use Inertia\Support\Header;

/**
 * @psalm-api
 */
class Response
{
    use ResolvesCallables;

    /**
     * @var array<string, mixed>
     */
    protected array $props = [];

    /**
     * @var array<string, mixed>
     */
    protected array $viewData = [];

    protected string $component = '';
    protected string $rootView  = 'app';
    protected string $version   = '';
    protected bool $clearHistory   = false;
    protected bool $encryptHistory = false;

    /**
     * @param array<string, mixed> $props
     */
    public function __construct(
        string $component,
        array $props,
        string $version = '',
        string $rootView = 'app',
        bool $encryptHistory = false,
        bool $clearHistory = false,
    ) {
        $this->component = $component;
        $this->props = $props;
        $this->version = $version;
        $this->rootView = $rootView;
        $this->encryptHistory = $encryptHistory;
        $this->clearHistory = $clearHistory;
    }

    /**
     * @param array<string, mixed>|string $key
     * @param mixed                       $value
     *
     * @return $this
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function with($key, $value = null): self
    {
        if (is_array($key)) {
            $this->props = array_merge($this->props, $key);
        } else {
            $this->props[$key] = $value;
        }

        return $this;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function withComponent(string $component): static
    {
        $this->component = $component;

        return $this;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function withVersion(string $version): static
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @param array<string, mixed>|string $key
     * @param mixed                       $value
     *
     * @return $this
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function withViewData($key, $value = null): self
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }

        return $this;
    }

    public function toResponse(?RequestInterface $request = null): ResponseInterface|View
    {
        $request ??= request();

        $props = $this->resolveProperties($request, $this->props);

        $page = array_merge(
            [
                'component'      => $this->component,
                'props'          => $props,
                'url'            => $request->getUri()->getPath(),
                'version'        => $this->version,
                'clearHistory'   => $this->clearHistory,
                'encryptHistory' => $this->encryptHistory,
            ],
            $this->resolveMergeProps($request),
            $this->resolveDeferredProps($request),
        );

        if (Http::isInertiaRequest($request)) {
            return \response()->setJSON($page, true)
                ->setHeader('Vary', Header::INERTIA)
                ->setHeader(Header::INERTIA, 'true');
        }

        $view = new View(new ConfigView(), '');
        $view->setData($this->viewData + ['page' => $page], 'raw');

        return $view;
    }

    /**
     * Resolve the properties for the response.
     *
     * @param array<string, mixed> $props
     *
     * @return array<string, mixed>
     */
    public function resolveProperties(RequestInterface $request, array $props): array
    {
        $props = $this->resolvePartialProperties($props, $request);

        return $this->resolvePropertyInstances($props, $request);
    }

    /**
     * Resolve properties for partial requests. Filters properties based on
     * 'only' and 'except' headers from the client, allowing for selective
     * data loading to improve performance.
     *
     * @param array<string, mixed> $props
     *
     * @return array<string, mixed>
     */
    public function resolvePartialProperties(array $props, RequestInterface $request): array
    {
        if (!$this->isPartial($request)) {
            return array_filter($props, static fn ($prop) => !($prop instanceof IgnoreFirstLoad)
                    && !($prop instanceof Deferrable && $prop->shouldDefer()));
        }

        $only   = $this->getOnlyProps($request);
        $except = $this->getExceptProps($request);

        if ($only) {
            $props = Arr::only($props, $only);
        }

        if ($except) {
            foreach ($except as $key) {
                unset($props[$key]);
            }
        }

        // Always include AlwaysProp instances even in partial reloads
        return $this->resolveAlways($props);
    }

    /**
     * Resolve `always` properties that should always be included.
     *
     * @param array<string, mixed> $props
     *
     * @return array<string, mixed>
     */
    public function resolveAlways(array $props): array
    {
        $always = array_filter($this->props, static fn ($prop) => $prop instanceof AlwaysProp);

        return array_merge($always, $props);
    }

    /**
     * Resolve all necessary class instances in the given props.
     *
     * @param array<string, mixed> $props
     *
     * @return array<string, mixed>
     */
    public function resolvePropertyInstances(array $props, RequestInterface $request): array
    {
        foreach ($props as $key => $value) {
            if (
                $value instanceof Closure
                || $value instanceof LazyProp
                || $value instanceof OptionalProp
                || $value instanceof DeferProp
                || $value instanceof AlwaysProp
                || $value instanceof MergeProp
            ) {
                $value = $this->resolveCallable($value);
            }

            if (is_array($value)) {
                $value = $this->resolvePropertyInstances($value, $request);
            }

            $props[$key] = $value;
        }

        return $props;
    }

    /**
     * Resolve merge props configuration for client-side prop merging.
     *
     * @return array<string, mixed>
     *
     * @psalm-suppress PossiblyUnusedParam
     */
    public function resolveMergeProps(RequestInterface $request): array
    {
        $mergeProps = array_filter($this->props, static fn ($prop) => $prop instanceof Mergeable && $prop->shouldMerge());

        if (empty($mergeProps)) {
            return [];
        }

        $result = [];

        $regularMerge = array_keys(array_filter($mergeProps, static fn ($prop) => !$prop->shouldDeepMerge()));

        $deepMerge = array_keys(array_filter($mergeProps, static fn ($prop) => $prop->shouldDeepMerge()));

        if (!empty($regularMerge)) {
            $result['mergeProps'] = array_values($regularMerge);
        }

        if (!empty($deepMerge)) {
            $result['deepMergeProps'] = array_values($deepMerge);
        }

        return $result;
    }

    /**
     * Resolve deferred props configuration for client-side lazy loading.
     *
     * @return array<string, mixed>
     */
    public function resolveDeferredProps(RequestInterface $request): array
    {
        if ($this->isPartial($request)) {
            return [];
        }

        $deferred = array_filter($this->props, static fn ($prop) => $prop instanceof Deferrable && $prop->shouldDefer());

        if (empty($deferred)) {
            return [];
        }

        $groups = [];

        foreach ($deferred as $key => $prop) {
            $group           = $prop->group();
            $groups[$group][] = $key;
        }

        return ['deferredProps' => $groups];
    }

    /**
     * Determine if this is a partial reload request.
     */
    protected function isPartial(RequestInterface $request): bool
    {
        return Http::getHeaderValue(Header::PARTIAL_COMPONENT, '', $request) === $this->component;
    }

    /**
     * Determine if this is an Inertia request.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    protected function isInertia(RequestInterface $request): bool
    {
        return Http::isInertiaRequest($request);
    }

    /**
     * Get the 'only' props from the request headers.
     *
     * @return list<string>|null
     */
    protected function getOnlyProps(RequestInterface $request): ?array
    {
        $header = Http::getHeaderValue(Header::PARTIAL_ONLY, '', $request);

        return $header ? array_filter(explode(',', $header)) : null;
    }

    /**
     * Get the 'except' props from the request headers.
     *
     * @return list<string>|null
     */
    protected function getExceptProps(RequestInterface $request): ?array
    {
        $header = Http::getHeaderValue(Header::PARTIAL_EXCEPT, '', $request);

        return $header ? array_filter(explode(',', $header)) : null;
    }
}
