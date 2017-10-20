<?php

namespace Xcart\App\Cache;


use Xcart\App\Helpers\Creator;
use Xcart\App\Helpers\SmartProperties;

class Cache
{
    use SmartProperties;

    protected $_config = [];

    protected $_drivers = [];

    public $defaultDriver = 'default';
    public $memoryDriver = 'memory';
    public $saveInMemory = false;
    
    public function setDrivers($config)
    {
        $this->_config = $config;
    }

    /**
     * @param string $name
     * @return CacheDriver|null
     * @throws \Xcart\App\Exceptions\InvalidConfigException
     */
    public function getDriver($name = 'default')
    {
        if (!isset($this->_drivers[$name])) {
            if (isset($this->_config[$name])) {
                $this->_drivers[$name] = Creator::create($this->_config[$name]);
            } else {
                return null;
            }
        }
        return $this->_drivers[$name];
    }
    
    public function set($key, $value, $timeout = null)
    {
        if ($this->saveInMemory) {
            $this->getDriver($this->memoryDriver)->set($key, $value, $timeout);
        }

        return $this->getDriver($this->defaultDriver)->set($key, $value, $timeout);
    }

    public function get($key, $default = null)
    {
        if ($this->saveInMemory) {
            if ($value = $this->getDriver($this->memoryDriver)->get($key)) {
                return $value;
            }
        }

        $value = $this->getDriver($this->defaultDriver)->get($key, $default);

        if ($this->saveInMemory) {
            $this->getDriver($this->memoryDriver)->set($key, $value, rand(3, 5));
        }

        return $value;
    }

    public function cleanUp($force = false)
    {
        if ($this->saveInMemory) {
            $this->getDriver($this->memoryDriver)->cleanUp($force);
        }
        $this->getDriver($this->defaultDriver)->cleanUp($force);
    }

}