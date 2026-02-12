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

use ReflectionProperty;
use Inertia\Directive;
use Tests\TestCase;

uses(TestCase::class);

function examplePage(): array
{
    return [
        'component' => 'Foo/Bar',
        'props'     => ['foo' => 'bar'],
        'url'       => '/test',
        'version'   => '',
    ];
}

// ──────────────────────────────────────────────
// compile()
// ──────────────────────────────────────────────
describe('Directive compile', function () {
    beforeEach(function () {
        // Reset the SSR cache between tests
        $ref = new ReflectionProperty(Directive::class, '__inertiaSsr');
        $ref->setAccessible(true);
        $ref->setValue(null, false);
    });

    it('renders a root div element with data-page attribute', function () {
        $page = examplePage();
        $html = Directive::compile($page);

        expect($html)->toContain('<div id="app"');
        expect($html)->toContain('data-page="');
        expect($html)->toContain('</div>');
    });

    it('uses default id of app when no expression given', function () {
        $page = examplePage();
        $html = Directive::compile($page);

        expect($html)->toContain('id="app"');
    });

    it('uses custom id when expression is provided', function () {
        $page = examplePage();
        $html = Directive::compile($page, 'my-app');

        expect($html)->toContain('id="my-app"');
    });

    it('trims quotes from expression', function () {
        $page = examplePage();

        expect(Directive::compile($page, "'custom'"))->toContain('id="custom"');
        expect(Directive::compile($page, '"custom"'))->toContain('id="custom"');
    });

    it('encodes page data as html entities in data-page', function () {
        $page = examplePage();
        $html = Directive::compile($page);

        $encoded = htmlentities(json_encode($page));
        expect($html)->toContain('data-page="' . $encoded . '"');
    });

    it('encodes component with forward slashes properly', function () {
        $page = examplePage();
        $html = Directive::compile($page);

        expect($html)->toContain('Foo\/Bar');
    });

    it('produces single-line output', function () {
        $page = examplePage();
        $html = Directive::compile($page);

        expect($html)->not->toContain("\n");
    });
});

// ──────────────────────────────────────────────
// compileHead()
// ──────────────────────────────────────────────
describe('Directive compileHead', function () {
    beforeEach(function () {
        $ref = new ReflectionProperty(Directive::class, '__inertiaSsr');
        $ref->setAccessible(true);
        $ref->setValue(null, false);
    });

    it('returns empty string when SSR is disabled', function () {
        $page = examplePage();
        $head = Directive::compileHead($page);

        expect($head)->toBe('');
    });
});
