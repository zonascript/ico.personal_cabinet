<?php
namespace Modules\Main\Models;

use Mindy\QueryBuilder\Expression;
use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\BooleanField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\ImageField;
use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Model;

class EmployeesModel extends Model
{
    public static function tableName()
    {
        return 's3_employees';
    }

    public static function getFields()
    {
        return [
            'id' => AutoField::className(),
            'isCeo' => BooleanField::className(),
            'name' => CharField::className(),
            'post' => [
                'class' => CharField::className(),
                'null' => true,
            ],
            'photo' => [
                'class' => ImageField::className(),
                'sizes' => [
                    'thumb' => [
                       50,50,
                       'method' => 'adaptiveResize'
                    ]
                ]
            ],
            'position' => [
                'class' => IntField::className(),
                'default' => 9999,
                'null' => false,
            ],
        ];
    }

    public function __toString()
    {
        $str = $this->name;

        if ($this->isCeo) {
            $str .= " (CEO)";
        }

        return $str;
    }

    public function beforeSave($owner, $isNew)
    {
        /** @var self $owner */
        if( $owner->isCeo && $owner->getOldAttribute('isCeo') != $owner->isCeo) {
            self::objects()->update(['isCeo' => false]);
        }

        if ($this->position == 9999) {
            list($this->position) = static::objects()->limit(1)->valuesList(['position' => new Expression('Max(position) + 1')], true);
        }
    }
}