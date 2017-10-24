<?php

namespace Modules\Pages\Forms;

use Mindy\Form\Fields\UEditorField;
use Mindy\Form\ModelForm;
use Modules\Pages\Models\Block;

/**
 * Class BlockForm
 * @package Modules\Pages
 */
class BlockForm extends ModelForm
{
    public function getFields()
    {
        return [
            'content' => [
                'class' => UEditorField::class
            ],
        ];

    }

    public function getModel()
    {
        return new Block;
    }
}
