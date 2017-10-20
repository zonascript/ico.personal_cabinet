<?php
namespace Modules\Product\Models;

use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Model;

class ProductTermModel extends Model
{
    public static function tableName()
    {
        return 'xcart_pc_terms';
    }

    public static  function getFields()
    {
        return [
            'termid' => [
                'class' => AutoField::className(),
            ],
            'term' => [
                'class' => CharField::className(),
                'null' => false,
                'default' => ''
            ]
        ];
    }
}