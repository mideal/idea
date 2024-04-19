<?php

namespace Idea\Console;

use Idea\Routing\Router;
use Idea\Console\Application as ConsoleApplication;
use Idea\Application;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Command\Command;

class KernelConsole
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

    public function handle(): int
    {
        $consoleApplication = new ConsoleApplication();

        foreach ((new Finder())->files()->in($this->app->basePath.'/app/Console/Commands') as $file) {
            $command = str_replace(
                ['/', '.php'],
                ['\\', ''],
                str_replace($this->app->basePath . '/app', 'App', $file->getRealPath())
            );
            if (is_subclass_of($command, Command::class) &&
                ! (new \ReflectionClass($command))->isAbstract()) {
                $consoleApplication->add(new $command());
            }
        }

        return $consoleApplication->run();
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
