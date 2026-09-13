# ez-php/swagger-ui

Serves Swagger UI / ReDoc documentation for the OpenAPI spec produced by ez-php/openapi

---

## Installation

```bash
composer require ez-php/swagger-ui
```

---

## Usage

Register the service provider (typically in `provider/modules.php`):

```php
$app->register(\EzPhp\SwaggerUI\SwaggerUiServiceProvider::class);
```

This registers `GET /docs`, rendering a Swagger UI page that fetches its spec
from `GET /openapi.json` (see `ez-php/openapi`). Configure via `config/swagger-ui.php`
or environment:

```php
return [
    'endpoint' => env('SWAGGER_UI_ENDPOINT', '/docs'),
    'spec_url' => env('SWAGGER_UI_SPEC_URL', '/openapi.json'),
    'renderer' => env('SWAGGER_UI_RENDERER', 'swagger-ui'), // or 'redoc'
];
```

Both renderers load their assets from a CDN (jsDelivr) — nothing is vendored
by this module. Apply authentication or rate limiting to the `/docs` route in
your own application's service provider if the documentation should not be
publicly accessible.

---

## License

MIT