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
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use Config\App;
use Inertia\Extras\Http;
use Inertia\Support\Header;
use Tests\TestCase;

uses(TestCase::class);

function makeHttpRequest(string $uri = 'http://example.com/test'): IncomingRequest
{
    return new IncomingRequest(
        new App(),
        new URI($uri),
        null,
        new UserAgent()
    );
}

// ──────────────────────────────────────────────
// isInertiaRequest()
// ──────────────────────────────────────────────
describe('Http::isInertiaRequest', function () {
    it('returns false for non-inertia requests', function () {
        $request = makeHttpRequest();

        expect(Http::isInertiaRequest($request))->toBeFalse();
    });

    it('returns true when X-Inertia header is present', function () {
        $request = makeHttpRequest();
        $request->setHeader(Header::INERTIA, 'true');

        expect(Http::isInertiaRequest($request))->toBeTrue();
    });

    it('detects inertia request from global request when none passed', function () {
        // When no request is passed, it uses request() which is a non-inertia request
        expect(Http::isInertiaRequest())->toBeFalse();
    });
});

// ──────────────────────────────────────────────
// getHeaderValue()
// ──────────────────────────────────────────────
describe('Http::getHeaderValue', function () {
    it('returns the header value when present', function () {
        $request = makeHttpRequest();
        $request->setHeader(Header::VERSION, 'abc123');

        $value = Http::getHeaderValue(Header::VERSION, '', $request);

        expect($value)->toBe('abc123');
    });

    it('returns default when header is missing', function () {
        $request = makeHttpRequest();

        $value = Http::getHeaderValue(Header::VERSION, 'default-version', $request);

        expect($value)->toBe('default-version');
    });

    it('returns empty string as default when no default given', function () {
        $request = makeHttpRequest();

        $value = Http::getHeaderValue('X-Custom-Header', '', $request);

        expect($value)->toBe('');
    });

    it('returns the partial component header value', function () {
        $request = makeHttpRequest();
        $request->setHeader(Header::PARTIAL_COMPONENT, 'User/Edit');

        $value = Http::getHeaderValue(Header::PARTIAL_COMPONENT, '', $request);

        expect($value)->toBe('User/Edit');
    });

    it('returns the partial only header with comma-separated values', function () {
        $request = makeHttpRequest();
        $request->setHeader(Header::PARTIAL_ONLY, 'name,email,phone');

        $value = Http::getHeaderValue(Header::PARTIAL_ONLY, '', $request);

        expect($value)->toBe('name,email,phone');
    });
});
