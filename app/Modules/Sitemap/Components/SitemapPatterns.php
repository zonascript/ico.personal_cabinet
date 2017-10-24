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
 * @date 03/07/14.07.2014 17:39
 */

namespace Modules\Sitemap\Components;


use Mindy\Base\Mindy;
use Mindy\Router\CustomPatterns;

class SitemapPatterns extends CustomPatterns
{
    public function getSitemaps()
    {
        return Mindy::app()->getModule('sitemap')->getSitemaps();
    }

    /**
     * @return array
     */
    public function getPatterns()
    {
        $patterns = [];
        $sitemaps = $this->getSitemaps();
        foreach ($sitemaps as $url => $sitemap) {
            $patterns[] = [
                'path' => array_shift($url),
                'name_prefix' => !empty($this->namespace) ? $this->namespace . '.' : '',
                'values' => [
                    'controller' => '\Modules\Sitemap\Controllers\SitemapController',
                    'action' => 'view'
                ]
            ];
        }
        return $patterns;
    }
}
