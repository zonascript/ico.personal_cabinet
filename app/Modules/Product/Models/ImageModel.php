<?php
namespace Modules\Product\Models;

use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\BlobField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\FileField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Model;
use Xcart\Product;

class ImageModel extends Model
{
    public static function tableName()
    {
        return 'xcart_images';
    }

    public static function getFields()
    {
        return [
            'imageid' => [
                'class' => AutoField::className(),
            ],
            'id' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0,
            ],
            'image' => [
                'class' => BlobField::className(),
                'null' => false,
                'default' => '',
            ],
            'image_path' => [
                'class' => FileField::className(),
                'adapterName' => 'www',
                'null' => false,
                'default' => '',
            ],
            'image_type' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
            ],
            'image_x' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0,
            ],
            'image_y' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0,
            ],
            'image_size' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0,
            ],
            'filename' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
            ],
            'date' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0,
            ],
            'alt' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => '',
            ],
            'avail' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => 'Y',
            ],
            'orderby' => [
                'class' => IntField::className(),
                'null' => false,
                'default' => 0,
            ],
            'md5' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => 0,
            ],
        ];
    }

    public function getURL()
    {
        return ltrim($this->image_path, '.');
    }
}