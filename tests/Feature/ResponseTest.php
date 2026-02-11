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

namespace Tests\Feature;

use CodeIgniter\HTTP\Response as HTTPResponse;
use CodeIgniter\View\View;
use Inertia\Directive;
use Inertia\Response;
use Tests\Support\FeatureRequestTestCase;

uses(FeatureRequestTestCase::class);

describe('Inertia Response Tests', function () {
    it('is a valid inertia response from a server request', function () {
        $routes = [['get', 'user/(:num)', '\Inertia\Controllers\TestController::index']];

        /** @var FeatureRequestTestCase $this */
        $result = $this->withRoutes($routes)->withBodyFormat('json')->get('/user/123');

        $user     = ['name' => 'Jonathon'];
        $response = new Response('User/Edit', ['user' => $user], '123');
        $view     = $response->toResponse($result->request());
        $page     = $view->getData()['page'];

        expect($result->response())->toBeInstanceOf(HTTPResponse::class);
        expect($view)->toBeInstanceOf(View::class);

        expect($page)->toHaveKeys(['component', 'props', 'url', 'version', 'clearHistory', 'encryptHistory']);

        expect($page['version'])->toEqual('123');
        expect($page['component'])->toEqual('User/Edit');
        expect($page['props']['user']['name'])->toEqual('Jonathon');
        expect($page['clearHistory'])->toBeFalse();
        expect($page['encryptHistory'])->toBeFalse();
        expect(str_replace('index.php/', '', $page['url']))->toEqual('/user/123');

        // Verify directive output produces valid HTML with data-page attribute
        $html = Directive::compile($page);
        $expectedDataPage = htmlentities(json_encode($page));
        expect($html)->toBe('<div id="app" data-page="' . $expectedDataPage . '"></div>');
    });

    it('is a valid inertia response from a xhr request', function () {
        $routes = [['get', 'user/(:num)', '\Inertia\Controllers\TestController::index']];
        $headers = ['X-Inertia' => true];

        /** @var FeatureRequestTestCase $this */
        $result = $this->withRoutes($routes)->withHeaders($headers)->get('/user/123');

        $user     = ['name' => 'Jonathon'];
        $response = new Response('User/Edit', ['user' => $user], '123');
        $view     = $response->toResponse($result->request());
        $page     = json_decode($view->getJSON());

        expect($view)->toBeInstanceOf(HTTPResponse::class);

        expect($page->version)->toEqual('123');
        expect($page->component)->toEqual('User/Edit');
        expect($page->props->user->name)->toEqual('Jonathon');
        expect($page->clearHistory)->toBeFalse();
        expect($page->encryptHistory)->toBeFalse();
        expect(str_replace('index.php/', '', $page->url))->toEqual('/user/123');
    });
});
