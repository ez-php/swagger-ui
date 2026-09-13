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

        return <<<HTML
            <!doctype html>
            <html>
            <head>
                <title>{$title}</title>
                <meta charset="utf-8">
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
            </head>
            <body>
                <div id="swagger-ui"></div>
                <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
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

        return <<<HTML
            <!doctype html>
            <html>
            <head>
                <title>{$title}</title>
                <meta charset="utf-8">
            </head>
            <body>
                <redoc spec-url="{$specUrl}"></redoc>
                <script src="https://cdn.jsdelivr.net/npm/redoc@2/bundles/redoc.standalone.js"></script>
            </body>
            </html>
            HTML;
    }
}
