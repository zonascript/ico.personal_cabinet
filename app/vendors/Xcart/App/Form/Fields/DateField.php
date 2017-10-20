<?php

namespace Xcart\App\Form\Fields;

/**
 * Class DateField
 * @package Mindy\Form
 */
class DateField extends CharField
{
    public function render()
    {
        $id = $this->getHtmlId();
        $js = "<script type='text/javascript'>$('#$id').pickmeup({format  : 'Y-m-d'});</script>";
        return parent::render() . $js;
    }
}
