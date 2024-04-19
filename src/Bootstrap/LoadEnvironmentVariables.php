<?php

namespace Idea\Bootstrap;

use Dotenv\Dotenv;
use Idea\Application;

class LoadEnvironmentVariables
{
    public function init(Application $app): void
    {
        $dotenv = Dotenv::createImmutable($app->basePath);
        $dotenv->load();
    }
}
