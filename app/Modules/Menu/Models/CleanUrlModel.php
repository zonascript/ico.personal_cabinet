<?php

namespace Modules\Menu\Models;

use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Model;

class CleanUrlModel extends Model
{
    public static function tableName()
    {
        return 'xcart_clean_urls';
    }

    public static function getFields()
    {
        return [
            'clean_url' => [
                'class' => CharField::className(),
                'null' => false,
                'unique' => true,
            ],
            'resource_type' => [
                'class' => CharField::className(),
                'null' => false,
                'primary' => true,
                'length' => 1,
                'chosen' => [
                    'P' => 'Product',
                    'M' => 'Brand',
                    'C' => 'Category',
                    'S' => 'Static page'
                ],
            ],
            'resource_id' => [
                'class' => IntField::className(),
                'primary' => true,
                'null' => false,
                'default' => 0,
            ],
            'mtime' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => time(),
            ]
        ];
    }

    public function beforeSave($owner, $isNew)
    {
        $owner->mtime = time();

        parent::beforeSave($owner, $isNew);
    }
}