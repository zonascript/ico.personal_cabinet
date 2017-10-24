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
 * @date 03/07/14.07.2014 16:29
 */

namespace Modules\Sitemap\Components;


use DateTime;
use Mindy\Helper\Xml;

use Mindy\Exception\Exception;
use Mindy\Base\Mindy;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Orm\TreeModel;


abstract class Sitemap
{
    use Accessors, Configurator;

    public $nameColumn = 'name';
    /**
     * @var \Mindy\Base\UrlManager
     */
    protected $urlManager;

    /**
     * @return string model class name
     */
    abstract public function getModelClass();

    public function init()
    {
        $this->urlManager = Mindy::app()->urlManager;
    }

    public function reverse($name, $params = null)
    {
        return $this->urlManager->reverse($name, $params);
    }

    public function getModule()
    {
        return $this->getModel()->getModule();
    }

    public function getModel()
    {
        $modelClass = $this->getModelClass();
        return $modelClass ? new $modelClass : null;
    }

    /**
     * Generate xml
     * @param $data array
     * @return array
     */
    public function generateXml($data)
    {
        return [
            'url' => [
                'loc' => $this->wrapAbsoluteUrl($this->getLoc($data)),
                'lastmod' => $this->getLastMod($data),
                'changefreq' => $this->getChangeFreq($data),
                'priority' => $this->getPriority($data)
            ]
        ];
    }

    /**
     * Generate html
     * @param $data array
     * @return array
     */
    public function generateHtml($data)
    {
        return [
            'url' => $this->wrapAbsoluteUrl($this->getLoc($data)),
            'name' => $this->getName($data),
            'level' => $this->getLevel($data)
        ];
    }

    /**
     * Documentation from sitemap protocol:
     * How frequently the page is likely to change. This value provides general information to search engines
     * and may not correlate exactly to how often they crawl the page. Valid values are:
     *
     * always, hourly, daily, weekly, monthly, yearly, never
     *
     * The value "always" should be used to describe documents that change each time they are accessed.
     * The value "never" should be used to describe archived URLs.
     *
     * Please note that the value of this tag is considered a hint and not a command.
     * Even though search engine crawlers may consider this information when making decisions,
     * they may crawl pages marked "hourly" less frequently than that, and they may crawl pages marked "yearly"
     * more frequently than that. Crawlers may periodically crawl pages marked "never" so that they
     * can handle unexpected changes to those pages.
     *
     * @param $data
     * @return string
     */
    public function getChangeFreq($data)
    {
        return 'monthly';
    }

    /**
     * Documentation from sitemap protocol:
     *
     * The priority of this URL relative to other URLs on your site. Valid values range from 0.0 to 1.0.
     * This value does not affect how your pages are compared to pages on other sites—it only lets the
     * search engines know which pages you deem most important for the crawlers.
     *
     * The default priority of a page is 0.5.
     *
     * Please note that the priority you assign to a page is not likely to influence the position
     * of your URLs in a search engine's result pages. Search engines may use this information when
     * selecting between URLs on the same site, so you can use this tag to increase the likelihood that
     * your most important pages are present in a search index.
     *
     * Also, please note that assigning a high priority to all of the URLs on your site is not likely
     * to help you. Since the priority is relative, it is only used to select between URLs on your site.
     *
     * @param $data
     * @return string
     */
    public function getPriority($data)
    {
        return '0.5';
    }

    /**
     * @param $value
     * @return bool|string
     * @throws \Mindy\Exception\Exception
     */
    protected function formatLastMod($value)
    {
        if(is_int($value)) {
            return date(DATE_W3C, $value);
        } else {
            $dateTime = new DateTime($value);
            $result = $dateTime->format(DateTime::W3C);
            if ($result === false) {
                throw new Exception('Unable to format datetime object. Datetime value: ' . $value);
            }
            return $result;
        }
    }

    /**
     * Documentation from sitemap protocol:
     * The date of last modification of the file. This date should be in W3C Datetime format. This format allows you to omit the time portion, if desired, and use YYYY-MM-DD.
     * Note that this tag is separate from the If-Modified-Since (304) header the server can return, and search engines may use the information from both sources differently.
     *
     * @param $data
     * @return bool|string
     */
    public function getLastMod($data)
    {
        return $this->formatLastMod(time());
    }

    /**
     * Documentation from sitemap protocol:
     * Parent tag for each URL entry. The remaining tags are children of this tag.
     *
     * @param $data
     * @throws \Mindy\Exception\Exception
     * @return string
     */
    public function getLoc($data)
    {
        $model = $this->getModel();
        if (method_exists($model, 'getAbsoluteUrl')) {
            return rtrim(Mindy::app()->request->http->getHostInfo(), '/') . '/' . ltrim($model->getAbsoluteUrl(), '/');
        } else {
            throw new Exception("Method getLoc not implemented in class " . get_class($this));
        }
    }

    public function getSitemapName()
    {
        $model = $this->getModel();
        $module = $model->getModule();
        $name = $module->t($model->classNameShort());
        return $name;
    }

    /**
     * For Html View
     * @param $data
     * @return string
     */
    public function getName($data)
    {
        if (isset($data[$this->nameColumn])) {
            return $data[$this->nameColumn];
        }
        return $this->getLoc($data);
    }

    /**
     * Zero-based level for align in Html View
     * @param $data
     * @return int
     */
    public function getLevel($data)
    {
        $level = 0;
        // For tree view
        if (is_subclass_of($this->getModel(), TreeModel::className()) && isset($data['level'])) {
            $level = $data['level'] - 1;
        }
        return $level;
    }

    /**
     * Return query set object
     * @return \Mindy\Orm\QuerySet
     */
    public function getQuerySet()
    {
        return $this->getModel()->objects();
    }

    /**
     * Return xml object with sitemap content
     * @return string
     */
    public function getXml()
    {
        $data = [
            '@attributes' => [
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
                'xsi:schemaLocation' => 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd',
            ]
        ];

        $extra = $this->getExtraItems();
        foreach ($extra as $item) {
            $data[] = $this->generateXml($item);
        }

        $qs = $this->getQuerySet();
        if ($qs) {
            $models = $qs->asArray(true)->all();
            foreach ($models as $item) {
                $data[] = $this->generateXml($item);
            }
        }
        return Xml::encode('urlset', $data, MINDY_DEBUG);
    }

    /**
     * Return data object with sitemap content for html view
     * @return string
     */
    public function getHtmlData()
    {
        $data = [];
        $extra = $this->getExtraItems();
        foreach ($extra as $item) {
            $data[] = $this->generateHtml($item);
        }

        $qs = $this->getQuerySet();
        if ($qs) {
            $models = $qs->asArray(true)->all();
            foreach ($models as $item) {
                $data[] = $this->generateHtml($item);
            }
        }
        return $data;
    }

    /**
     * Returns extra items, not included to QuerySet (ex: root of a catalog, root of FAQ)
     * @return array
     */
    public function getExtraItems()
    {
        return [];
    }

    /**
     * @param bool $header add text/xml header
     * @return string
     */
    public function render($header = true)
    {
        if ($header) {
            header("Content-Type: text/xml");
        }
        return $this->getXml();
    }

    protected function wrapAbsoluteUrl($url)
    {
        $absHost = rtrim(Mindy::app()->request->http->getHostInfo(), '/');
        $url = ltrim($url, '/');
        return $absHost ? $absHost . '/' . $url : $url;
    }
}
