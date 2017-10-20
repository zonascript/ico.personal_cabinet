<?php

namespace Xcart\App\Form\Fields;

use Xcart\App\Validation\EmailValidator;

/**
 * Class EmailField
 * @package Mindy\Form
 */
class EmailField extends CharField
{
    public $type = 'email';

    public function init()
    {
        parent::init();
        $this->validators[] = new EmailValidator($this->required);
    }
}
