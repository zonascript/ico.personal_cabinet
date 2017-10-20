<?php
namespace Xcart\App\Request;

use Xcart\App\Application\Application;
use Xcart\App\Helpers\Creator;

class RequestManager
{
    protected $_request;

    public $cliRequest;

    public $httpRequest;

    public function getRequest()
    {
        if (!$this->_request) {
            if (Application::getIsCliMode()) {
                $this->_request = Creator::create($this->cliRequest);
            } else {
                $this->_request = Creator::create($this->httpRequest);
            }
        }
        return $this->_request;
    }

    public function setRequest(Request $request)
    {
        $this->_request = $request;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->getRequest(), $name], $arguments);
    }

    public function __get($name)
    {
        return $this->getRequest()->{$name};
    }

    public function __set($name, $value)
    {
        $this->getRequest()->{$name} = $value;
    }
}