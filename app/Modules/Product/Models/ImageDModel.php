<?php
namespace Modules\Product\Models;

use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\BlobField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\IntField;

class ImageDModel extends ImageModel
{
    public static function tableName()
    {
        return parent::tableName().'_D';
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