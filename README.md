# Inertia CI 4

The CodeIgniter 4 adapter for [Inertia.js](https://inertiajs.com/) — build modern single-page apps using classic server-side routing and controllers.

Supports the **Inertia.js v2** protocol including deferred props, merge props, history encryption, and more.

## Requirements

- PHP 8.1+
- CodeIgniter 4.5+

## Installation

```shell
composer require xzones2014/inertia-ci4
```

## Setup

### 1. Publish the Config

Create `app/Config/Inertia.php` extending the package config:

```php
<?php

namespace Config;

use Inertia\Config\Inertia as BaseInertia;

class Inertia extends BaseInertia
{
    public string $rootView   = 'app';       // your root view file
    public bool $isSsrEnabled = false;
    public string $ssrUrl     = 'http://127.0.0.1:13714';
    public bool $encryptHistory = false;     // encrypt history globally
}
```

### 2. Register the Middleware

In `app/Config/Filters.php`, register the Inertia middleware:

```php
public array $aliases = [
    // ...
    'inertia' => \Inertia\Middleware::class,
];
```

Then apply it to your routes:

```php
public array $globals = [
    'before' => ['inertia'],
    'after'  => ['inertia'],
];
```

### 3. Create the Root View

Create `app/Views/app.php`:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= \Inertia\Inertia::init($page, true) ?>
    @vite(['resources/js/app.js'])
</head>
<body>
    <?= \Inertia\Inertia::init($page) ?>
</body>
</html>
```

## Usage

### Rendering Responses

From a controller, return an Inertia response:

```php
use Inertia\Inertia;

class UsersController extends BaseController
{
    public function index(): string
    {
        return Inertia::render('Users/Index', [
            'users' => $this->userModel->findAll(),
        ]);
    }
}
```

Or use the global helper:

```php
return inertia('Users/Index', ['users' => $users]);
```

### Sharing Data

Share data globally with every Inertia response:

```php
// Single value
Inertia::share('appName', 'My App');

// Multiple values
Inertia::share([
    'appName' => 'My App',
    'user'    => fn () => auth()->user(),
]);
```

### Customizing the Middleware

Extend the base middleware to customize shared data, versioning, or the root view:

```php
<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use Inertia\Middleware;

class InertiaMiddleware extends Middleware
{
    protected string $rootView = 'app';

    public function share(RequestInterface $request): array
    {
        return array_merge(parent::share($request), [
            'flash' => fn () => [
                'success' => session()->getFlashdata('success'),
                'error'   => session()->getFlashdata('error'),
            ],
        ]);
    }

    public function version(RequestInterface $request): ?string
    {
        // Custom versioning logic
        return parent::version($request);
    }
}
```

## Prop Types

Inertia CI4 supports all Inertia.js v2 prop types for fine-grained control over when and how data is sent to the frontend.

### Always Props

Props that are included in **every** response, even during partial reloads. Ideal for validation errors:

```php
Inertia::render('Users/Edit', [
    'user'   => $user,
    'errors' => Inertia::always(fn () => $validation->getErrors()),
]);
```

### Optional Props

Props excluded from the initial page load, only included when explicitly requested via a partial reload:

```php
Inertia::render('Dashboard', [
    'stats'   => 'always loaded',
    'reports' => Inertia::optional(fn () => $this->getReports()),
]);
```

### Deferred Props

Props loaded **asynchronously** after the initial page load, improving perceived performance:

```php
Inertia::render('Dashboard', [
    'title' => 'Dashboard',
    'stats' => Inertia::defer(fn () => $this->getExpensiveStats()),
    'chart' => Inertia::defer(fn () => $this->getChartData(), 'charts'),
]);
```

The optional second argument groups deferred props so they can be loaded together.

### Merge Props

Props that are **merged** with existing client-side data instead of replacing them during partial reloads:

```php
Inertia::render('Users/Index', [
    'users' => Inertia::merge($paginatedUsers),
]);
```

For nested data, use deep merge:

```php
Inertia::render('Settings', [
    'config' => Inertia::deepMerge($nestedConfig),
]);
```

### Lazy Props (Deprecated)

Alias for `optional()`. Use `Inertia::optional()` instead:

```php
// Deprecated
Inertia::render('Page', [
    'data' => Inertia::lazy(fn () => $data),
]);
```

## History Encryption

Encrypt the browser history state to protect sensitive data.

### Globally

Set `encryptHistory = true` in your config:

```php
// app/Config/Inertia.php
public bool $encryptHistory = true;
```

### Programmatically

For the next response only:

```php
Inertia::encryptHistory();

return Inertia::render('Secret/Page', [...]);
```

### Per-Route via Filter

Register the `EncryptHistoryMiddleware` for specific routes:

```php
// app/Config/Filters.php
public array $aliases = [
    'encrypt-history' => \Inertia\EncryptHistoryMiddleware::class,
];

// app/Config/Routes.php
$routes->group('admin', ['filter' => 'encrypt-history'], function ($routes) {
    // ...
});
```

## Clearing History

Mark the next response to clear the browser's history state:

```php
Inertia::clearHistory();

return Inertia::render('Dashboard', [...]);
```

## External Redirects

For redirects that leave the Inertia app (e.g., to an external URL or a non-Inertia route):

```php
return Inertia::location($url);

// Or via the helper
return inertia_location($url);
```

## Root View

Change the root view template at runtime:

```php
Inertia::setRootView('admin');
```

Or override `rootView()` in your middleware subclass for per-request control:

```php
public function rootView(RequestInterface $request): string
{
    return str_starts_with($request->getUri()->getPath(), '/admin')
        ? 'admin'
        : 'app';
}
```

## Asset Versioning

The middleware automatically versions your assets by hashing `build/manifest.json` or `mix-manifest.json`. Override `version()` in your middleware subclass for custom logic:

```php
public function version(RequestInterface $request): ?string
{
    return 'v2.0.0';
}
```

## Testing

```shell
composer test
```

## Upgrading from v0.0.x — Breaking Changes

If you are upgrading from a previous version, the following breaking changes require updates to your code.

### Middleware Method Renames

The middleware hook methods have been renamed for consistency with inertia-laravel:

| Before | After |
|---|---|
| `withVersion()` | `version(RequestInterface $request)` |
| `withShare(RequestInterface $request)` | `share(RequestInterface $request)` |

**Before:**
```php
class InertiaMiddleware extends Middleware
{
    public function withVersion(): false|string|null { ... }
    public function withShare(RequestInterface $request): array { ... }
}
```

**After:**
```php
class InertiaMiddleware extends Middleware
{
    public function version(RequestInterface $request): ?string { ... }
    public function share(RequestInterface $request): array { ... }
}
```

### Middleware Default Shared Data Changed

The base `share()` method no longer shares `alert` or `flash` data by default — only `errors` (wrapped in `AlwaysProp`). Add flash data in your own middleware subclass:

**Before (provided by default):**
```php
[
    'alert'  => fn () => session()->getFlashdata('alert'),
    'errors' => fn () => $this->resolveValidationErrors($request),
    'flash'  => fn () => ['success' => ..., 'error' => ...],
]
```

**After (you must add flash/alert yourself):**
```php
public function share(RequestInterface $request): array
{
    return array_merge(parent::share($request), [
        'alert' => fn () => session()->getFlashdata('alert'),
        'flash' => fn () => [
            'success' => session()->getFlashdata('success'),
            'error'   => session()->getFlashdata('error'),
        ],
    ]);
}
```

### Middleware Visibility Changes

`onEmptyResponse()`, `onVersionChange()`, and `resolveValidationErrors()` are now **public** (previously private) and accept new parameters:

| Before | After |
|---|---|
| `private onEmptyResponse()` | `public onEmptyResponse(RequestInterface $request, ResponseInterface $response)` |
| `private onVersionChange(RequestInterface $request)` | `public onVersionChange(RequestInterface $request, ResponseInterface $response)` |
| `private resolveValidationErrors(RequestInterface $request)` | `public resolveValidationErrors(RequestInterface $request)` |

### Response Page Object Has New Keys

The page object now always includes `clearHistory` and `encryptHistory` (both default to `false`). If you are asserting on the page structure in tests or inspecting it client-side, update accordingly:

```diff
 {
   "component": "Users/Index",
   "props": { ... },
   "url": "/users",
   "version": "abc123",
+  "clearHistory": false,
+  "encryptHistory": false
 }
```

Conditional keys `mergeProps`, `deepMergeProps`, and `deferredProps` may also appear when using the corresponding prop types.

### Response Constructor Signature Changed

If you instantiate `Response` directly (rather than via `Inertia::render()`), the constructor now accepts additional parameters:

**Before:**
```php
new Response($component, $props, $version);
```

**After:**
```php
new Response($component, $props, $version, $rootView, $encryptHistory, $clearHistory);
```

The new parameters are optional and default to `'app'`, `false`, `false` respectively.

## License

MIT. See [LICENSE](LICENSE).