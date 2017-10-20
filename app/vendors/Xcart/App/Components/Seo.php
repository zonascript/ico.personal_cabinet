<?php

namespace Xcart\App\Components;

use Xcart\App\Helpers\SmartProperties;

class Seo
{
    use SmartProperties;

    protected $_title = null;

    protected $_description = null;

    protected $_keywords = null;

    public function setTitle($title)
    {
        $this->_title = $title;
    }

    public function getTitle()
    {
        return $this->_title;
    }

    public function setDescription($description)
    {
        $this->_description = $description;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function setKeywords($description)
    {
        $this->_description = $description;
    }

    public function getKeywords()
    {
        return $this->_description;
    }
}