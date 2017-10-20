<?php

namespace Modules\Shipping\Models;


use Xcart\App\Orm\AutoMetaModel;
use Xcart\App\Orm\Fields\AutoField;

class TrackingLinksCarrierModel extends AutoMetaModel
{
    public static function tableName()
    {
        return 'xcart_tracking_links_carrier';
    }

    public static function getFields()
    {
        return [
            'carrier_id' => [
                'class' => AutoField::className()
            ],
        ];
    }
}