<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Http\Request;
use EzPhp\SwaggerUI\SwaggerUiController;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SwaggerUiController::class)]
final class SwaggerUiControllerTest extends TestCase
{
    public function testReturns200(): void
    {
        $controller = new SwaggerUiController('/openapi.json', 'swagger-ui', 'API Documentation');

        $response = $controller($this->makeRequest());

        self::assertSame(200, $response->status());
    }

    public function testResponseHasHtmlContentType(): void
    {
        $controller = new SwaggerUiController('/openapi.json', 'swagger-ui', 'API Documentation');

        $response = $controller($this->makeRequest());

        $headers = array_change_key_case($response->headers(), CASE_LOWER);

        self::assertStringContainsString('text/html', $headers['content-type'] ?? '');
    }

    public function testSwaggerUiRendererEmbedsSpecUrlAndBundle(): void
    {
        $controller = new SwaggerUiController('/openapi.json', 'swagger-ui', 'My API');

        $response = $controller($this->makeRequest());

        self::assertStringContainsString('My API', $response->body());
        self::assertStringContainsString('/openapi.json', $response->body());
        self::assertStringContainsString('swagger-ui-bundle.js', $response->body());
    }

    public function testRedocRendererEmbedsSpecUrlAndBundle(): void
    {
        $controller = new SwaggerUiController('/openapi.json', 'redoc', 'My API');

        $response = $controller($this->makeRequest());

        self::assertStringContainsString('My API', $response->body());
        self::assertStringContainsString('spec-url="/openapi.json"', $response->body());
        self::assertStringContainsString('redoc.standalone.js', $response->body());
    }

    public function testCdnAssetsArePinnedWithSubresourceIntegrity(): void
    {
        foreach (['swagger-ui', 'redoc'] as $renderer) {
            $body = (new SwaggerUiController('/openapi.json', $renderer, 'My API'))($this->makeRequest())->body();

            self::assertMatchesRegularExpression('#cdn\.jsdelivr\.net/npm/[a-z-]+@\d+\.\d+\.\d+/#', $body);
            self::assertDoesNotMatchRegularExpression('#@\d+/#', $body, 'CDN assets must use an exact version');

            preg_match_all('#<(?:script|link)\b[^>]*cdn\.jsdelivr\.net[^>]*>#', $body, $tags);
            self::assertNotEmpty($tags[0]);

            foreach ($tags[0] as $tag) {
                self::assertMatchesRegularExpression('#integrity="sha384-[A-Za-z0-9+/=]{64}"#', $tag);
                self::assertStringContainsString('crossorigin="anonymous"', $tag);
            }
        }
    }

    public function testUnknownRendererFallsBackToSwaggerUi(): void
    {
        $controller = new SwaggerUiController('/openapi.json', 'something-else', 'My API');

        $response = $controller($this->makeRequest());

        self::assertStringContainsString('swagger-ui-bundle.js', $response->body());
    }

    public function testTitleAndSpecUrlAreHtmlEscaped(): void
    {
        $controller = new SwaggerUiController('/spec.json?x="><script>', 'swagger-ui', '<script>alert(1)</script>');

        $response = $controller($this->makeRequest());

        self::assertStringNotContainsString('<script>alert(1)</script>', $response->body());
        self::assertStringNotContainsString('x="><script>', $response->body());
    }

    private function makeRequest(): Request
    {
        return new Request('GET', '/docs');
    }
}
