<?php

namespace Modules\Core\Helpers;


use Modules\Core\Models\CountryModel;
use Modules\Core\Models\GeoipLitecityLocationModel;
use Modules\Core\Models\GlobalConfigModel;
use Modules\Core\Models\StateModel;
use Modules\Core\Models\TelephoneAreaModel;
use Modules\Sites\Models\SiteConfigModel;

class GeoipHelper
{
    /**
     * @param $ip
     * @return GeoipLitecityLocationModel|null
     */
    public static function getGeoipLocation($ip)
    {
        $model = null;

        if ($iIp = ip2long($ip)) {
            $model = GeoipLitecityLocationModel::objects()
                ->filter(['blocks__startIpNum__lt' => $iIp])
                ->order(['-blocks__startIpNum'])
                ->limit(1)
                ->get();
        }

        return $model;
    }

    public static function getAreaCodeFromPhone($phone)
    {
        $area = null;

        if (preg_match('/\d{3}/', $phone, $match)) {
            $area = $match[0];
        }

        return $area;
    }

    /**
     * @param $phone
     * @return null|StateModel
     */
    public static function getStateByPhone($phone)
    {
        /** @var StateModel $model */
        $model = null;

        if ($area = static::getAreaCodeFromPhone($phone)) {
            if ($areaModel = TelephoneAreaModel::objects()->get(['area_code' => $area])) {
                if (array_key_exists($areaModel->country, CountryModel::$codes)) {
                    $model = StateModel::objects()
                        ->filter(
                            [
                                'country_code' => CountryModel::$codes[$areaModel->country],
                                'state' => $areaModel->state,
                            ]
                        )
                        ->limit(1)
                        ->get();
                }
            }
        }

        return $model;
    }

    public static function getPhones($params)
    {
        $state = $orderState = null;
		
		if ($params['state'] && $params['country']) {
			$state = StateModel::objects()->get(
				[
					'code' => $params['state'],
					'country_code' => $params['country']
				]
			);
		}

        if ($params['phone']) {
			$orderState = static::getStateByPhone($params['phone']);
		}

        $phones = ($state ? $state->phone : '') . ($orderState ? ($state->phone && $orderState->phone ? ', ' : '') . $orderState->phone : '');

        if (empty($phones)) {
            $phones = $params['storefrontid']
                ? SiteConfigModel::objects()->get(['name' => 'cidev_top_header_code', 'storefrontid' => $params['storefrontid']])->value
                : GlobalConfigModel::objects()->get(['name' => 'cidev_top_header_code'])->value;
        }

        return $phones;
    }
}