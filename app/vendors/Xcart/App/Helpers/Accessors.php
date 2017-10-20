<?php

namespace Xcart\App\Helpers;

trait Accessors
{
    use SmartProperties;

    public function __getInternal($key)
    {
        return $this->__smartGet($key);
    }
    public function __setInternal($name, $value)
    {
        return $this->__smartSet($name, $value);
    }

    public function __issetInternal($name)
    {
        return $this->__smartIsset($name);
    }

    public function __unsetInternal($name)
    {
        $this->__smartUnset($name);
    }

    public function __callInternal($name, $params)
    {
        return $this->__smartCall($name, $params);
    }
}