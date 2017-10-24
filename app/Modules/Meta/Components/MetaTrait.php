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
 * @date 12/06/14.06.2014 19:35
 */

namespace Modules\Meta\Components;


use Mindy\Base\Mindy;
use Mindy\Orm\Model;

trait MetaTrait
{
    /**
     * @var bool
     */
    public $titleSortAsc = true;
    /**
     * @var string
     */
    protected $canonical;
    /**
     * @var string
     */
    protected $keywords;
    /**
     * @var string
     */
    protected $description;
    /**
     * @var array
     */
    protected $breadcrumbs = [];

    /**
     * @var string[]
     */
    protected $title = [];

    /**
     * @var string|null
     */
    protected $metaTemplate = null;

    /**
     * @var array
     */
    protected $metaTemplateParams = [];

    /**
     * @param $canonical string
     */
    public function setCanonical($canonical)
    {
        if($canonical instanceof Model) {
            $canonical = $canonical->getAbsoluteUrl();
        }
        $this->canonical = Mindy::app()->request->http->absoluteUrl($canonical);
    }

    /**
     * @return string
     */
    public function getCanonical()
    {
        return $this->canonical;
    }

    /**
     * @param $keywords string
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param $description string
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $value array|string
     * @return $this
     */
    public function setBreadcrumbs($value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $this->breadcrumbs = $value;
        return $this;
    }

    /**
     * @param $name
     * @param $url
     * @return $this
     */
    public function addBreadcrumb($name, $url = null, $items = [])
    {
        if ($name instanceof Model) {
            if ($url === null && method_exists($name, 'getAbsoluteUrl')) {
                $url = $name->getAbsoluteUrl();
            }
            $name = (string)$name;
        }

        $this->breadcrumbs[] = [
            'name' => $name,
            'url' => $url,
            'items' => $items
        ];
        return $this;
    }

    /**
     * @return array
     */
    public function getBreadcrumbs()
    {
        return $this->breadcrumbs;
    }

    /**
     * @param $value
     * @return $this
     */
    public function addTitle($value)
    {
        $this->title[] = (string) $value;
        return $this;
    }

    /**
     * @param $value array|string
     * @return $this
     */
    public function setTitle($value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $this->title = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getTitle()
    {
        $title = $this->title;
        if ($this->titleSortAsc) {
            krsort($title);
        }
        return $title;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPageTitle($value)
    {
        return $this->setTitle($value);
    }

    /**
     * @return array
     */
    public function getPageTitle()
    {
        return $this->getTitle();
    }

    /**
     * @param $template string
     * @param array $params
     */
    public function setMetaTemplate($template, $params = [])
    {
        $this->metaTemplate = $template;
        $this->metaTemplateParams = $params;
    }

    /**
     * @return mixed
     */
    public function getMetaTemplate()
    {
        return $this->metaTemplate;
    }

    /**
     * @return mixed
     */
    public function getMetaTemplateParams()
    {
        return $this->metaTemplateParams;
    }
}
