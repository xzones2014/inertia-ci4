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

use Inertia\Support\Header;

// ──────────────────────────────────────────────
// Header Constants
// ──────────────────────────────────────────────
describe('Header constants', function () {
    it('defines the main Inertia header', function () {
        expect(Header::INERTIA)->toBe('X-Inertia');
    });

    it('defines the error bag header', function () {
        expect(Header::ERROR_BAG)->toBe('X-Inertia-Error-Bag');
    });

    it('defines the location header', function () {
        expect(Header::LOCATION)->toBe('X-Inertia-Location');
    });

    it('defines the version header', function () {
        expect(Header::VERSION)->toBe('X-Inertia-Version');
    });

    it('defines the partial component header', function () {
        expect(Header::PARTIAL_COMPONENT)->toBe('X-Inertia-Partial-Component');
    });

    it('defines the partial only header', function () {
        expect(Header::PARTIAL_ONLY)->toBe('X-Inertia-Partial-Data');
    });

    it('defines the partial except header', function () {
        expect(Header::PARTIAL_EXCEPT)->toBe('X-Inertia-Partial-Except');
    });

    it('defines the reset header', function () {
        expect(Header::RESET)->toBe('X-Inertia-Reset');
    });
});
