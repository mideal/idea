<?php

namespace Idea\Container;

use Psr\Container\ContainerInterface;
use Idea\Container\Exception\ServiceNotFoundException;
use Closure;
use Exception;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;

class Container implements ContainerInterface
{
    protected static self $instance;

    protected array $services = [];

    protected array $aliases = [];

    public static function getInstance(): Container
    {
        if (empty(self::$instance)) {
            self::$instance = new Container();
        }
        return self::$instance;
    }

    public function bind(string $id, $concrete): void
    {
        $this->services[$id] = $concrete;
    }

    public function get(string $id): object
    {
        $id = $this->aliases[$id] ?? $id;
        if(!$this->services[$id]) {
            throw new ServiceNotFoundException('Service not found', 1);
        }
        return $this->services[$id];
    }
    public function has(string $id): bool
    {
        return isset($this->services[$id]) ? true : false;
    }

    public function set(string $key, object $concreate): void
    {
        $this->services[$key] = $concreate;
    }

    public function setAlias(string $abstract, string $alias): void
    {
        if ($alias === $abstract) {
            throw new LogicException("[{$abstract}] is aliased to itself.");
        }
        $this->aliases[$abstract] = $alias;
    }

    public function resolve(string $abstract, array $parameters = [], bool $isSetInContainer = false): object
    {
        $key = isset($this->aliases[$abstract]) ? $this->aliases[$abstract] : $abstract;

        if ($this->has($key) && !$parameters && !$isSetInContainer) {
            return $this->services[$key];
        }

        $object = $this->build($abstract, $parameters);

        if ($isSetInContainer) {
            $this->services[$key] = $object;
        }

        return $object;
    }

    public function build($abstract, array $parameters): object
    {
        if ($abstract instanceof Closure) {
            return $abstract($parameters);
        }

        try {
            $refClass = new ReflectionClass($abstract);
        } catch (ReflectionException $e) {
            throw new Exception("Target class [$abstract] does not exist.", 0, $e);
        }

        if (!$refClass->isInstantiable()) {
            throw new Exception("Target [$abstract] is not instantiable.");
        }

        $constructor = $refClass->getConstructor();

        if (is_null($constructor)) {
            return new $abstract();
        }

        $instances = $this->resolveDependencies($constructor->getParameters());

        return $refClass->newInstanceArgs($instances);
    }

    protected function resolveDependencies(array $dependencies)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            $result = is_null($this->getClassNameFromParameter($dependency))
                            ? $this->resolvePrimitive($dependency)
                            : $this->resolve($this->getClassNameFromParameter($dependency));

            if ($dependency->isVariadic()) {
                $results = array_merge($results, $result);
            } else {
                $results[] = $result;
            }
        }

        return $results;
    }

    public function getClassNameFromParameter(ReflectionParameter  $parameter)
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if (!is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }

    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->isVariadic()) {
            return [];
        }

        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new Exception($message);

    }

    protected function resolveClass(ReflectionParameter $parameter): object
    {
        return $this->resolve($this->getClassNameFromParameter($parameter));
    }

    public function resolveMethod(object $object, string $method, array $parameters = [])
    {
        $diParameters = $this->getDiArguments($object, $method, $parameters);
        return $object->{$method}(...$diParameters);
    }

    protected function getDiArguments(object $object, string $method, array $parameters = []): array
    {

        $diParameters = [];
        $method = new \ReflectionMethod(
            $object,
            $method
        );

        foreach ($method->getParameters() as $parameter) {
            if (array_key_exists($parameter->getName(), $parameters)) {
                $diParameters[] = $parameters[$parameter->getName()];
                continue;
            }
            $diParameters[] = is_null($this->getClassNameFromParameter($parameter))
            ? $this->resolvePrimitive($parameter)
            : $this->resolve($this->getClassNameFromParameter($parameter));
        }

        return $diParameters;
    }
}
