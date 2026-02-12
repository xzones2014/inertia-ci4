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
use Inertia\AlwaysProp;
use Inertia\Config\Services;
use Inertia\DeferProp;
use Inertia\Inertia;
use Inertia\LazyProp;
use Inertia\MergeProp;
use Inertia\OptionalProp;
use Inertia\ResponseFactory;
use Inertia\Support\Header;
use ReflectionProperty;
use Tests\TestCase;

uses(TestCase::class);

// ──────────────────────────────────────────────
// Facade static proxy
// ──────────────────────────────────────────────
describe('Inertia Facade', function () {
    it('proxies share() calls to ResponseFactory', function () {
        Inertia::share('testKey', 'testValue');

        expect(Inertia::getShared('testKey'))->toBe('testValue');
    });

    it('proxies flushShared()', function () {
        Inertia::share('key', 'value');
        Inertia::flushShared();

        expect(Inertia::getShared())->toBe([]);
    });

    it('proxies version()', function () {
        Inertia::version('v1.2.3');

        expect(Inertia::getVersion())->toBe('v1.2.3');
    });

    it('proxies version() with closure', function () {
        Inertia::version(fn () => 'dynamic-123');

        expect(Inertia::getVersion())->toBe('dynamic-123');
    });

    it('creates AlwaysProp via always()', function () {
        $prop = Inertia::always('data');

        expect($prop)->toBeInstanceOf(AlwaysProp::class);
        expect($prop())->toBe('data');
    });

    it('creates LazyProp via lazy()', function () {
        $prop = Inertia::lazy(fn () => 'lazy-data');

        expect($prop)->toBeInstanceOf(LazyProp::class);
        expect($prop())->toBe('lazy-data');
    });

    it('creates OptionalProp via optional()', function () {
        $prop = Inertia::optional(fn () => 'optional-data');

        expect($prop)->toBeInstanceOf(OptionalProp::class);
        expect($prop())->toBe('optional-data');
    });

    it('creates DeferProp via defer()', function () {
        $prop = Inertia::defer(fn () => 'deferred');

        expect($prop)->toBeInstanceOf(DeferProp::class);
        expect($prop())->toBe('deferred');
    });

    it('creates DeferProp with custom group', function () {
        $prop = Inertia::defer(fn () => 'data', 'sidebar');

        expect($prop)->toBeInstanceOf(DeferProp::class);
        expect($prop->group())->toBe('sidebar');
    });

    it('creates MergeProp via merge()', function () {
        $prop = Inertia::merge(['item']);

        expect($prop)->toBeInstanceOf(MergeProp::class);
        expect($prop())->toBe(['item']);
    });

    it('creates deep MergeProp via deepMerge()', function () {
        $prop = Inertia::deepMerge(['item']);

        expect($prop)->toBeInstanceOf(MergeProp::class);
        expect($prop->shouldDeepMerge())->toBeTrue();
    });

    it('proxies clearHistory()', function () {
        Inertia::clearHistory();

        $factory = Services::inertia();
        $ref     = new ReflectionProperty(ResponseFactory::class, 'shouldClearHistory');
        $ref->setAccessible(true);

        expect($ref->getValue($factory))->toBeTrue();
    });

    it('proxies encryptHistory()', function () {
        Inertia::encryptHistory();

        $factory = Services::inertia();
        $ref     = new ReflectionProperty(ResponseFactory::class, 'encryptHistory');
        $ref->setAccessible(true);

        expect($ref->getValue($factory))->toBeTrue();
    });

    it('proxies render() and returns a string', function () {
        // Inject an Inertia XHR request so render returns JSON instead of view
        $request = new IncomingRequest(new App(), new URI('http://example.com'), null, new UserAgent());
        $request->setHeader(Header::INERTIA, 'true');
        Services::injectMock('request', $request);

        $result = Inertia::render('TestComponent', ['foo' => 'bar']);

        expect($result)->toBeString();
        expect($result)->toContain('TestComponent');
    });

    it('proxies location() for non-inertia request', function () {
        $result = Inertia::location('https://example.com');

        expect($result->getStatusCode())->toBe(303);
    });

    it('proxies setRootView()', function () {
        Inertia::setRootView('admin');

        $factory = Services::inertia();
        $ref     = new ReflectionProperty(ResponseFactory::class, 'rootView');
        $ref->setAccessible(true);

        expect($ref->getValue($factory))->toBe('admin');
    });
});
