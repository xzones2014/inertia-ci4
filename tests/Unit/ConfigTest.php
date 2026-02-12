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

use Inertia\Config\Inertia as InertiaConfig;
use Inertia\Config\Services;
use Inertia\ResponseFactory;
use Inertia\Ssr\HttpGateway;
use Tests\TestCase;

uses(TestCase::class);

// ──────────────────────────────────────────────
// Inertia Config
// ──────────────────────────────────────────────
describe('Inertia Config', function () {
    it('has default rootView of app', function () {
        $config = new InertiaConfig();

        expect($config->rootView)->toBe('app');
    });

    it('has SSR disabled by default', function () {
        $config = new InertiaConfig();

        expect($config->isSsrEnabled)->toBeFalse();
    });

    it('has default SSR URL', function () {
        $config = new InertiaConfig();

        expect($config->ssrUrl)->toBe('http://127.0.0.1:13714');
    });

    it('has encryptHistory disabled by default', function () {
        $config = new InertiaConfig();

        expect($config->encryptHistory)->toBeFalse();
    });
});

// ──────────────────────────────────────────────
// Services
// ──────────────────────────────────────────────
describe('Services', function () {
    it('returns a ResponseFactory from inertia()', function () {
        $factory = Services::inertia(false);

        expect($factory)->toBeInstanceOf(ResponseFactory::class);
    });

    it('returns a shared ResponseFactory instance', function () {
        $a = Services::inertia();
        $b = Services::inertia();

        expect($a)->toBe($b);
    });

    it('returns an HttpGateway from httpGateway()', function () {
        $gateway = Services::httpGateway(false);

        expect($gateway)->toBeInstanceOf(HttpGateway::class);
    });

    it('returns a shared HttpGateway instance', function () {
        $a = Services::httpGateway();
        $b = Services::httpGateway();

        expect($a)->toBe($b);
    });
});
