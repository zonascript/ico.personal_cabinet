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
 * @date 03/07/14.07.2014 16:27
 */

namespace Modules\Sitemap\Components;


use Mindy\Base\Mindy;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Helper\Xml;

class SitemapIndex implements ISitemapIndex
{
    use Accessors, Configurator;

    public $sitemaps = [];

    public function getSitemaps()
    {
        $data = [
            '@attributes' => [
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
                'xsi:schemaLocation' => 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd',
            ]
        ];
        $http = Mindy::app()->request->http;
        $urlManager = Mindy::app()->urlManager;
        foreach ($this->sitemaps as $name => $sitemap) {
            $data[] = [
                'sitemap' => [
                    'loc' => $http->getHostInfo() . $urlManager->reverse('sitemap:view', ['name' => $name]),
                    'lastmod' => date(DATE_W3C)
                ]
            ];
        }
        return Xml::encode('sitemapindex', $data, MINDY_DEBUG);
    }

    public function render()
    {
        return $this->getSitemaps();
    }
}
