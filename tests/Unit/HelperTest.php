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
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use Config\App;
use Config\Services;
use Inertia\ResponseFactory;
use Inertia\Support\Header;
use Tests\TestCase;

uses(TestCase::class);

function injectInertiaRequest(): void
{
    $request = new IncomingRequest(new App(), new URI('http://example.com'), null, new UserAgent());
    $request->setHeader(Header::INERTIA, 'true');
    Services::injectMock('request', $request);
}

// ──────────────────────────────────────────────
// inertia() helper
// ──────────────────────────────────────────────
describe('inertia() helper', function () {
    it('returns a ResponseFactory instance when called without arguments', function () {
        $result = \inertia();

        expect($result)->toBeInstanceOf(ResponseFactory::class);
    });

    it('returns a string when called with a component name', function () {
        injectInertiaRequest();

        $result = \inertia('TestComponent');

        expect($result)->toBeString();
    });

    it('passes props to render when component is given', function () {
        injectInertiaRequest();

        $result = \inertia('TestComponent', ['key' => 'value']);

        expect($result)->toBeString();
        expect($result)->toContain('TestComponent');
    });

    it('returns the shared service instance', function () {
        $a = \inertia();
        $b = \inertia();

        // Both should be the same shared instance
        expect($a)->toBe($b);
    });
});

// ──────────────────────────────────────────────
// inertia_location() helper
// ──────────────────────────────────────────────
describe('inertia_location() helper', function () {
    it('returns a response instance', function () {
        $result = \inertia_location('https://example.com');

        expect($result)->toBeInstanceOf(ResponseInterface::class);
    });

    it('redirects to the given URL for non-inertia requests', function () {
        $result = \inertia_location('https://example.com');

        expect($result->getStatusCode())->toBe(303);
    });
});
