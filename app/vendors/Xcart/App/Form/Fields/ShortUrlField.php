<?php

namespace Xcart\App\Form\Fields;

/**
 * Class ShortUrlField
 * @package Mindy\Form
 */
class ShortUrlField extends CharField
{
    public function getValue()
    {
        $slugs = explode('/', parent::getValue());
        return end($slugs);
    }
}
