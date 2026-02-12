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

use Inertia\Ssr\Response as SsrResponse;

// ──────────────────────────────────────────────
// SSR Response
// ──────────────────────────────────────────────
describe('Ssr Response', function () {
    it('stores head and body content', function () {
        $response = new SsrResponse('<title>Test</title>', '<div>Content</div>');

        expect($response->head)->toBe('<title>Test</title>');
        expect($response->body)->toBe('<div>Content</div>');
    });

    it('allows empty head and body', function () {
        $response = new SsrResponse('', '');

        expect($response->head)->toBe('');
        expect($response->body)->toBe('');
    });

    it('preserves HTML content with special characters', function () {
        $head = '<meta charset="UTF-8" /><title>Test &amp; Page</title>';
        $body = '<div id="app" data-page="{&quot;foo&quot;:&quot;bar&quot;}"></div>';

        $response = new SsrResponse($head, $body);

        expect($response->head)->toBe($head);
        expect($response->body)->toBe($body);
    });
});
