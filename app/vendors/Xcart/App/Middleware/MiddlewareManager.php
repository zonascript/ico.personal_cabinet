<?php

namespace Xcart\App\Middleware;

use Exception;
use Xcart\App\Helpers\Creator;
use Xcart\App\Helpers\SmartProperties;
use Xcart\App\Request\Request;
use Xcart\App\Traits\Configurator;

class MiddlewareManager implements IMiddleware
{
    use Configurator, SmartProperties;

    /**
     * @var Middleware[]
     */
    public $middleware = [];

    /**
     * @var Middleware[]
     */
    private $_middleware = [];

    public function init()
    {
        foreach ($this->middleware as $middleware) {
            $this->_middleware[] = Creator::createObject($middleware);
        }
    }

    public function processView($request, &$output)
    {
        foreach ($this->_middleware as $middleware) {
            $middleware->processView($request, $output);
        }
    }

    public function processRequest($request)
    {
        foreach ($this->_middleware as $middleware) {
            $middleware->processRequest($request);
        }
    }

    /**
     * @param Exception $exception
     * @void
     */
    public function processException(Exception $exception)
    {
        foreach ($this->_middleware as $middleware) {
            $middleware->processException($exception);
        }
    }

    public function processResponse($request)
    {
        foreach ($this->_middleware as $middleware) {
            $middleware->processResponse($request);
        }
    }
}
