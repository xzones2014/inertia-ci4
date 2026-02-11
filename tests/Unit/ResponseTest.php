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
use CodeIgniter\View\View;
use Config\App;
use Inertia\AlwaysProp;
use Inertia\DeferProp;
use Inertia\LazyProp;
use Inertia\MergeProp;
use Inertia\OptionalProp;
use Inertia\Response;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Helper to create an IncomingRequest with optional headers.
 *
 * @param array<string, string> $headers
 */
function makeRequest(string $uri = 'http://example.com/test', array $headers = []): IncomingRequest
{
    $request = new IncomingRequest(
        new App(),
        new URI($uri),
        null,
        new UserAgent()
    );

    foreach ($headers as $name => $value) {
        $request->setHeader($name, $value);
    }

    return $request;
}

// ──────────────────────────────────────────────
// Construction & Page Object
// ──────────────────────────────────────────────
describe('Response Page Object', function () {
    it('returns a View for non-inertia requests', function () {
        $response = new Response('Users/Index', ['users' => []], '1.0');
        $result   = $response->toResponse(makeRequest());

        expect($result)->toBeInstanceOf(View::class);
    });

    it('includes all standard page keys', function () {
        $response = new Response('Users/Index', ['name' => 'John'], '1.0');
        $page     = $response->toResponse(makeRequest())->getData()['page'];

        expect($page)->toHaveKeys([
            'component', 'props', 'url', 'version',
            'clearHistory', 'encryptHistory',
        ]);
        expect($page['component'])->toBe('Users/Index');
        expect($page['props'])->toBe(['name' => 'John']);
        expect($page['version'])->toBe('1.0');
    });

    it('defaults clearHistory and encryptHistory to false', function () {
        $page = (new Response('Test', []))->toResponse(makeRequest())->getData()['page'];

        expect($page['clearHistory'])->toBeFalse();
        expect($page['encryptHistory'])->toBeFalse();
    });

    it('sets clearHistory flag via constructor', function () {
        $response = new Response('Test', [], '', 'app', false, true);
        $page     = $response->toResponse(makeRequest())->getData()['page'];

        expect($page['clearHistory'])->toBeTrue();
    });

    it('sets encryptHistory flag via constructor', function () {
        $response = new Response('Test', [], '', 'app', true, false);
        $page     = $response->toResponse(makeRequest())->getData()['page'];

        expect($page['encryptHistory'])->toBeTrue();
    });
});

// ──────────────────────────────────────────────
// with() and withViewData()
// ──────────────────────────────────────────────
describe('Response with() and withViewData()', function () {
    it('adds a single prop via with()', function () {
        $response = new Response('Test', ['foo' => 'bar']);
        $response->with('baz', 'qux');

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page['props'])->toHaveKey('foo');
        expect($page['props'])->toHaveKey('baz');
        expect($page['props']['baz'])->toBe('qux');
    });

    it('merges array props via with()', function () {
        $response = new Response('Test', ['a' => 1]);
        $response->with(['b' => 2, 'c' => 3]);

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page['props'])->toBe(['a' => 1, 'b' => 2, 'c' => 3]);
    });

    it('adds view data via withViewData()', function () {
        $response = new Response('Test', []);
        $response->withViewData('title', 'My Page');

        $data = $response->toResponse(makeRequest())->getData();

        expect($data)->toHaveKey('title');
        expect($data['title'])->toBe('My Page');
        expect($data)->toHaveKey('page');
    });

    it('merges array view data via withViewData()', function () {
        $response = new Response('Test', []);
        $response->withViewData(['title' => 'A', 'lang' => 'en']);

        $data = $response->toResponse(makeRequest())->getData();

        expect($data['title'])->toBe('A');
        expect($data['lang'])->toBe('en');
    });
});

// ──────────────────────────────────────────────
// Property Resolution – Initial Load
// ──────────────────────────────────────────────
describe('Property Resolution – Initial Load', function () {
    it('resolves closures in props', function () {
        $response = new Response('Test', [
            'name' => fn () => 'John',
            'age'  => 30,
        ]);

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page['props']['name'])->toBe('John');
        expect($page['props']['age'])->toBe(30);
    });

    it('excludes OptionalProp on initial load', function () {
        $response = new Response('Test', [
            'name' => 'John',
            'lazy' => new OptionalProp(fn () => 'lazy data'),
        ]);

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page['props'])->toHaveKey('name');
        expect($page['props'])->not->toHaveKey('lazy');
    });

    it('excludes LazyProp on initial load', function () {
        $response = new Response('Test', [
            'name' => 'John',
            'old'  => new LazyProp(fn () => 'old data'),
        ]);

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page['props'])->toHaveKey('name');
        expect($page['props'])->not->toHaveKey('old');
    });

    it('excludes DeferProp on initial load', function () {
        $response = new Response('Test', [
            'name'  => 'John',
            'stats' => new DeferProp(fn () => [1, 2, 3]),
        ]);

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page['props'])->toHaveKey('name');
        expect($page['props'])->not->toHaveKey('stats');
    });

    it('includes AlwaysProp on initial load', function () {
        $response = new Response('Test', [
            'name'   => 'John',
            'errors' => new AlwaysProp(fn () => (object) []),
        ]);

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page['props'])->toHaveKey('name');
        expect($page['props'])->toHaveKey('errors');
    });

    it('includes MergeProp on initial load', function () {
        $response = new Response('Test', [
            'items' => new MergeProp(fn () => [1, 2]),
        ]);

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page['props'])->toHaveKey('items');
        expect($page['props']['items'])->toBe([1, 2]);
    });

    it('resolves nested array closures', function () {
        $response = new Response('Test', [
            'nested' => [
                'value' => fn () => 'resolved',
            ],
        ]);

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page['props']['nested']['value'])->toBe('resolved');
    });
});

