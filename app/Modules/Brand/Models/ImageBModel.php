<?php
namespace Modules\Brand\Models;

use Modules\Product\Models\ImageModel;

class ImageBModel extends ImageModel
{
    public static function tableName()
    {
        return parent::tableName().'_B';
    }

    public static function getFields()
    {
        return array_merge_recursive(parent::getFields(), [
            'image_path' => [
                'uploadTo' => 'D/%M/%O/%Y-%m-%d',
            ]
        ]);
    }
}