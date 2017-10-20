<?php

namespace Modules\Order\Models;

use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;

class FraudCheckModel extends AutoMetaModel
{
    public static function tableName()
    {
        return 'xcart_fraud_check';
    }

    public static function getFields()
    {
        return [
            'id' => [
                'class' => AutoField::className(),
            ],
            'question_template_body' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => ''
            ]
        ];
    }
}