# CodeIgniter RestServer (CI4 Module)

This is a modernized, CI4-compatible module with filters for CORS, API keys, authentication (Basic/Digest/Session), and rate limiting, plus a lean base REST controller and a framework-neutral `Format` utility.

Note: CodeIgniter 4 has built-in RESTful controllers. This module focuses on extras like API keys, CORS, and rate limiting, packaged for easy reuse.

## Requirements

- PHP 8.1+
- CodeIgniter 4.5+

## Installation

Install via Composer in your CI4 application:

```bash
composer require chriskacerguis/codeigniter-restserver
```

Auto-discovery is enabled, so the module’s config, filters, and migrations will be found automatically.

### Manual installation (no Composer)

If you cannot use Composer, you can install this module manually.

1) Download the source

- Download a release ZIP from GitHub or clone the repo, then copy the `src/` folder into your app. A common layout is:

```
app/ThirdParty/RestServer/src
```

2) Register PSR-4 autoloading

Edit `app/Config/Autoload.php` and add the namespace mapping so CodeIgniter can locate the classes:

```php
public $psr4 = [
    'App\\' => APPPATH,
    // Add this line (adjust the path if you chose a different location)
    'chriskacerguis\\RestServer\\' => APPPATH . 'ThirdParty/RestServer/src',
];
```

Alternative: If you prefer `app/Modules/RestServer`, copy `src/` there and map it accordingly:

```php
'chriskacerguis\\RestServer\\' => APPPATH . 'Modules/RestServer/src',
```

3) Run the module migrations

Once the namespace is registered, you can run the included migrations by namespace:

```bash
php spark migrate -n 'chriskacerguis\RestServer'
```

4) Wire filters and routes

Follow the "Filters wiring" section below to add the filter aliases and route groups in your app configs.

5) Verify

Create a simple controller that extends `chriskacerguis\RestServer\RestController` and hit it in the browser or via curl. See the "Quick demo" section for a copy-paste example.

Notes:
- Language files in this repository are optional; the core module doesn’t require them for basic operation.
- When updating the module, repeat step 1 and keep your PSR-4 mapping intact.

## Database migrations

This package includes migrations for tables `keys`, `logs`, `access`, `limits`.

Run them from your CI4 app root (where `spark` exists):

```bash
php spark migrate -n 'chriskacerguis\RestServer'
# or run all discovered namespaces (including this one)
php spark migrate -all

php spark migrate:status -n 'chriskacerguis\RestServer'
```

## Configuration

Settings live in `chriskacerguis\RestServer\Config\Rest` (no `rest.php` in CI4). Common options:

- `defaultFormat`: `json` (supported: `json,array,csv,html,jsonp,php,serialized,xml`)
- Auth: `auth` = `false` | `basic` | `digest` | `session`, `realm`, `validLogins`
- API Keys: `enableKeys`, `keysTable`, `keyHeaderName`, `keysExpire`, `keysExpiryColumn`, `keyLength`
- Rate Limits: `enableLimits`, `limitsMethod` = `ROUTED_URL` | `API_KEY` | `IP_ADDRESS` | `METHOD_NAME`, `limitDefaultPerHour`, `limitWindowSeconds`
- CORS: `checkCors`, `allowAnyCorsDomain`, `allowedCorsOrigins`, `allowedCorsHeaders`, `allowedCorsMethods`, `forcedCorsHeaders`

Adjust these in your app by publishing a copy or via your own config/environment.

## Filters wiring

Add filter aliases in your CI4 app’s `app/Config/Filters.php`:

```php
public $aliases = [
    'rest-auth'      => \chriskacerguis\RestServer\Filters\AuthFilter::class,
    'rest-apikey'    => \chriskacerguis\RestServer\Filters\ApiKeyFilter::class,
    'rest-cors'      => \chriskacerguis\RestServer\Filters\CorsFilter::class,
    'rest-ratelimit' => \chriskacerguis\RestServer\Filters\RateLimitFilter::class,
];

public $globals = [
    'before' => [
        // 'rest-cors', // enable globally if desired
    ],
    'after' => [],
];
```

Apply filters to routes in `app/Config/Routes.php`:

```php
$routes->group('api', ['filter' => 'rest-cors'], static function ($routes) {
    $routes->group('', ['filter' => 'rest-apikey'], static function ($routes) {
        $routes->group('', ['filter' => 'rest-auth'], static function ($routes) {
            $routes->group('', ['filter' => 'rest-ratelimit'], static function ($routes) {
                $routes->get('users', 'Api\\Users::index');
            });
        });
    });
});
```

## Base controller

Use the provided base controller to simplify response handling:

```php
use chriskacerguis\RestServer\RestController;

class Users extends RestController
{
    public function index()
    {
        return $this->respondData([
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ]);
    }
}
```

## API keys

- Enable in config: `enableKeys = true`.
- Provide keys in request header `X-API-KEY: <key>` or query `?api_key=<key>`.
- Keys are validated via the `keys` table; optional expiry uses `keysExpire` and `keysExpiryColumn`.

## Rate limiting

- Enable in config: `enableLimits = true`.
- Default window and limit can be customized via `limitDefaultPerHour` and `limitWindowSeconds`.
- Exposes headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`; on exceed, returns `429` with `Retry-After`.

## CORS

- Enable check: `checkCors = true` and wire `rest-cors` filter.
- Configure allowed origins/methods/headers via config.

## Logging & access controls (optional)

- `logs` and `access` tables and models are included for future/optional use.
- You may add additional filters or listeners to record logs and enforce per-controller/method access.

## Upgrading from CI3

- CI3’s `rest.php` is no longer used. Settings moved to `Config\Rest`.
- Controller usage is CI4-style (`ResourceController` based).
- Install in a CI4 app and use the steps above.

## Development

Style and static analysis:

```bash
composer run lint
composer run stan
```

## Quick demo: copy-paste test

Use this minimal controller and route in your CI4 app to verify the module works end-to-end.

1) Create `app/Controllers/Api/Users.php` in your CI4 app:

```php
<?php
namespace App\Controllers\Api;

use chriskacerguis\RestServer\RestController;

class Users extends RestController
{
    public function index()
    {
        return $this->respondData([
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ]);
    }
}
```

2) In `app/Config/Filters.php` add aliases (if not already present):

```php
public $aliases = [
    'rest-auth'      => \chriskacerguis\RestServer\Filters\AuthFilter::class,
    'rest-apikey'    => \chriskacerguis\RestServer\Filters\ApiKeyFilter::class,
    'rest-cors'      => \chriskacerguis\RestServer\Filters\CorsFilter::class,
    'rest-ratelimit' => \chriskacerguis\RestServer\Filters\RateLimitFilter::class,
];
```

3) In `app/Config/Routes.php` add a quick route with filters:

```php
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('api', ['filter' => 'rest-cors'], static function ($routes) {
    $routes->group('', ['filter' => 'rest-apikey'], static function ($routes) {
        $routes->group('', ['filter' => 'rest-auth'], static function ($routes) {
            $routes->group('', ['filter' => 'rest-ratelimit'], static function ($routes) {
                $routes->get('users', 'Api\\Users::index');
            });
        });
    });
});
```

4) Configure and migrate:
- In `Config\\Rest` (this module) set `enableKeys = true` and insert at least one key record in the `keys` table.
- Run migrations in your app root:

```bash
php spark migrate -n 'chriskacerguis\RestServer'
```

5) Test with curl:

```bash
curl -H 'X-API-KEY: YOUR_KEY' http://localhost:8080/api/users
```

## License

MIT
