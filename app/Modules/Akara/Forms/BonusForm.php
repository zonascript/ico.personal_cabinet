<?php

namespace Modules\Akara\Forms;


use Mindy\Form\ModelForm;

class BonusForm extends ModelForm
{
    public function getExclude()
    {
        return [
            'created_at',
            'updated_at',
        ];
    }
}