// ──────────────────────────────────────────────
// Partial Reloads
// ──────────────────────────────────────────────
describe('Partial Reloads', function () {
    it('filters to only requested props', function () {
        $response = new Response('User/Edit', [
            'name'  => 'John',
            'email' => 'john@example.com',
            'age'   => 30,
        ]);

        $request = makeRequest('http://example.com/test', [
            'X-Inertia-Partial-Component' => 'User/Edit',
            'X-Inertia-Partial-Data'      => 'name,email',
        ]);

        $page = $response->toResponse($request)->getData()['page'];

        expect($page['props'])->toHaveKey('name');
        expect($page['props'])->toHaveKey('email');
        expect($page['props'])->not->toHaveKey('age');
    });

    it('excludes specified props via except header', function () {
        $response = new Response('User/Edit', [
            'name'  => 'John',
            'email' => 'john@example.com',
            'age'   => 30,
        ]);

        $request = makeRequest('http://example.com/test', [
            'X-Inertia-Partial-Component' => 'User/Edit',
            'X-Inertia-Partial-Except'    => 'age',
        ]);

        $page = $response->toResponse($request)->getData()['page'];

        expect($page['props'])->toHaveKey('name');
        expect($page['props'])->toHaveKey('email');
        expect($page['props'])->not->toHaveKey('age');
    });

    it('does not filter when component does not match', function () {
        $response = new Response('User/Edit', [
            'name'  => 'John',
            'email' => 'john@example.com',
        ]);

        $request = makeRequest('http://example.com/test', [
            'X-Inertia-Partial-Component' => 'Different/Component',
            'X-Inertia-Partial-Data'      => 'name',
        ]);

        $page = $response->toResponse($request)->getData()['page'];

        expect($page['props'])->toHaveKey('name');
        expect($page['props'])->toHaveKey('email');
    });

    it('includes AlwaysProp even when not in partial only list', function () {
        $response = new Response('User/Edit', [
            'name'   => 'John',
            'errors' => new AlwaysProp(fn () => (object) ['name' => 'required']),
        ]);

        $request = makeRequest('http://example.com/test', [
            'X-Inertia-Partial-Component' => 'User/Edit',
            'X-Inertia-Partial-Data'      => 'name',
        ]);

        $page = $response->toResponse($request)->getData()['page'];

        expect($page['props'])->toHaveKey('name');
        expect($page['props'])->toHaveKey('errors');
        expect($page['props']['errors'])->toEqual((object) ['name' => 'required']);
    });

    it('includes OptionalProp when explicitly requested in partial reload', function () {
        $response = new Response('User/Edit', [
            'name' => 'John',
            'lazy' => new OptionalProp(fn () => 'lazy data'),
        ]);

        $request = makeRequest('http://example.com/test', [
            'X-Inertia-Partial-Component' => 'User/Edit',
            'X-Inertia-Partial-Data'      => 'name,lazy',
        ]);

        $page = $response->toResponse($request)->getData()['page'];

        expect($page['props'])->toHaveKey('name');
        expect($page['props'])->toHaveKey('lazy');
        expect($page['props']['lazy'])->toBe('lazy data');
    });

    it('includes DeferProp when explicitly requested in partial reload', function () {
        $response = new Response('User/Edit', [
            'name'  => 'John',
            'stats' => new DeferProp(fn () => [1, 2, 3]),
        ]);

        $request = makeRequest('http://example.com/test', [
            'X-Inertia-Partial-Component' => 'User/Edit',
            'X-Inertia-Partial-Data'      => 'stats',
        ]);

        $page = $response->toResponse($request)->getData()['page'];

        expect($page['props'])->toHaveKey('stats');
        expect($page['props']['stats'])->toBe([1, 2, 3]);
    });
});

