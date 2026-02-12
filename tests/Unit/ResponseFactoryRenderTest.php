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
use Config\Services;
use Inertia\Directive;
use Inertia\ResponseFactory;
use Inertia\Support\Header;
use ReflectionProperty;
use Tests\TestCase;

uses(TestCase::class);

function injectInertiaXhrRequest(): void
{
    $request = new IncomingRequest(new App(), new URI('http://example.com'), null, new UserAgent());
    $request->setHeader(Header::INERTIA, 'true');
    Services::injectMock('request', $request);
}

// ──────────────────────────────────────────────
// render()
// ──────────────────────────────────────────────
describe('ResponseFactory render', function () {
    beforeEach(function () {
        // Inject an Inertia XHR request so render() returns JSON
        // instead of trying to render a view file
        injectInertiaXhrRequest();
    });

    it('returns a string for inertia requests', function () {
        $factory = new ResponseFactory();
        $result  = $factory->render('User/Edit', ['name' => 'John']);

        expect($result)->toBeString();
    });

    it('includes the component name in the response', function () {
        $factory = new ResponseFactory();
        $result  = $factory->render('User/Edit');

        expect($result)->toContain('User/Edit');
    });

    it('includes props in the response', function () {
        $factory = new ResponseFactory();
        $result  = $factory->render('User/Edit', ['name' => 'John']);

        expect($result)->toContain('John');
    });

    it('merges shared props with render props', function () {
        $factory = new ResponseFactory();
        $factory->share('shared', 'data');

        $result = $factory->render('TestComponent', ['local' => 'value']);

        expect($result)->toContain('shared');
        expect($result)->toContain('local');
    });

    it('resets clearHistory after render', function () {
        $factory = new ResponseFactory();
        $factory->clearHistory();

        $factory->render('TestComponent');

        $ref = new ReflectionProperty(ResponseFactory::class, 'shouldClearHistory');
        $ref->setAccessible(true);

        expect($ref->getValue($factory))->toBeFalse();
    });

    it('includes encryptHistory in the page data when enabled', function () {
        $factory = new ResponseFactory();
        $factory->encryptHistory();

        $result = $factory->render('TestComponent');

        expect($result)->toContain('encryptHistory');
    });

    it('returns JSON with correct page structure', function () {
        $factory = new ResponseFactory();
        $factory->version('v1');
        $result = $factory->render('Dashboard', ['user' => 'Jane']);

        $page = json_decode($result, true);

        expect($page)->toHaveKey('component');
        expect($page)->toHaveKey('props');
        expect($page)->toHaveKey('url');
        expect($page)->toHaveKey('version');
        expect($page['component'])->toBe('Dashboard');
        expect($page['props']['user'])->toBe('Jane');
        expect($page['version'])->toBe('v1');
    });
});

// ──────────────────────────────────────────────
// location()
// ──────────────────────────────────────────────
describe('ResponseFactory location', function () {
    it('returns 409 with X-Inertia-Location for inertia requests', function () {
        // Simulate inertia request by setting the header on the global request
        $request = new IncomingRequest(new App(), new URI('http://example.com'), null, new UserAgent());
        $request->setHeader(Header::INERTIA, 'true');

        // Inject the request into the service container
        Services::injectMock('request', $request);

        $factory  = new ResponseFactory();
        $response = $factory->location('https://external.com');

        expect($response->getStatusCode())->toBe(409);
        expect($response->getHeaderLine(Header::LOCATION))->toBe('https://external.com');
    });

    it('returns 303 redirect for non-inertia requests', function () {
        $factory  = new ResponseFactory();
        $response = $factory->location('https://external.com');

        expect($response->getStatusCode())->toBe(303);
    });

    it('accepts a string URL', function () {
        $factory  = new ResponseFactory();
        $response = $factory->location('/new-path');

        expect($response->getStatusCode())->toBe(303);
    });

    it('accepts a RequestInterface for the URL', function () {
        $request  = new IncomingRequest(new App(), new URI('http://example.com/page'), null, new UserAgent());
        $factory  = new ResponseFactory();
        $response = $factory->location($request);

        expect($response->getStatusCode())->toBe(303);
    });
});

// ──────────────────────────────────────────────
// init() static method
// ──────────────────────────────────────────────
describe('ResponseFactory init', function () {
    beforeEach(function () {
        $ref = new ReflectionProperty(Directive::class, '__inertiaSsr');
        $ref->setAccessible(true);
        $ref->setValue(null, false);
    });

    it('renders page body by default', function () {
        $page = [
            'component' => 'Test',
            'props'     => [],
            'url'       => '/test',
            'version'   => '',
        ];

        $result = ResponseFactory::init($page);

        expect($result)->toContain('<div id="app"');
        expect($result)->toContain('data-page=');
    });

    it('renders page head when isHead is true', function () {
        $page = [
            'component' => 'Test',
            'props'     => [],
            'url'       => '/test',
            'version'   => '',
        ];

        $result = ResponseFactory::init($page, true);

        // With SSR disabled, head should be empty
        expect($result)->toBe('');
    });
});
