<?php

namespace Modules\Product\Models;


use Xcart\App\Orm\Fields\IntField;
use Xcart\App\Orm\Model;

class ProductCategoryTermsModel extends Model
{
    public static function tableName()
    {
        return 'xcart_pc_category_terms';
    }

    public static  function getFields()
    {
        return [
            'categoryid' => [
                'class' => IntField::className(),
                'primary' => true
            ],
            'termid' => [
                'class' => IntField::className(),
                'primary' => true
            ],
            'term_count' => [
                'class' => IntField::className(),
            ],
        ];
    }
}