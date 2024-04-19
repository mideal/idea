<?php

namespace Idea\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;
use Exception;

class ServiceNotFoundException extends Exception implements NotFoundExceptionInterface
{
}
