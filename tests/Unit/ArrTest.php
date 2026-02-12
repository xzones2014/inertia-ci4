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

use stdClass;
use ArrayAccess;
use Inertia\Extras\Arr;

// ──────────────────────────────────────────────
// accessible()
// ──────────────────────────────────────────────
describe('Arr::accessible', function () {
    it('returns true for arrays', function () {
        expect(Arr::accessible([]))->toBeTrue();
        expect(Arr::accessible([1, 2, 3]))->toBeTrue();
        expect(Arr::accessible(['key' => 'value']))->toBeTrue();
    });

    it('returns true for ArrayAccess instances', function () {
        $arrayAccess = new class () implements ArrayAccess {
            public function offsetExists($offset): bool
            {
                return true;
            }

            public function offsetGet($offset): mixed
            {
                return null;
            }

            public function offsetSet($offset, $value): void {}

            public function offsetUnset($offset): void {}
        };

        expect(Arr::accessible($arrayAccess))->toBeTrue();
    });

    it('returns false for non-array values', function () {
        expect(Arr::accessible('string'))->toBeFalse();
        expect(Arr::accessible(123))->toBeFalse();
        expect(Arr::accessible(null))->toBeFalse();
        expect(Arr::accessible(new stdClass()))->toBeFalse();
    });
});

// ──────────────────────────────────────────────
// exists()
// ──────────────────────────────────────────────
describe('Arr::exists', function () {
    it('checks key existence in arrays', function () {
        $array = ['foo' => 'bar', 'baz' => null];

        expect(Arr::exists($array, 'foo'))->toBeTrue();
        expect(Arr::exists($array, 'baz'))->toBeTrue();
        expect(Arr::exists($array, 'missing'))->toBeFalse();
    });

    it('checks key existence with integer keys', function () {
        $array = ['a', 'b', 'c'];

        expect(Arr::exists($array, 0))->toBeTrue();
        expect(Arr::exists($array, 2))->toBeTrue();
        expect(Arr::exists($array, 5))->toBeFalse();
    });

    it('checks key existence in ArrayAccess', function () {
        $arrayAccess = new class () implements ArrayAccess {
            public function offsetExists($offset): bool
            {
                return $offset === 'exists';
            }

            public function offsetGet($offset): mixed
            {
                return null;
            }

            public function offsetSet($offset, $value): void {}

            public function offsetUnset($offset): void {}
        };

        expect(Arr::exists($arrayAccess, 'exists'))->toBeTrue();
        expect(Arr::exists($arrayAccess, 'nope'))->toBeFalse();
    });
});

// ──────────────────────────────────────────────
// get()
// ──────────────────────────────────────────────
describe('Arr::get', function () {
    it('gets a value by key', function () {
        $array = ['foo' => 'bar'];

        expect(Arr::get($array, 'foo'))->toBe('bar');
    });

    it('returns default when key not found', function () {
        $array = ['foo' => 'bar'];

        expect(Arr::get($array, 'missing', 'default'))->toBe('default');
    });

    it('returns entire array when key is null', function () {
        $array = ['a' => 1, 'b' => 2];

        expect(Arr::get($array, null))->toBe(['a' => 1, 'b' => 2]);
    });

    it('supports dot notation for nested access', function () {
        $array = [
            'user' => [
                'name'    => 'John',
                'address' => [
                    'city' => 'NYC',
                ],
            ],
        ];

        expect(Arr::get($array, 'user.name'))->toBe('John');
        expect(Arr::get($array, 'user.address.city'))->toBe('NYC');
    });

    it('returns default for missing nested keys', function () {
        $array = ['user' => ['name' => 'John']];

        expect(Arr::get($array, 'user.email', 'none'))->toBe('none');
        expect(Arr::get($array, 'missing.deep.key', 'fallback'))->toBe('fallback');
    });

    it('returns default when value is not accessible', function () {
        expect(Arr::get('not-an-array', 'key', 'default'))->toBe('default');
    });

    it('resolves default from closure', function () {
        $array = ['foo' => 'bar'];

        $result = Arr::get($array, 'missing', fn () => 'computed');

        expect($result)->toBe('computed');
    });
});

// ──────────────────────────────────────────────
// set()
// ──────────────────────────────────────────────
describe('Arr::set', function () {
    it('sets a value by key', function () {
        $array = [];
        Arr::set($array, 'foo', 'bar');

        expect($array)->toBe(['foo' => 'bar']);
    });

    it('sets nested values using dot notation', function () {
        $array = [];
        Arr::set($array, 'user.name', 'John');

        expect($array)->toBe(['user' => ['name' => 'John']]);
    });

    it('sets deeply nested values', function () {
        $array = [];
        Arr::set($array, 'a.b.c', 'value');

        expect($array)->toBe(['a' => ['b' => ['c' => 'value']]]);
    });

    it('replaces entire array when key is null', function () {
        $array  = ['old' => 'data'];
        $result = Arr::set($array, null, ['new' => 'data']);

        expect($result)->toBe(['new' => 'data']);
    });

    it('overwrites existing values', function () {
        $array = ['foo' => 'old'];
        Arr::set($array, 'foo', 'new');

        expect($array['foo'])->toBe('new');
    });

    it('creates intermediate arrays if they do not exist', function () {
        $array = [];
        Arr::set($array, 'level1.level2.level3', 'deep');

        expect($array['level1']['level2']['level3'])->toBe('deep');
    });
});

// ──────────────────────────────────────────────
// only()
// ──────────────────────────────────────────────
describe('Arr::only', function () {
    it('returns only specified keys', function () {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        expect(Arr::only($array, ['a', 'c']))->toBe(['a' => 1, 'c' => 3]);
    });

    it('handles a single key as string', function () {
        $array = ['a' => 1, 'b' => 2];

        expect(Arr::only($array, 'a'))->toBe(['a' => 1]);
    });

    it('ignores keys that do not exist', function () {
        $array = ['a' => 1, 'b' => 2];

        expect(Arr::only($array, ['a', 'missing']))->toBe(['a' => 1]);
    });

    it('returns empty array when no keys match', function () {
        $array = ['a' => 1];

        expect(Arr::only($array, ['x', 'y']))->toBe([]);
    });
});

// ──────────────────────────────────────────────
// value()
// ──────────────────────────────────────────────
describe('Arr::value', function () {
    it('returns scalar values as-is', function () {
        expect(Arr::value('hello'))->toBe('hello');
        expect(Arr::value(42))->toBe(42);
        expect(Arr::value(null))->toBeNull();
    });

    it('resolves closures', function () {
        $result = Arr::value(fn () => 'resolved');

        expect($result)->toBe('resolved');
    });

    it('passes arguments to closures', function () {
        $result = Arr::value(fn ($a, $b) => $a + $b, 3, 7);

        expect($result)->toBe(10);
    });

    it('does not invoke non-closure callables', function () {
        // Arrays and strings that are callable should NOT be invoked
        expect(Arr::value('strtoupper'))->toBe('strtoupper');
    });
});
