<?php

/**
 * User: max
 * Date: 27/08/15
 * Time: 20:33
 */

namespace Modules\Mail\Forms;

use Mindy\Form\Fields\CharField;
use Mindy\Form\Fields\UEditorField;
use Mindy\Form\ModelForm;
use Modules\Mail\Models\Queue;

class QueueForm extends ModelForm
{
    public function getModel()
    {
        return new Queue;
    }

    public function getFields()
    {
        return [
            'message_html' => [
                'class' => UEditorField::className()
            ],
            'template' => [
                'class' => CharField::className(),
                'html' => [
                    'readonly' => true
                ]
            ],
        ];
    }
}
