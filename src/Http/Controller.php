<?php

namespace Idea\Http;

abstract class Controller
{
    public function callMethod(string $method, array $parameters): void
    {
        $this->{$method}(...array_values($parameters));
    }

    public function __call(string $method, array $parameters): void
    {
        throw new \BadMethodCallException(sprintf(
            'Method %s::%s does not exist.',
            static::class,
            $method
        ));
    }
}
