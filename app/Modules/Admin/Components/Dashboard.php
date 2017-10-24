<?php

/**
 * User: max
 * Date: 22/07/15
 * Time: 14:54
 */

namespace Modules\Admin\Components;

use Mindy\Utils\RenderTrait;

abstract class Dashboard
{
    use RenderTrait;

    abstract public function getTemplate();

    public function __toString()
    {
        return (string)$this->renderTemplate($this->getTemplate(), $this->getData());
    }

    public function getData()
    {
        return [

        ];
    }

    public function render()
    {
        echo $this->renderTemplate($this->getTemplate(), $this->getData());
    }
}
