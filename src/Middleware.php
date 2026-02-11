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
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Validation\ValidationInterface;
use Inertia\Extras\Http;
use Inertia\Support\Header;

/**
 * @psalm-api
 */
class Middleware implements FilterInterface
{
    /**
     * The root view template for Inertia responses.
     * Override this in your subclass or override rootView() for dynamic resolution.
     */
    protected string $rootView = 'app';

    /**
     * Determines the current asset version.
     * Override this method to provide custom versioning logic.
     */
    public function version(RequestInterface $request): ?string
    {
        $manifests = [
            FCPATH . 'build/manifest.json',
            FCPATH . 'mix-manifest.json',
        ];

        foreach ($manifests as $manifest) {
            if (file_exists($manifest)) {
                return hash_file('xxh128', $manifest);
            }
        }

        return null;
    }

    /**
     * Define the props shared by default.
     * Override this method to add your own shared data.
     *
     * @return array<string, mixed>
     */
    public function share(RequestInterface $request): array
    {
        return [
            'errors' => Inertia::always(fn () => $this->resolveValidationErrors($request)),
        ];
    }

    /**
     * Resolve the root view for the given request.
     * Override this method to return a different root view per request.
     */
    public function rootView(RequestInterface $request): string
    {
        return $this->rootView;
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    public function before(RequestInterface $request, $arguments = null): RequestInterface|ResponseInterface|string|null
    {
        Inertia::version(fn () => $this->version($request));
        Inertia::share($this->share($request));
        Inertia::setRootView($this->rootView($request));

        return $request;
    }

    /**
     * Handle the outgoing response.
     *
     * @param null $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('Vary', Header::INERTIA);

        if (! $request->hasHeader(Header::INERTIA)) {
            return $response;
        }

        if (
            strtolower($request->getMethod()) === 'get'
            && Http::getHeaderValue(Header::VERSION, '', $request) !== Inertia::getVersion()
        ) {
            $response = $this->onVersionChange($request, $response);
        }

        if ($response->getStatusCode() === 200 && empty($response->getJSON())) {
            $response = $this->onEmptyResponse($request, $response);
        }

        if (
            $response->getStatusCode() === 302
            && in_array(strtoupper($request->getMethod()), ['PUT', 'PATCH', 'DELETE'], true)
        ) {
            $response->setStatusCode(303);
        }

        return $response;
    }

    /**
     * Handle an empty Inertia response by redirecting back.
     * Override this method to customize the behavior.
     */
    public function onEmptyResponse(RequestInterface $request, ResponseInterface $response): RedirectResponse
    {
        return \redirect()->back();
    }

    /**
     * Handle a version change by forcing a full page reload via location redirect.
     * Override this method to customize the behavior.
     */
    public function onVersionChange(RequestInterface $request, ResponseInterface $response): RedirectResponse|ResponseInterface
    {
        return Inertia::location($request->getUri());
    }

    /**
     * Resolves and prepares validation errors in such
     * a way that they are easier to use client-side.
     */
    public function resolveValidationErrors(RequestInterface $request): object
    {
        service('session');

        /** @var ValidationInterface */
        $validation = service('validation');

        $errors = session()->getFlashdata('errors') ?? $validation->getErrors();

        if (! $errors) {
            return (object) [];
        }

        if ($request->hasHeader(Header::ERROR_BAG)) {
            return (object) [Http::getHeaderValue(Header::ERROR_BAG, '', $request) => $errors];
        }

        return (object) $errors;
    }
}
