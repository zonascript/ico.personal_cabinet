<?php

namespace Xcart\App\Form\Fields;

/**
 * Class HiddenField
 * @package Mindy\Form
 */
class HiddenField extends CharField
{
    public $type = 'hidden';

    public $hidden = true;

    public function renderLabel()
    {
        return '';
    }
}
