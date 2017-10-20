<?php
namespace Modules\User\Helpers;

use Modules\User\Models\ReferrerModel;
use Modules\User\Models\SurfMetaModel;
use Modules\User\Models\SurfPathModel;
use Xcart\App\Main\Xcart;

class SurfingHelper
{
    public static function logSurfPath(array $params = [])
    {
        $model = new SurfPathModel($params);

        $sReferalUrl = null;
        $aGoalArray = [];

        if (defined("IS_ROBOT") || !Xcart::app()->request->session->getId()) {
            return false;
        }

        $oSurfMeta = SurfMetaModel::getInstance();

        if ($oSurfMeta->id) {

            if (in_array($model->resource_type, [SurfPathModel::GOAL_TYPE_ADD_TO_CART, SurfPathModel::GOAL_TYPE_CHECKOUT, SurfPathModel::GOAL_TYPE_SEARCH, SurfPathModel::GOAL_TYPE_ORDER])) {
                $aGoalArray[$model->goals_arr[$model->resource_type]] = "Y";
            }

            $oSurfMeta->points_visited++;
            $oSurfMeta->setAttributes(array_merge($oSurfMeta->getAttributes(), $aGoalArray));

            $model->meta_id = $oSurfMeta->id;
            $model->timestamp = time();
            $model->position = $oSurfMeta->points_visited;

            if ($model->meta_id) {
                if ($model->isValid()) {
                    $model->save();
                }
            }

            if (!is_null($sReferalUrl = SurfingHelper::getReferUrl())) {
                $referer = (string) urldecode($sReferalUrl);

                $oReferer = ReferrerModel::objects()->filter(['referer' => $referer])->limit(1)->get();
                if (!$oReferer) {
                    $oReferer = new ReferrerModel(['referer' => $referer]);
                }
                $oReferer->visits++;
                $oReferer->save();

                $oSurfMeta->points_visited++;
                $oSurfMeta->referal_url = $referer;
                if ($oSurfMeta->id) {
                    (new SurfPathModel([
                            'meta_id'         => $oSurfMeta->id,
                            'resource_id'     => $oReferer->referer_id,
                            'resource_type'   => SurfPathModel::GOAL_TYPE_REFERER,
                            'timestamp'       => time(),
                            'position'        => $oSurfMeta->points_visited,
                            'additional_data' => Xcart::app()->request->getUserAgent(),
                        ]
                    ))->save();
                }
            }

            $oSurfMeta->save();

            return true;
        }

        return false;
    }

    public static function getSurfPathAdditionalData($params)
    {
        $additional_data = '';
        $selected_fv_id_arr = [];
        if ($params['resource_type'] == SurfPathModel::GOAL_TYPE_SEARCH) {
            $aUri = Xcart::app()->request->getQueryArray();
            $req_arr = explode("/", $aUri["request_uri"]);
            $additional_data = $req_arr[2];
        }
        if (in_array($params['resource_type'], [
                SurfPathModel::GOAL_TYPE_CATEGORY,
                SurfPathModel::GOAL_TYPE_BRAND,
                SurfPathModel::GOAL_TYPE_SEARCH
            ])
            && !empty($params['cidev_filters_tree_sorted'])
            && is_array($params['cidev_filters_tree_sorted'])) {
            foreach ($params['cidev_filters_tree_sorted'] as $v) {
                if (!empty($v["filter_values"]) && is_array($v["filter_values"])) {
                    foreach ($v["filter_values"] as $tree_filter_values) {
                        if ($tree_filter_values["selected"] == "Y") {
                            $selected_fv_id_arr[] = $tree_filter_values["fv_id"];
                        }
                    }
                }
            }
            if (!empty($selected_fv_id_arr)) {
                if (!empty($additional_data)) {
                    $additional_data .= ',';
                }
                $additional_data .= implode(",", $selected_fv_id_arr);
            }
        }
        return $additional_data;
    }

    public static function getReferUrl()
    {
        $sReferUrl = null;
        $aReferalUrl = parse_url(Xcart::app()->request->getReferrer());
        $aUri = Xcart::app()->request->getQueryArray();

        if ($aReferalUrl['host'] != Xcart::app()->request->getHost()) {
            $sPath = ltrim($aReferalUrl['path'], '/');
            $sReferUrl = $aReferalUrl['host'] . (empty($sPath) ? '' : '/' . $sPath) . (empty($aReferalUrl['query']) ? '' : "?{$aReferalUrl['query']}");
            $aUri = Xcart::app()->request->getQueryArray();

            if (!empty($aUri['origin']) && !empty($aReferalUrl['host'])) {
                $sReferUrl .= (empty($aReferalUrl['query']) ? '?' : '&') . http_build_query(['origin' => $aUri['origin']]);
            }
        }
        return $sReferUrl;
    }
}