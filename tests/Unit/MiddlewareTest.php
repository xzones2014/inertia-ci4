<?php

declare(strict_types=1);

/**
 * This file is part of Inertia.js Codeigniter 4.
 *
 * (c) 2023 Fab IT Hub <hello@fabithub.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Unit;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use Config\App;
use Inertia\AlwaysProp;
use Inertia\Middleware;
use Tests\TestCase;

uses(TestCase::class);

function makeMiddlewareRequest(string $uri = 'http://example.com/test'): IncomingRequest
{
    return new IncomingRequest(
        new App(),
        new URI($uri),
        null,
        new UserAgent()
    );
}

// ──────────────────────────────────────────────
// Version
// ──────────────────────────────────────────────
describe('Middleware Version', function () {
    it('returns null when no manifest files exist', function () {
        $middleware = new Middleware();
        $request   = makeMiddlewareRequest();

        expect($middleware->version($request))->toBeNull();
    });

    it('can be overridden in subclass', function () {
        $middleware = new class () extends Middleware {
            public function version(RequestInterface $request): ?string
            {
                return 'custom-hash-123';
            }
        };

        expect($middleware->version(makeMiddlewareRequest()))->toBe('custom-hash-123');
    });
});

// ──────────────────────────────────────────────
// Shared Data
// ──────────────────────────────────────────────
describe('Middleware Share', function () {
    it('shares errors wrapped in AlwaysProp', function () {
        $middleware = new Middleware();
        $shared    = $middleware->share(makeMiddlewareRequest());

        expect($shared)->toHaveKey('errors');
        expect($shared['errors'])->toBeInstanceOf(AlwaysProp::class);
    });

    it('can extend shared data in subclass', function () {
        $middleware = new class () extends Middleware {
            public function share(RequestInterface $request): array
            {
                return array_merge(parent::share($request), [
                    'flash'  => fn () => ['success' => null, 'error' => null],
                    'locale' => fn () => 'en',
                ]);
            }
        };

        $shared = $middleware->share(makeMiddlewareRequest());

        expect($shared)->toHaveKey('errors');
        expect($shared)->toHaveKey('flash');
        expect($shared)->toHaveKey('locale');
    });
});

// ──────────────────────────────────────────────
// Root View
// ──────────────────────────────────────────────
describe('Middleware Root View', function () {
    it('returns default root view', function () {
        $middleware = new Middleware();

        expect($middleware->rootView(makeMiddlewareRequest()))->toBe('app');
    });

    it('can be customized via property in subclass', function () {
        $middleware = new class () extends Middleware {
            protected string $rootView = 'admin';
        };

        expect($middleware->rootView(makeMiddlewareRequest()))->toBe('admin');
    });

    it('can be customized via method override', function () {
        $middleware = new class () extends Middleware {
            public function rootView(RequestInterface $request): string
            {
                return 'dashboard';
            }
        };

        expect($middleware->rootView(makeMiddlewareRequest()))->toBe('dashboard');
    });
});

// ──────────────────────────────────────────────
// Validation Error Resolution
// ──────────────────────────────────────────────
describe('Middleware Validation Errors', function () {
    it('returns empty object when no errors', function () {
        $middleware = new Middleware();
        $errors    = $middleware->resolveValidationErrors(makeMiddlewareRequest());

        expect($errors)->toEqual((object) []);
    });

    it('wraps errors in error bag when header present', function () {
        $middleware = new Middleware();

        // Flash some errors to session
        session()->setFlashdata('errors', ['name' => 'Name is required']);

        $request = makeMiddlewareRequest();
        $request->setHeader('X-Inertia-Error-Bag', 'createUser');

        $errors = $middleware->resolveValidationErrors($request);

        expect($errors)->toHaveProperty('createUser');
    });
});
