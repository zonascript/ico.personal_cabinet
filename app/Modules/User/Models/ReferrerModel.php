<?php

namespace Modules\User\Models;

use Xcart\App\Orm\Model;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Fields\TimestampField;

class ReferrerModel extends Model
{
    public static function tableName()
    {
        return 'xcart_referers';
    }

    public static function getFields()
    {
        return [
            'referer_id' => [
                'class' => AutoField::className(),
            ],
            'referer' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
            ],
            'visits' => [
                'class' => IntField::className(),
                'default' => 0
            ],
            'last_visited' => [
                'class' => TimestampField::className(),
                'autoNow' => true
            ]
        ];
    }
}