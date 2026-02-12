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
use Inertia\AlwaysProp;
use Inertia\DeferProp;
use Inertia\LazyProp;
use Inertia\MergeProp;
use Inertia\OptionalProp;
use Inertia\ResponseFactory;
use Tests\TestCase;

uses(TestCase::class);

// ──────────────────────────────────────────────
// Shared Props
// ──────────────────────────────────────────────
describe('ResponseFactory Sharing', function () {
    it('shares props by key-value', function () {
        $factory = new ResponseFactory();
        $factory->share('foo', 'bar');

        expect($factory->getShared('foo'))->toBe('bar');
    });

    it('shares props by array', function () {
        $factory = new ResponseFactory();
        $factory->share(['foo' => 'bar', 'baz' => 'qux']);

        expect($factory->getShared('foo'))->toBe('bar');
        expect($factory->getShared('baz'))->toBe('qux');
    });

    it('returns all shared props when key is null', function () {
        $factory = new ResponseFactory();
        $factory->share('a', 1);
        $factory->share('b', 2);

        expect($factory->getShared())->toBe(['a' => 1, 'b' => 2]);
    });

    it('returns default when shared key not found', function () {
        $factory = new ResponseFactory();

        expect($factory->getShared('missing', 'default'))->toBe('default');
    });

    it('flushes all shared props', function () {
        $factory = new ResponseFactory();
        $factory->share('foo', 'bar');
        $factory->flushShared();

        expect($factory->getShared())->toBe([]);
    });

    it('overwrites existing shared props', function () {
        $factory = new ResponseFactory();
        $factory->share('key', 'old');
        $factory->share('key', 'new');

        expect($factory->getShared('key'))->toBe('new');
    });
});

// ──────────────────────────────────────────────
// Versioning
// ──────────────────────────────────────────────
describe('ResponseFactory Versioning', function () {
    it('sets and gets a version string', function () {
        $factory = new ResponseFactory();
        $factory->version('1.0.0');

        expect($factory->getVersion())->toBe('1.0.0');
    });

    it('resolves version from closure', function () {
        $factory = new ResponseFactory();
        $factory->version(fn () => 'abc123');

        expect($factory->getVersion())->toBe('abc123');
    });

    it('returns empty string when version is null', function () {
        $factory = new ResponseFactory();
        $factory->version(null);

        expect($factory->getVersion())->toBe('');
    });
});

// ──────────────────────────────────────────────
// Prop Type Factories
// ──────────────────────────────────────────────
describe('ResponseFactory Prop Factories', function () {
    it('creates a LazyProp', function () {
        $factory = new ResponseFactory();
        $prop    = $factory->lazy(fn () => 'data');

        expect($prop)->toBeInstanceOf(LazyProp::class);
        expect($prop())->toBe('data');
    });

    it('creates an OptionalProp', function () {
        $factory = new ResponseFactory();
        $prop    = $factory->optional(fn () => 'data');

        expect($prop)->toBeInstanceOf(OptionalProp::class);
        expect($prop())->toBe('data');
    });

    it('creates a DeferProp with default group', function () {
        $factory = new ResponseFactory();
        $prop    = $factory->defer(fn () => 'data');

        expect($prop)->toBeInstanceOf(DeferProp::class);
        expect($prop->group())->toBe('default');
        expect($prop())->toBe('data');
    });

    it('creates a DeferProp with custom group', function () {
        $factory = new ResponseFactory();
        $prop    = $factory->defer(fn () => 'data', 'sidebar');

        expect($prop)->toBeInstanceOf(DeferProp::class);
        expect($prop->group())->toBe('sidebar');
    });

    it('creates a MergeProp', function () {
        $factory = new ResponseFactory();
        $prop    = $factory->merge([1, 2, 3]);

        expect($prop)->toBeInstanceOf(MergeProp::class);
        expect($prop->shouldMerge())->toBeTrue();
        expect($prop->shouldDeepMerge())->toBeFalse();
    });

    it('creates a deep MergeProp', function () {
        $factory = new ResponseFactory();
        $prop    = $factory->deepMerge(['a' => 1]);

        expect($prop)->toBeInstanceOf(MergeProp::class);
        expect($prop->shouldMerge())->toBeTrue();
        expect($prop->shouldDeepMerge())->toBeTrue();
    });

    it('creates an AlwaysProp', function () {
        $factory = new ResponseFactory();
        $prop    = $factory->always('errors');

        expect($prop)->toBeInstanceOf(AlwaysProp::class);
        expect($prop())->toBe('errors');
    });

    it('creates an AlwaysProp from closure', function () {
        $factory = new ResponseFactory();
        $prop    = $factory->always(fn () => ['field' => 'error']);

        expect($prop)->toBeInstanceOf(AlwaysProp::class);
        expect($prop())->toBe(['field' => 'error']);
    });
});

// ──────────────────────────────────────────────
// History Management
// ──────────────────────────────────────────────
describe('ResponseFactory History', function () {
    it('defaults shouldClearHistory to false', function () {
        $factory = new ResponseFactory();
        $ref     = new ReflectionProperty($factory, 'shouldClearHistory');

        expect($ref->getValue($factory))->toBeFalse();
    });

    it('sets clearHistory flag', function () {
        $factory = new ResponseFactory();
        $factory->clearHistory();

        $ref = new ReflectionProperty($factory, 'shouldClearHistory');

        expect($ref->getValue($factory))->toBeTrue();
    });

    it('defaults encryptHistory to null', function () {
        $factory = new ResponseFactory();
        $ref     = new ReflectionProperty($factory, 'encryptHistory');

        expect($ref->getValue($factory))->toBeNull();
    });

    it('sets encryptHistory flag', function () {
        $factory = new ResponseFactory();
        $factory->encryptHistory();

        $ref = new ReflectionProperty($factory, 'encryptHistory');

        expect($ref->getValue($factory))->toBeTrue();
    });

    it('can disable encryptHistory', function () {
        $factory = new ResponseFactory();
        $factory->encryptHistory(true);
        $factory->encryptHistory(false);

        $ref = new ReflectionProperty($factory, 'encryptHistory');

        expect($ref->getValue($factory))->toBeFalse();
    });
});

// ──────────────────────────────────────────────
// Root View
// ──────────────────────────────────────────────
describe('ResponseFactory Root View', function () {
    it('defaults root view to app', function () {
        $factory = new ResponseFactory();
        $ref     = new ReflectionProperty($factory, 'rootView');

        expect($ref->getValue($factory))->toBe('app');
    });

    it('changes root view', function () {
        $factory = new ResponseFactory();
        $factory->setRootView('admin');

        $ref = new ReflectionProperty($factory, 'rootView');

        expect($ref->getValue($factory))->toBe('admin');
    });
});
