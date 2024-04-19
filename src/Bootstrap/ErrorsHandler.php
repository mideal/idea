<?php

namespace Idea\Bootstrap;

use Idea\Application;
use Idea\Logger\LoggerFactory;
use Monolog\ErrorHandler;

class ErrorsHandler
{
    public function init(Application $app): void
    {
        if($_ENV['APP_ENV'] == 'dev' && defined('CLI_START')) {
            return;
        }
        if($_ENV['APP_ENV'] == 'dev' && !defined('CLI_START')) {
            $whoops = new \Whoops\Run();
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
            $whoops->register();
            return;
        }

        $logger = new LoggerFactory();
        $errorHandler = new ErrorHandler($logger->get('telegram'));
        $errorHandler->registerErrorHandler();
        $errorHandler->registerFatalHandler();
        $errorHandler->registerExceptionHandler();
    }
}
