<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Contracts\ContainerInterface;
use EzPhp\Contracts\RouterInterface;
use EzPhp\Routing\Router;
use EzPhp\SwaggerUI\SwaggerUiController;
use EzPhp\SwaggerUI\SwaggerUiServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(SwaggerUiServiceProvider::class)]
#[UsesClass(SwaggerUiController::class)]
final class SwaggerUiServiceProviderTest extends TestCase
{
    private SwaggerUiFakeContainer $container;

    private Router $router;

    private SwaggerUiServiceProvider $provider;

    protected function setUp(): void
    {
        $this->container = new SwaggerUiFakeContainer();
        $this->router = new Router($this->container);
        $this->container->instance(RouterInterface::class, $this->router);
        $this->container->instance(ContainerInterface::class, $this->container);

        $this->provider = new SwaggerUiServiceProvider($this->container);
    }

    public function testRegisterBindsSwaggerUiController(): void
    {
        $this->provider->register();

        $controller = $this->container->make(SwaggerUiController::class);

        self::assertInstanceOf(SwaggerUiController::class, $controller);
    }

    public function testBootRegistersDefaultDocsRoute(): void
    {
        $this->provider->register();
        $this->provider->boot();

        $routes = $this->router->toCache();

        $found = false;

        foreach ($routes as $route) {
            if ($route['method'] === 'GET' && $route['path'] === '/docs') {
                $found = true;
                break;
            }
        }

        self::assertTrue($found, 'GET /docs route was not registered by boot()');
    }

    public function testBootHandlesRouterNotBound(): void
    {
        $emptyContainer = new SwaggerUiFakeContainer();
        $provider = new SwaggerUiServiceProvider($emptyContainer);
        $provider->register();

        // Must not throw — Router is not bound, boot() degrades gracefully
        $provider->boot();

        $this->addToAssertionCount(1);
    }

    public function testRegisterHandlesMissingConfigWithDefaults(): void
    {
        $this->provider->register();

        // No ConfigInterface bound — controller must still be constructible with defaults.
        $controller = $this->container->make(SwaggerUiController::class);

        $response = $controller(new \EzPhp\Http\Request('GET', '/docs'));

        self::assertStringContainsString('/openapi.json', $response->body());
        self::assertStringContainsString('swagger-ui-bundle.js', $response->body());
    }
}

// ─── Fixture ─────────────────────────────────────────────────────────────────

/**
 * Minimal ContainerInterface implementation for service provider tests.
 * Supports Closure-based bindings and instance registration only.
 */
final class SwaggerUiFakeContainer implements ContainerInterface
{
    /** @var array<string, callable> */
    private array $bindings = [];

    public function bind(string $abstract, string|callable|null $factory = null): static
    {
        if (is_callable($factory)) {
            $this->bindings[$abstract] = $factory;
        }

        return $this;
    }

    public function make(string $abstract): mixed
    {
        if (!isset($this->bindings[$abstract])) {
            throw new \RuntimeException("Not bound: {$abstract}");
        }

        return ($this->bindings[$abstract])($this);
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }

    public function instance(string $abstract, object $instance): void
    {
        $this->bindings[$abstract] = static fn (ContainerInterface $_c): object => $instance;
    }
}
