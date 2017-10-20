<?php

namespace Xcart\App\Logger\Handler;

use Monolog\Handler\NullHandler as MonoNullHandler;

/**
 * Class NullHandler
 * @package Xcart\App\Logger
 */
class NullHandler extends ProxyHandler
{
    public function getHandler()
    {
        return new MonoNullHandler($this->getLevel());
    }
}
