<?php

declare(strict_types=1);

namespace EzPhp\SwaggerUI;

use EzPhp\Http\Request;
use EzPhp\Http\Response;

/**
 * Invokable HTTP controller that renders an API documentation viewer over an
 * OpenAPI spec URL (typically `GET /openapi.json` from `ez-php/openapi`).
 *
 * Registered by `SwaggerUiServiceProvider::boot()`. Both supported renderers
 * (`swagger-ui`, `redoc`) are loaded from a CDN — no static assets are
 * vendored by this module.
 */
final class SwaggerUiController
{
    /**
     * Pinned CDN assets with Subresource Integrity hashes. A floating tag
     * (`@5`) would silently load whatever jsDelivr serves next; with a pinned
     * version and `integrity`, the browser refuses tampered or changed files.
     * Bump version and hash together (sha384 of the exact file).
     */
    private const string SWAGGER_CSS = 'https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.33.0/swagger-ui.css';

    private const string SWAGGER_CSS_SRI = 'sha384-Ov4/wv3j2bmct8cDc5X4ngJZohVPzEmc6uDPH8WeljUxO5vtoykvMEfbu9Vh6RaW';

    private const string SWAGGER_JS = 'https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.33.0/swagger-ui-bundle.js';

    private const string SWAGGER_JS_SRI = 'sha384-YDALVcy8kj8yltLBVi1vBiBAUqdxvus673gM8XKwiy6aDUJFXivF/KCufekjYbVf';

    private const string REDOC_JS = 'https://cdn.jsdelivr.net/npm/redoc@2.5.4/bundles/redoc.standalone.js';

    private const string REDOC_JS_SRI = 'sha384-w447zOpYfw/1Tv/5AK9NfHTlQIqE3RVR6KY62jCyy9zNDgO64cMwGGP1Fj0zJVf5';

    /**
     * @param string $specUrl  URL of the OpenAPI spec the viewer fetches, e.g. '/openapi.json'.
     * @param string $renderer Either 'swagger-ui' or 'redoc'. Falls back to 'swagger-ui' for any other value.
     * @param string $title    HTML `<title>` for the documentation page.
     */
    public function __construct(
        private readonly string $specUrl,
        private readonly string $renderer,
        private readonly string $title,
    ) {
    }

    /**
     * Render the documentation page as HTML.
     *
     * @param Request $request Incoming HTTP request (unused but required by the router signature).
     *
     * @return Response
     */
    public function __invoke(Request $request): Response
    {
        $html = $this->renderer === 'redoc' ? $this->renderRedoc() : $this->renderSwaggerUi();

        return (new Response($html, 200))->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Build the Swagger UI HTML page (assets from the jsDelivr CDN).
     */
    private function renderSwaggerUi(): string
    {
        $title = htmlspecialchars($this->title, ENT_QUOTES);
        $specUrl = htmlspecialchars($this->specUrl, ENT_QUOTES);
        $css = self::SWAGGER_CSS;
        $cssSri = self::SWAGGER_CSS_SRI;
        $js = self::SWAGGER_JS;
        $jsSri = self::SWAGGER_JS_SRI;

        return <<<HTML
            <!doctype html>
            <html>
            <head>
                <title>{$title}</title>
                <meta charset="utf-8">
                <link rel="stylesheet" href="{$css}" integrity="{$cssSri}" crossorigin="anonymous">
            </head>
            <body>
                <div id="swagger-ui"></div>
                <script src="{$js}" integrity="{$jsSri}" crossorigin="anonymous"></script>
                <script>
                    window.onload = function () {
                        window.ui = SwaggerUIBundle({
                            url: "{$specUrl}",
                            dom_id: "#swagger-ui",
                        });
                    };
                </script>
            </body>
            </html>
            HTML;
    }

    /**
     * Build the ReDoc HTML page (assets from the jsDelivr CDN).
     */
    private function renderRedoc(): string
    {
        $title = htmlspecialchars($this->title, ENT_QUOTES);
        $specUrl = htmlspecialchars($this->specUrl, ENT_QUOTES);
        $js = self::REDOC_JS;
        $jsSri = self::REDOC_JS_SRI;

        return <<<HTML
            <!doctype html>
            <html>
            <head>
                <title>{$title}</title>
                <meta charset="utf-8">
            </head>
            <body>
                <redoc spec-url="{$specUrl}"></redoc>
                <script src="{$js}" integrity="{$jsSri}" crossorigin="anonymous"></script>
            </body>
            </html>
            HTML;
    }
}
