<?php

namespace Modules\Redirect\Forms;

use Mindy\Form\Fields\DropDownField;
use Mindy\Form\Fields\TextField;
use Mindy\Form\ModelForm;
use Modules\Redirect\Models\Redirect;
use Modules\Redirect\RedirectModule;

class RedirectForm extends ModelForm
{
    public function getModel()
    {
        return new Redirect;
    }
}
