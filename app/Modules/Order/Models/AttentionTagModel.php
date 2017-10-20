<?php
namespace Modules\Order\Models;

use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Fields\TextField;
use Xcart\App\Orm\Model;

class AttentionTagModel extends Model
{
    public static function tableName()
    {
        return 'xcart_attention_tags_values';
    }

    public static function getFields()
    {
        return [
            'status_id' => AutoField::className(),
            'status' => [
                'class' => CharField::className(),
                'null' => false,
            ],
            'active' => [
                'class' => CharField::className(),
                'null' => false,
                'choices' => [
                    'Y' => 'Enabled',
                    'N' => 'Disabled'
                ],
                'default' => 'Y'
            ],
            'events' => [
                'class' => IntField::className(),
                'length' => 1,
                'null' => false,
                'choices' => [
                    0 => 'None',
                    1 => 'Trigger'
                ],
                'default' => 0
            ],
            'orderby' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0
            ],
            'color' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '#F4CCCC',
            ],
            'description' => [
                'class' => TextField::className(),
                'null' => false,
                'default' => ''
            ],
        ];
    }
}