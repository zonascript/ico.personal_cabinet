<?php

namespace Modules\Akara\Forms;


use Mindy\Form\ModelForm;

class TransactionForm extends ModelForm
{
    public function getExclude()
    {
        return [
            'created_at',
            'updated_at',
        ];
    }
}