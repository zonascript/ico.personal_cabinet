<?php

namespace Modules\Brand\Helpers;


use Xcart\External_Marketplaces\DisabledMarketPlace;
use Xcart\External_Marketplaces\ExternalMarketPlace;

class BrandHelper
{
    public static function getExternalMarketplaces($brandid)
    {
        $aMarketplaces = ExternalMarketPlace::getExternalMarketPlaces();
        $aDisabledMarketPlaces = [];

        if ($brandid) {
            $aDisabledMarketPlaces = DisabledMarketPlace::getDisabledMarketPlaces($brandid, 'B');
        }

        return [
            'aExternalMarketplaces' => $aMarketplaces,
            'aDisabledMarketPlaces' => $aDisabledMarketPlaces
        ];
    }
}