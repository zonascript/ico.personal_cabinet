<?php
namespace Xcart\App\Main;


use Xcart\App\Exceptions\UnknownPropertyException;
use Xcart\App\Helpers\Creator;
use Xcart\App\Helpers\SmartProperties;

trait ComponentsLibrary
{
    use SmartProperties;

    protected $_components;
    protected $_componentsConfig;
    protected $_componentsAppendedConfig;

    public function setComponents($config = [])
    {
        $this->_componentsConfig = $config;
    }

    public function getComponent($name)
    {
        if (!isset($this->_components[$name])) {
            if (isset($this->_componentsConfig[$name])
                || isset($this->_componentsAppendedConfig[$name]))
            {
                if (isset($this->_componentsConfig[$name])) {
                    $this->_components[$name] = Creator::create($this->_componentsConfig[$name]);
                }
                else {
                    $this->_components[$name] = Creator::create($this->_componentsAppendedConfig[$name]);
                }
            }
            else {
                throw new UnknownPropertyException("Component with name " . $name . " not found");
            }
        }

        return $this->_components[$name];
    }

    public function setComponent($name, $component)
    {
        if (is_object($component)) {
            $this->_components[$name] = $component;
        }
        else {
            $this->_componentsAppendedConfig[$name] = $component;
        }
    }

    public function hasComponent($name)
    {
        return isset($this->_componentsConfig[$name]) || isset($this->_components[$name]) || isset($this->_componentsAppendedConfig);
    }

    public function __get($name)
    {
        if ($this->hasComponent($name)) {
            return $this->getComponent($name);
        } else {
            return $this->__smartGet($name);
        }
    }
}