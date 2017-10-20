<?php

namespace Modules\Shipping\Models;

use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;

class TrackingLinksModel extends AutoMetaModel
{
    public static function tableName()
    {
        return 'xcart_tracking_links';
    }

    public static function getFields()
    {
        return [
            'linkid' => [
                'class' => AutoField::className()
            ],
        ];
    }
}