<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 04/09/14.09.2014 17:46
 */

namespace Modules\Sites\Helpers;

use Mindy\Base\Mindy;
use Modules\Sites\Models\Site;

class SiteHelper
{
    /**
     * @param bool $currentFirst
     * @return Site[]
     */
    public static function getSites($currentFirst = false)
    {
        $modelClass = Mindy::app()->getModule('Sites')->modelClass;
        $currentSite = Mindy::app()->getModule('Sites')->getSite();
        $sites = $modelClass::objects()->all();
        if ($currentFirst && $currentSite) {
            usort($sites, function ($siteA, $siteB) {
                $current = Mindy::app()->getModule('Sites')->getSite();
                return $siteA->id == $current->id ? 0 : 1;
            });
        }
        return $sites;
    }

    /**
     * @return string
     */
    public static function getSelect()
    {
        $request = Mindy::app()->request;
        $sites = self::getSites();
        if (count($sites) > 1) {
            $url = $request->getPath();
            $schema = $request->http->getSchema();
            $options = '';
            foreach ($sites as $site) {
                $options .= strtr('<option value="{value}"{selected}>{name}</option>', [
                    '{value}' => $schema . '://' . $site->domain . $url,
                    '{name}' => $site->name,
                    '{selected}' => $site->domain == $request->getHost() ? ' selected' : ''
                ]);
            }
            $js = 'window.location=this.value;';
            return "<select onchange='$js'>$options</select>";
        } else {
            return "";
        }
    }
}
