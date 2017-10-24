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
 * @date 03/07/14.07.2014 16:26
 */

namespace Modules\Sitemap;

use Mindy\Base\Module;
use Mindy\Helper\Alias;

class SitemapModule extends Module
{
    public $enableHtmlVersion = false;

    public $sitemaps = [];

    public $cacheTimeout = 3600;

    public function getSitemaps()
    {
        if (empty($this->sitemaps)) {
            $path = Alias::get('application.config.sitemaps') . '.php';
            if (is_file($path)) {
                $this->sitemaps = include_once($path);
            }
        }
        return $this->sitemaps;
    }

    public function getVersion()
    {
        return '1.0';
    }
}
