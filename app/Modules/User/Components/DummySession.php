<?php

namespace Modules\User\Components;

use Mindy\Session\IHttpSession;

class DummySession
{
    public function getId()
    {
        return null;
    }

    public function open()
    {
    }

    public function close()
    {
    }

    public function destroy()
    {
    }

    public function getIsStarted()
    {
        return false;
    }

    public function getSessionID()
    {
        return null;
    }

    public function setSessionID($value)
    {
    }

    public function regenerateID($deleteOld = false)
    {
    }

    public function getCount()
    {
    }

    public function getKeys()
    {
    }

    public function get($key, $defaultValue = null)
    {
    }

    public function itemAt($key)
    {
    }

    public function add($key, $value)
    {
    }

    public function remove($key)
    {
    }

    public function clear()
    {
    }

    public function contains($key)
    {
    }

    public function all()
    {
        return [];
    }
}