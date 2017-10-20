<?php
namespace Modules\Sites\Models;

use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Fields\TextField;
use Xcart\App\Orm\Model;

class SiteConfigModel extends Model
{
    public static function tableName()
    {
        return 'xcart_storefronts_config';
    }

    public static function getFields()
    {
        return [
            'name' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
                'primary' => true
            ],
            'storefrontid' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0,
                'primary' => true
            ],
            'comment' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
            ],
            'category' => [
                'class' => CharField::className(),
                'null' => false,
                'length' => 32,
                'default' => '',
            ],
            'type' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => 'text',
                'choises' => [
                    'numeric' => 'numeric',
                    'text' => 'text',
                    'textarea' => 'textarea',
                    'checkbox' => 'checkbox',
                    'separator' => 'separator',
                    'selector' => 'selector',
                    'multiselector' => 'multiselector',
                ],
            ],

            'validation' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
            ],

            'orderby' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0,
            ],
            'value' => [
                'class' => TextField::className(),
                'null' => false,
                'default' => ''
            ],
            'defvalue' => [
                'class' => TextField::className(),
                'null' => false,
                'default' => ''
            ],
            'variants' => [
                'class' => TextField::className(),
                'null' => false,
                'default' => ''
            ],

        ];
    }

    public function __toString()
    {
        return $this->value;
    }
}