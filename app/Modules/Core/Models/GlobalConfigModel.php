<?php
namespace Modules\Core\Models;

use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Fields\TextField;
use Xcart\App\Orm\Model;

class GlobalConfigModel extends Model
{
    public static function tableName()
    {
        return 'xcart_config';
    }

    public static function getFields()
    {
        return [
            'name' => [
                'class' => CharField::className(),
                'primary' => true,
                'null' => false,
            ],
            'value' => [
                'class' => TextField::className(),
                'null' => false
            ],
            'defvalue' => [
                'class' => TextField::className(),
                'null' => false
            ],
            'category' => [
                'class' => CharField::className(),
                'null' => false,
                'length' => 32,
            ],
            'type' => [
                'class' => CharField::className(),
                'default' => 'text',
                'chosen' => [
                    'numeric'=>'numeric',
                    'text' => 'text',
                    'textarea' => 'textarea',
                    'checkbox' => 'checkbox',
                    'separator' => 'separator',
                    'selector' => 'selector',
                    'multiselector' => 'multiselector'
                ]
            ],
            'variants' => [
                'class' => TextField::className(),
                'null' => false
            ],
            'validation' => [
                'class' => CharField::className(),
                'null' => false
            ],
            'orderby' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0,
            ],
            'comment' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => ''
            ],
        ];
    }
}