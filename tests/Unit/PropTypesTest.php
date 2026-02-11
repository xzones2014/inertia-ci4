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

use Inertia\AlwaysProp;
use Inertia\Deferrable;
use Inertia\DeferProp;
use Inertia\IgnoreFirstLoad;
use Inertia\LazyProp;
use Inertia\Mergeable;
use Inertia\MergeProp;
use Inertia\OptionalProp;

describe('AlwaysProp', function () {
    it('resolves scalar values', function () {
        $prop = new AlwaysProp('hello');
        expect($prop())->toBe('hello');
    });

    it('resolves closure values', function () {
        $prop = new AlwaysProp(fn () => 'resolved');
        expect($prop())->toBe('resolved');
    });

    it('resolves array values', function () {
        $prop = new AlwaysProp(['a' => 1, 'b' => 2]);
        expect($prop())->toBe(['a' => 1, 'b' => 2]);
    });

    it('resolves null values', function () {
        $prop = new AlwaysProp(null);
        expect($prop())->toBeNull();
    });
});

describe('OptionalProp', function () {
    it('implements IgnoreFirstLoad', function () {
        $prop = new OptionalProp(fn () => 'data');
        expect($prop)->toBeInstanceOf(IgnoreFirstLoad::class);
    });

    it('resolves callback value', function () {
        $prop = new OptionalProp(fn () => 'optional data');
        expect($prop())->toBe('optional data');
    });

    it('does not invoke string callables', function () {
        $prop = new OptionalProp(fn () => 'strtoupper');
        expect($prop())->toBe('strtoupper');
    });
});

describe('LazyProp', function () {
    it('implements IgnoreFirstLoad', function () {
        $prop = new LazyProp(fn () => 'data');
        expect($prop)->toBeInstanceOf(IgnoreFirstLoad::class);
    });

    it('resolves callback value', function () {
        $prop = new LazyProp(fn () => 'lazy data');
        expect($prop())->toBe('lazy data');
    });
});

describe('DeferProp', function () {
    it('implements required interfaces', function () {
        $prop = new DeferProp(fn () => 'data');
        expect($prop)->toBeInstanceOf(Deferrable::class);
        expect($prop)->toBeInstanceOf(IgnoreFirstLoad::class);
        expect($prop)->toBeInstanceOf(Mergeable::class);
    });

    it('resolves callback value', function () {
        $prop = new DeferProp(fn () => 'deferred data');
        expect($prop())->toBe('deferred data');
    });

    it('defaults to deferred state', function () {
        $prop = new DeferProp(fn () => 'data');
        expect($prop->shouldDefer())->toBeTrue();
    });

    it('uses default group when none specified', function () {
        $prop = new DeferProp(fn () => 'data');
        expect($prop->group())->toBe('default');
    });

    it('uses custom group when specified', function () {
        $prop = new DeferProp(fn () => 'data', 'stats');
        expect($prop->group())->toBe('stats');
    });

    it('supports merge chaining', function () {
        $prop = new DeferProp(fn () => 'data');
        expect($prop->shouldMerge())->toBeFalse();

        $result = $prop->merge();
        expect($result)->toBe($prop);
        expect($prop->shouldMerge())->toBeTrue();
    });

    it('supports deep merge', function () {
        $prop = new DeferProp(fn () => 'data');
        expect($prop->shouldDeepMerge())->toBeFalse();

        $prop->deepMerge();
        expect($prop->shouldDeepMerge())->toBeTrue();
        expect($prop->shouldMerge())->toBeTrue();
    });

    it('is not mergeable by default', function () {
        $prop = new DeferProp(fn () => 'data');
        expect($prop->shouldMerge())->toBeFalse();
        expect($prop->shouldDeepMerge())->toBeFalse();
    });
});

describe('MergeProp', function () {
    it('implements Mergeable', function () {
        $prop = new MergeProp('data');
        expect($prop)->toBeInstanceOf(Mergeable::class);
    });

    it('is mergeable by default', function () {
        $prop = new MergeProp('data');
        expect($prop->shouldMerge())->toBeTrue();
    });

    it('is not deep merge by default', function () {
        $prop = new MergeProp('data');
        expect($prop->shouldDeepMerge())->toBeFalse();
    });

    it('resolves scalar values', function () {
        $prop = new MergeProp('hello');
        expect($prop())->toBe('hello');
    });

    it('resolves closure values', function () {
        $prop = new MergeProp(fn () => [1, 2, 3]);
        expect($prop())->toBe([1, 2, 3]);
    });

    it('resolves array values', function () {
        $prop = new MergeProp(['a', 'b', 'c']);
        expect($prop())->toBe(['a', 'b', 'c']);
    });

    it('supports deep merge chaining', function () {
        $prop = new MergeProp('data');
        $result = $prop->deepMerge();
        expect($result)->toBe($prop);
        expect($prop->shouldDeepMerge())->toBeTrue();
        expect($prop->shouldMerge())->toBeTrue();
    });
});
