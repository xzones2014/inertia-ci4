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
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use Config\App;
use Inertia\Config\Services;
use Inertia\Inertia;
use Inertia\Middleware;
use Inertia\ResponseFactory;
use Inertia\Support\Header;
use ReflectionProperty;
use Tests\TestCase;

uses(TestCase::class);

function makeLifecycleRequest(string $uri = 'http://example.com/test', string $method = 'GET'): IncomingRequest
{
    $request = new IncomingRequest(
        new App(),
        new URI($uri),
        null,
        new UserAgent()
    );

    $request->setMethod($method);

    return $request;
}

function makeResponse(int $statusCode = 200, ?string $json = null): Response
{
    /** @var Response $response */
    $response = \response();
    $response->setStatusCode($statusCode);

    if ($json !== null) {
        $response->setJSON(json_decode($json, true));
    }

    return $response;
}

// ──────────────────────────────────────────────
// before() lifecycle
// ──────────────────────────────────────────────
describe('Middleware before()', function () {
    it('returns the request', function () {
        $middleware = new Middleware();
        $request   = makeLifecycleRequest();

        $result = $middleware->before($request);

        expect($result)->toBe($request);
    });

    it('shares version from middleware version() method', function () {
        $middleware = new class () extends Middleware {
            public function version(RequestInterface $request): ?string
            {
                return 'v1.0';
            }
        };

        $request = makeLifecycleRequest();
        $middleware->before($request);

        expect(Inertia::getVersion())->toBe('v1.0');
    });

    it('shares errors from middleware share() method', function () {
        $middleware = new Middleware();
        $request   = makeLifecycleRequest();

        $middleware->before($request);

        $shared = Inertia::getShared();
        expect($shared)->toHaveKey('errors');
    });

    it('sets root view from middleware rootView() method', function () {
        $middleware = new class () extends Middleware {
            protected string $rootView = 'custom-layout';
        };

        $request = makeLifecycleRequest();
        $middleware->before($request);

        $factory = Services::inertia();
        $ref     = new ReflectionProperty(ResponseFactory::class, 'rootView');
        $ref->setAccessible(true);

        expect($ref->getValue($factory))->toBe('custom-layout');
    });
});

// ──────────────────────────────────────────────
// after() lifecycle — Vary header
// ──────────────────────────────────────────────
describe('Middleware after() Vary header', function () {
    it('sets Vary header to X-Inertia', function () {
        $middleware = new Middleware();
        $request   = makeLifecycleRequest();
        $response  = makeResponse();

        $result = $middleware->after($request, $response);

        expect($result->getHeaderLine('Vary'))->toBe(Header::INERTIA);
    });

    it('returns response immediately for non-inertia requests', function () {
        $middleware = new Middleware();
        $request   = makeLifecycleRequest();
        $response  = makeResponse(200, '{"data":"test"}');

        $result = $middleware->after($request, $response);

        // Should still be 200, no redirects
        expect($result->getStatusCode())->toBe(200);
    });
});

// ──────────────────────────────────────────────
// after() lifecycle — Version conflict (409)
// ──────────────────────────────────────────────
describe('Middleware after() version conflict', function () {
    it('returns 409 when inertia version mismatches on GET request', function () {
        $middleware = new Middleware();
        $request   = makeLifecycleRequest('http://example.com/test', 'GET');
        $request->setHeader(Header::INERTIA, 'true');
        $request->setHeader(Header::VERSION, 'old-version');

        // Inject as global request so Http::isInertiaRequest() works inside location()
        Services::injectMock('request', $request);

        // Set a different version via Inertia
        Inertia::version('new-version');

        $response = makeResponse();
        $result   = $middleware->after($request, $response);

        expect($result->getStatusCode())->toBe(409);
        expect($result->getHeaderLine(Header::LOCATION))->not->toBeEmpty();
    });

    it('does not trigger 409 when versions match', function () {
        $middleware = new Middleware();
        $request   = makeLifecycleRequest('http://example.com/test', 'GET');
        $request->setHeader(Header::INERTIA, 'true');
        $request->setHeader(Header::VERSION, 'same-version');

        Inertia::version('same-version');

        $response = makeResponse(200, '{"component":"Test"}');
        $result   = $middleware->after($request, $response);

        expect($result->getStatusCode())->toBe(200);
    });

    it('does not trigger 409 for non-GET requests even with version mismatch', function () {
        $middleware = new Middleware();
        $request   = makeLifecycleRequest('http://example.com/test', 'POST');
        $request->setHeader(Header::INERTIA, 'true');
        $request->setHeader(Header::VERSION, 'old-version');

        Inertia::version('new-version');

        $response = makeResponse(200, '{"component":"Test"}');
        $result   = $middleware->after($request, $response);

        // POST should not trigger version check
        expect($result->getStatusCode())->not->toBe(409);
    });
});

