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

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use Config\App;
use Inertia\Config\Services;
use Inertia\EncryptHistoryMiddleware;
use Inertia\Inertia;
use Inertia\ResponseFactory;
use ReflectionProperty;
use Tests\TestCase;

uses(TestCase::class);

function makeEncryptRequest(string $uri = 'http://example.com/test'): IncomingRequest
{
    return new IncomingRequest(
        new App(),
        new URI($uri),
        null,
        new UserAgent()
    );
}

// ──────────────────────────────────────────────
// before()
// ──────────────────────────────────────────────
describe('EncryptHistoryMiddleware before', function () {
    it('enables history encryption', function () {
        $middleware = new EncryptHistoryMiddleware();
        $request   = makeEncryptRequest();

        $middleware->before($request);

        // Verify encryptHistory was set on the Inertia facade
        $factory = Inertia::__callStatic('getShared', []);
        $ref     = new ReflectionProperty(ResponseFactory::class, 'encryptHistory');
        $ref->setAccessible(true);

        $instance = Services::inertia();
        expect($ref->getValue($instance))->toBeTrue();
    });

    it('returns the request', function () {
        $middleware = new EncryptHistoryMiddleware();
        $request   = makeEncryptRequest();

        $result = $middleware->before($request);

        expect($result)->toBe($request);
    });
});

// ──────────────────────────────────────────────
// after()
// ──────────────────────────────────────────────
describe('EncryptHistoryMiddleware after', function () {
    it('returns null', function () {
        $middleware = new EncryptHistoryMiddleware();
        $request   = makeEncryptRequest();
        $response  = \response();

        $result = $middleware->after($request, $response);

        expect($result)->toBeNull();
    });
});

// ──────────────────────────────────────────────
// FilterInterface compliance
// ──────────────────────────────────────────────
describe('EncryptHistoryMiddleware interface', function () {
    it('implements FilterInterface', function () {
        $middleware = new EncryptHistoryMiddleware();

        expect($middleware)->toBeInstanceOf(FilterInterface::class);
    });
});
