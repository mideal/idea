<?php

namespace Idea\Routing;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Idea\Http\Response\ApiResponse;
use Idea\Container\Container;
use Exception;

class Router
{
    private RouteCollection $routCollections;

    public function __construct()
    {
        $this->routCollections = new RouteCollection();
    }

    public function get(string $uri, array|string|callable|null $action = null): Route
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, 'get'.$uri, $action);
    }
    public function post(string $uri, array|string|callable|null $action = null): Route
    {
        return $this->addRoute('POST', $uri, 'post'.$uri, $action);
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->routCollections;
    }

    public function addRoute(string|array $methods, string $path, string $name, array|string|callable|null $action = null): Route
    {
        $route = new Route(methods:$methods, path: $path);
        if($action) {
            $route->addDefaults(['_controller' => $action]);
        }
        $this->routCollections->add($name, $route);
        return $route;
    }

    public function run(Request $request, Container $container): Response
    {
        try {
            $routeParameters = $this->getRouteParameters($request);
            foreach ($routeParameters['_middlewares'] as $middlewareClass) {
                $middleware = $container->resolve($middlewareClass);
                $container->resolveMethod($middleware, 'handle');
            }
            $parameters = array_filter($routeParameters, function ($routeParameter) {
                return substr($routeParameter, 0, 1) != '_';
            }, ARRAY_FILTER_USE_KEY);

            if($routeParameters['_controller'] instanceof \Closure) {
                $response = $routeParameters['_controller'](...$parameters);
            } elseif(is_string($routeParameters['_controller']) && class_exists($routeParameters['_controller']) && method_exists($routeParameters['_controller'], '__invoke')) {
                $controller = $container->resolve($routeParameters['_controller']);
                $response = $container->resolveMethod($controller, '__invoke', $parameters);
            } elseif(is_array($routeParameters['_controller']) && class_exists($routeParameters['_controller'][0])) {
                $controller = $container->resolve($routeParameters['_controller'][0]);
                $response = $container->resolveMethod($controller, $routeParameters['_controller'][1], $parameters);
            } else {
                throw new Exception('No controller found');
            }

            if (!$response instanceof Response) {
                $response = new ApiResponse($response);
            }
        } catch (ResourceNotFoundException|MethodNotAllowedException $exception) {
            $response = new ApiResponse('Not Found', 404);
        } catch (Exception $exception) {
            $response = new ApiResponse('An error occurred', 500, $exception->getMessage());
        }

        if ($response->getStatusCode() === Response::HTTP_NOT_MODIFIED) {
            $response->setNotModified();
        }

        $response->prepare($request);

        return $response;
    }

    private function getRouteParameters(Request $request): array
    {
        $context = new RequestContext();
        $context->fromRequest($request);
        $matcher = new UrlMatcher($this->getRouteCollection(), $context);

        return $matcher->match($request->getPathInfo());
    }
}
