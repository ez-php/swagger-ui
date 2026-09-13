<?php

declare(strict_types=1);

namespace EzPhp\SwaggerUI;

use EzPhp\Contracts\ConfigInterface;
use EzPhp\Contracts\ContainerInterface;
use EzPhp\Contracts\ServiceProvider;
use EzPhp\Routing\Router;

/**
 * Service provider for the ez-php/swagger-ui module.
 *
 * Register in `provider/modules.php`:
 *
 *   $app->register(SwaggerUiServiceProvider::class);
 *
 * Serves a Swagger UI / ReDoc documentation page pointed at an OpenAPI spec
 * URL (typically `GET /openapi.json`, produced by `ez-php/openapi`). This
 * module does not generate a spec itself — see `ez-php/openapi` for that.
 *
 * Configuration keys (config/swagger-ui.php or environment):
 *
 *   swagger-ui.endpoint  — URI for the documentation page (default: '/docs')
 *   swagger-ui.spec_url  — URL of the OpenAPI spec to render (default: '/openapi.json')
 *   swagger-ui.renderer  — 'swagger-ui' or 'redoc' (default: 'swagger-ui')
 *   app.name             — used as the HTML page title (default: 'API Documentation')
 */
final class SwaggerUiServiceProvider extends ServiceProvider
{
    /**
     * Bind `SwaggerUiController` with values read from config.
     */
    public function register(): void
    {
        $this->app->bind(SwaggerUiController::class, function (ContainerInterface $app): SwaggerUiController {
            $specUrl = '/openapi.json';
            $renderer = 'swagger-ui';
            $title = 'API Documentation';

            if ($app->has(ConfigInterface::class)) {
                $config = $app->make(ConfigInterface::class);
                $raw = $config->get('swagger-ui.spec_url', '/openapi.json');
                $specUrl = is_string($raw) ? $raw : '/openapi.json';
                $raw = $config->get('swagger-ui.renderer', 'swagger-ui');
                $renderer = is_string($raw) ? $raw : 'swagger-ui';
                $raw = $config->get('app.name', 'API Documentation');
                $title = is_string($raw) ? $raw : 'API Documentation';
            }

            return new SwaggerUiController($specUrl, $renderer, $title);
        });
    }

    /**
     * Register the documentation route.
     *
     * Wrapped in try/catch so the provider degrades gracefully in CLI and
     * test contexts where the Router is not bound.
     */
    public function boot(): void
    {
        if (!$this->app->has(Router::class)) {
            return;
        }

        $router = $this->app->make(Router::class);

        $endpoint = '/docs';

        if ($this->app->has(ConfigInterface::class)) {
            $config = $this->app->make(ConfigInterface::class);
            $raw = $config->get('swagger-ui.endpoint', '/docs');
            $endpoint = is_string($raw) ? $raw : '/docs';
        }

        $router->get($endpoint, [SwaggerUiController::class, '__invoke']);
    }
}