// ──────────────────────────────────────────────
// Merge Props
// ──────────────────────────────────────────────
describe('Merge Props Resolution', function () {
    it('includes mergeProps in page object', function () {
        $response = new Response('Test', [
            'items' => new MergeProp(fn () => [1, 2, 3]),
            'name'  => 'John',
        ]);

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page)->toHaveKey('mergeProps');
        expect($page['mergeProps'])->toContain('items');
    });

    it('includes deepMergeProps in page object', function () {
        $prop     = (new MergeProp(fn () => ['a' => 1]))->deepMerge();
        $response = new Response('Test', ['config' => $prop]);

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page)->toHaveKey('deepMergeProps');
        expect($page['deepMergeProps'])->toContain('config');
    });

    it('does not include mergeProps when none exist', function () {
        $response = new Response('Test', ['name' => 'John']);

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page)->not->toHaveKey('mergeProps');
        expect($page)->not->toHaveKey('deepMergeProps');
    });

    it('separates regular and deep merge props', function () {
        $response = new Response('Test', [
            'items'  => new MergeProp(fn () => [1, 2]),
            'config' => (new MergeProp(fn () => ['a' => 1]))->deepMerge(),
        ]);

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page['mergeProps'])->toBe(['items']);
        expect($page['deepMergeProps'])->toBe(['config']);
    });
});

// ──────────────────────────────────────────────
// Deferred Props
// ──────────────────────────────────────────────
describe('Deferred Props Resolution', function () {
    it('includes deferredProps groups in page object', function () {
        $response = new Response('Test', [
            'name'  => 'John',
            'stats' => new DeferProp(fn () => [1, 2, 3]),
            'chart' => new DeferProp(fn () => ['data'], 'charts'),
        ]);

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page)->toHaveKey('deferredProps');
        expect($page['deferredProps'])->toHaveKey('default');
        expect($page['deferredProps'])->toHaveKey('charts');
        expect($page['deferredProps']['default'])->toContain('stats');
        expect($page['deferredProps']['charts'])->toContain('chart');
    });

    it('groups multiple deferred props in same group', function () {
        $response = new Response('Test', [
            'a' => new DeferProp(fn () => 1, 'batch'),
            'b' => new DeferProp(fn () => 2, 'batch'),
        ]);

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page['deferredProps']['batch'])->toContain('a');
        expect($page['deferredProps']['batch'])->toContain('b');
    });

    it('does not include deferredProps on partial reload', function () {
        $response = new Response('Test', [
            'name'  => 'John',
            'stats' => new DeferProp(fn () => [1, 2, 3]),
        ]);

        $request = makeRequest('http://example.com/test', [
            'X-Inertia-Partial-Component' => 'Test',
            'X-Inertia-Partial-Data'      => 'name,stats',
        ]);

        $page = $response->toResponse($request)->getData()['page'];

        expect($page)->not->toHaveKey('deferredProps');
    });

    it('does not include deferredProps when none exist', function () {
        $response = new Response('Test', ['name' => 'John']);

        $page = $response->toResponse(makeRequest())->getData()['page'];

        expect($page)->not->toHaveKey('deferredProps');
    });
});

// ──────────────────────────────────────────────
// XHR / Inertia JSON Response
// ──────────────────────────────────────────────
describe('Inertia XHR Response', function () {
    it('returns JSON response for Inertia requests', function () {
        $response = new Response('Test', ['name' => 'John'], '1.0');

        $request = makeRequest('http://example.com/test', [
            'X-Inertia' => 'true',
        ]);

        $result = $response->toResponse($request);

        expect($result)->toBeInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class);
        expect($result->hasHeader('X-Inertia'))->toBeTrue();
        expect($result->hasHeader('Vary'))->toBeTrue();

        $page = json_decode($result->getJSON(), true);
        expect($page['component'])->toBe('Test');
        expect($page['props']['name'])->toBe('John');
        expect($page['version'])->toBe('1.0');
        expect($page['clearHistory'])->toBeFalse();
        expect($page['encryptHistory'])->toBeFalse();
    });

    it('returns JSON with encryptHistory when enabled', function () {
        $response = new Response('Test', [], '', 'app', true, false);

        $request = makeRequest('http://example.com/test', [
            'X-Inertia' => 'true',
        ]);

        $result = $response->toResponse($request);
        $page   = json_decode($result->getJSON(), true);

        expect($page['encryptHistory'])->toBeTrue();
        expect($page['clearHistory'])->toBeFalse();
    });

    it('returns JSON with clearHistory when enabled', function () {
        $response = new Response('Test', [], '', 'app', false, true);

        $request = makeRequest('http://example.com/test', [
            'X-Inertia' => 'true',
        ]);

        $result = $response->toResponse($request);
        $page   = json_decode($result->getJSON(), true);

        expect($page['clearHistory'])->toBeTrue();
    });
});
