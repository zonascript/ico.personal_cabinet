<?php

namespace Xcart\App\Storage;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;

class Storage
{
    /**
     * @var Filesystem[]|null
     */
    protected static $filesystems = [];

    public $default = 'default';
    public $adapters = [];

    public function init()
    {
        if (empty($this->adapters[$this->default])) {
            throw new \Exception("'default' adapter not set");
        }

//        foreach ($this->adapters as $config) {
//            if ($config['class'] instanceof AbstractAdapter) {
//
//            }
//        }
    }

    /**
     * @param FilesystemInterface $filesystem
     * @param string|null $name Name of adapter
     */
    public function setFilesystem(FilesystemInterface $filesystem, $name = null)
    {
        self::$filesystems[$name] = $filesystem;
    }

    /**
     * @param string|null $name Name of adapter
     *
     * @return \League\Flysystem\Filesystem|null
     * @throws \Xcart\App\Exceptions\UnknownPropertyException|\Exception
     */
    public function getFilesystem($name = null)
    {
        if (!$name) {
            $name = $this->default;
        }

        if (empty(self::$filesystems[$name])) {
            if (empty($this->adapters[$name]['class'])) {
                throw new \Exception("'{$name}' adapter class not set");
            }
            
            $class = $this->adapters[$name]['class'];
            $config = $this->adapters[$name];
            unset($config['class']);
            
            self::$filesystems[$name] = new Filesystem((new $class($config)));
        }

        return self::$filesystems[$name];
    }
}