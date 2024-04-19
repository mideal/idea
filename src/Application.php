<?php

namespace Idea;

use Idea\Container\Container;
use Idea\Logger\LoggerFactory;
use Idea\Http\Response;
use Idea\Http\JsonResponse;
use Idea\Http\Request;
use Composer\Autoload\ClassLoader;
use Idea\Http\Kernel;
use Idea\Console\KernelConsole;

class Application
{
    public readonly string $basePath;
    public readonly Container $container;
    protected array $servicesBootstrap = [
        \Idea\Bootstrap\LoadEnvironmentVariables::class,
        \Idea\Bootstrap\LoadConfiguration::class,
        \Idea\Bootstrap\ErrorsHandler::class,
    ];

    public function __construct(?string $basePath = null)
    {
        $this->container = Container::getInstance();
        $this->basePath = is_string($basePath) ? $basePath : dirname(array_keys(ClassLoader::getRegisteredLoaders())[0]);
        $this->setBaseAliases();
        $this->setBaseServices();
        $this->bootstrap();
    }

    protected function setBaseAliases(): void
    {
        $this->container->setAlias(self::class, 'app');
        $this->container->setAlias(\Psr\Container\ContainerInterface::class, 'container');
        $this->container->setAlias(\Psr\Log\LoggerInterface::class, 'log');
        $this->container->setAlias(Request::class, 'request');
        $this->container->setAlias(\Idea\Routing\Router::class, 'router');
    }

    protected function setBaseServices(): void
    {
        $this->container->set('app', $this);
        $this->container->set('container', $this->container);
        $this->container->set('log', (new LoggerFactory())->get());
    }

    public function handleRequest(Request $request): Response | JsonResponse
    {
        $this->container->set('request', $request);
        $kernel = $this->container->resolve(Kernel::class);

        return $kernel->handle($request);
    }

    public function handleConsole(): int
    {
        $kernel = $this->container->resolve(KernelConsole::class);
        return $kernel->handle();
    }

    protected function bootstrap(): void
    {
        foreach ($this->servicesBootstrap as $service) {
            $this->container->resolve($service)->init($this);
        }
    }

    public function handleErrors(): void
    {
        $errorHandler = $this->container->resolve(\Idea\Bootstrap\ErrorsHandler::class);
        $errorHandler->init($this);
    }

}
