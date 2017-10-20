<?php
namespace Xcart\App\Cache\Drivers;

use Xcart\App\Cache\CacheDriver;

class Dummy extends CacheDriver
{
    protected function getValue($key)
    {
        return null;
    }

    protected function setValue($key, $data, $timeout)
    {
        return true;
    }


    public function cleanUp($force = false)
    {

    }
}