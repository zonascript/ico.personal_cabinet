<?php

namespace Xcart\App\Components;

use Xcart\App\Helpers\ClassNames;
use Xcart\App\Helpers\SmartProperties;
use Xcart\App\Main\Xcart;
use Xcart\App\Orm\Model;

class Breadcrumbs
{
    use SmartProperties, ClassNames;

    const DEFAULT_LIST = 'DEFAULT';

    protected $_active = self::DEFAULT_LIST;

    protected $_lists = [];

    public function setActive($name = self::DEFAULT_LIST)
    {
        return $this->to($name);
    }

    public function getActive()
    {
        return $this->_active;
    }

    public function to($name = self::DEFAULT_LIST)
    {
        $this->_active = $name;
        return $this;
    }

    public function clear()
    {
        return $this->_lists[$this->_active] = [];
    }

    public function add($name, $url = null, $params = [], $meta = [])
    {
        if (!isset($this->_lists[$this->_active])) {
            $this->_lists[$this->_active] = [];
        }
        if ($name instanceof Model) {
            if (!$url && method_exists($name, 'getAbsoluteUrl')) {
                $url = $name->getAbsoluteUrl();
            }
            $name = (string) $name;
        }
        if ($url && mb_strpos($url, '/', null, 'UTF-8') === false && mb_strpos($url, ':', null, 'UTF-8') > 0) {
            $url = Xcart::app()->router->url($url, $params);
        }
        $item = [
            'name' => $name,
            'url' => $url,
            'meta' => $meta
        ];
        $this->_lists[$this->_active][] = $item;
    }

    public function get($name = self::DEFAULT_LIST)
    {
        return isset($this->_lists[$name]) ? $this->_lists[$name] : [];
    }
}