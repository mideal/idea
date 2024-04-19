<?php

namespace Idea\Routing;

use Symfony\Component\Routing\Route as SymfonyRoute;

class Route extends SymfonyRoute
{
    public function middleware(array $middlewares): self
    {
        $this->addDefaults(['_middlewares' => $middlewares]);
        return $this;
    }
}
