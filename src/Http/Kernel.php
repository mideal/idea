<?php

namespace Idea\Http;

use Idea\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Idea\Application;

class Kernel
{
    protected Application $app;

    protected Router $router;

    protected array $servicesBootstrap = [
    ];

    protected array $middleware = [
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->bootstrap();
    }

    public function handle(Request $request): Response
    {
        $this->router = include $this->app->basePath.'/routes/api.php';
        $this->app->container->set('router', $this->router);
        return $this->router->run($request, $this->app->container);
    }

    protected function bootstrap(): void
    {
        foreach ($this->servicesBootstrap as $service) {
            $this->app->container->resolve($service)->init($this->app);
        }
    }

    public function terminate($response): void
    {
    }
}