// ──────────────────────────────────────────────
// after() lifecycle — 302 → 303 redirect conversion
// ──────────────────────────────────────────────
describe('Middleware after() redirect conversion', function () {
    it('converts 302 to 303 for PUT requests on inertia', function () {
        $middleware = new Middleware();
        $request   = makeLifecycleRequest('http://example.com/test', 'PUT');
        $request->setHeader(Header::INERTIA, 'true');

        $response = makeResponse(302);
        $response->setHeader('Location', '/redirected');

        $result = $middleware->after($request, $response);

        expect($result->getStatusCode())->toBe(303);
    });

    it('converts 302 to 303 for PATCH requests on inertia', function () {
        $middleware = new Middleware();
        $request   = makeLifecycleRequest('http://example.com/test', 'PATCH');
        $request->setHeader(Header::INERTIA, 'true');

        $response = makeResponse(302);
        $response->setHeader('Location', '/redirected');

        $result = $middleware->after($request, $response);

        expect($result->getStatusCode())->toBe(303);
    });

    it('converts 302 to 303 for DELETE requests on inertia', function () {
        $middleware = new Middleware();
        $request   = makeLifecycleRequest('http://example.com/test', 'DELETE');
        $request->setHeader(Header::INERTIA, 'true');

        $response = makeResponse(302);
        $response->setHeader('Location', '/redirected');

        $result = $middleware->after($request, $response);

        expect($result->getStatusCode())->toBe(303);
    });

    it('does not convert 302 to 303 for GET requests', function () {
        $middleware = new Middleware();
        $request   = makeLifecycleRequest('http://example.com/test', 'GET');
        $request->setHeader(Header::INERTIA, 'true');
        $request->setHeader(Header::VERSION, '');

        Inertia::version('');

        $response = makeResponse(302);
        $response->setHeader('Location', '/redirected');

        $result = $middleware->after($request, $response);

        expect($result->getStatusCode())->not->toBe(303);
    });

    it('does not convert non-302 status codes', function () {
        $middleware = new Middleware();
        $request   = makeLifecycleRequest('http://example.com/test', 'PUT');
        $request->setHeader(Header::INERTIA, 'true');

        $response = makeResponse(301);
        $response->setHeader('Location', '/redirected');

        $result = $middleware->after($request, $response);

        expect($result->getStatusCode())->toBe(301);
    });
});

// ──────────────────────────────────────────────
// after() lifecycle — Empty response handling
// ──────────────────────────────────────────────
describe('Middleware after() empty response', function () {
    it('calls onEmptyResponse which returns a redirect', function () {
        $middleware = new Middleware();
        $request   = makeLifecycleRequest();

        $result = $middleware->onEmptyResponse($request, makeResponse());

        // onEmptyResponse should return a RedirectResponse
        expect($result)->toBeInstanceOf(RedirectResponse::class);
    });
});

// ──────────────────────────────────────────────
// after() lifecycle — Version change handling
// ──────────────────────────────────────────────
describe('Middleware after() version change', function () {
    it('calls onVersionChange which returns 409 location redirect', function () {
        $middleware = new Middleware();
        $request   = makeLifecycleRequest();
        $request->setHeader(Header::INERTIA, 'true');
        Services::injectMock('request', $request);

        $result = $middleware->onVersionChange($request, makeResponse());

        expect($result->getStatusCode())->toBe(409);
        expect($result->getHeaderLine(Header::LOCATION))->not->toBeEmpty();
    });
});

// ──────────────────────────────────────────────
// Overridable hooks
// ──────────────────────────────────────────────
describe('Middleware overridable hooks', function () {
    it('allows onEmptyResponse to be overridden', function () {
        $middleware = new class () extends Middleware {
            public function onEmptyResponse(RequestInterface $request, ResponseInterface $response): RedirectResponse
            {
                return \redirect()->to('/custom-empty');
            }
        };

        $request = makeLifecycleRequest();
        $result  = $middleware->onEmptyResponse($request, makeResponse(200));

        expect($result)->toBeInstanceOf(RedirectResponse::class);
    });

    it('allows onVersionChange to be overridden', function () {
        $middleware = new class () extends Middleware {
            public function onVersionChange(RequestInterface $request, ResponseInterface $response): ResponseInterface
            {
                return \response()->setStatusCode(503);
            }
        };

        $request = makeLifecycleRequest('http://example.com/test', 'GET');
        $request->setHeader(Header::INERTIA, 'true');
        $request->setHeader(Header::VERSION, 'old');

        Inertia::version('new');

        $result = $middleware->after($request, makeResponse(200));

        expect($result->getStatusCode())->toBe(503);
    });
});
