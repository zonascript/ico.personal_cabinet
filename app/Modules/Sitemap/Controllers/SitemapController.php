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
 * @date 03/07/14.07.2014 16:37
 */

namespace Modules\Sitemap\Controllers;

use Mindy\Base\Mindy;
use Modules\Core\Controllers\FrontendController;
use Modules\Sitemap\Components\SitemapIndex;
use Modules\Sitemap\SitemapModule;

class SitemapController extends FrontendController
{
    public function actionIndex()
    {
        header("Content-Type: text/xml");

        /** @var \Modules\Sitemap\SitemapModule $module */
        $module = Mindy::app()->getModule('Sitemap');

        $index = new SitemapIndex([
            'sitemaps' => $module->getSitemaps()
        ]);

        $cache = Mindy::app()->cache;
        $cacheKey = 'sitemap-index';

        $xml = $cache->get($cacheKey);
        if (!$xml) {
            $xml = $index->render();
            $cache->set($cacheKey, $xml, $module->cacheTimeout);
        }

        echo $xml;
    }

    public function actionView($name)
    {
        $sitemap = $this->getSitemap($name);
        if ($sitemap === null) {
            $this->error(404);
        }

        header("Content-Type: text/xml");

        /** @var \Modules\Sitemap\SitemapModule $module */
        $module = Mindy::app()->getModule('Sitemap');

        $cache = Mindy::app()->cache;
        $cacheKey = 'sitemap-' . $name;

        $xml = $cache->get($cacheKey);
        if (!$xml) {
            $xml = $sitemap->render();
            $cache->set($cacheKey, $xml, $module->cacheTimeout);
        }

        echo $xml;
    }

    /**
     * @param $name
     * @return null|\Modules\Sitemap\Components\Sitemap
     */
    protected function getSitemap($name)
    {
        /** @var \Modules\Sitemap\SitemapModule $module */
        $module = Mindy::app()->getModule('Sitemap');
        $sitemaps = $module->getSitemaps();
        return isset($sitemaps[$name]) ? $sitemaps[$name] : null;
    }

    public function actionHtml()
    {
        if (!$this->getModule()->enableHtmlVersion) {
            $this->error(404);
        }

        $this->addTitle(SitemapModule::t('Sitemap'));
        $this->addBreadcrumb(SitemapModule::t('Sitemap'));

        echo $this->render('sitemap/index.html', [
            'sitemaps' => Mindy::app()->getModule('sitemap')->getSitemaps()
        ]);
    }

    public function actionApiHtml()
    {
        if (!$this->getModule()->enableHtmlVersion) {
            $this->error(404);
        }

        $sitemaps = Mindy::app()->getModule('sitemap')->getSitemaps();
        $data = [];
        foreach ($sitemaps as $name => $sitemap) {
            $items = [];
            if (count($sitemap->htmlData) > 0) {
                foreach ($sitemap->htmlData as $item) {
                    $items[] = $item;
                }
            }
            $data[] = [
                'title' => $sitemap->module->name . ' - ' . $sitemap->sitemapName,
                'items' => $items
            ];
        }

        echo $this->json($data);
        Mindy::app()->end();
    }
}
