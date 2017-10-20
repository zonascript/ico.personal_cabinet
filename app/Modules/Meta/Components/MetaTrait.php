<?php
namespace Modules\Meta\Components;


use Xcart\App\Main\Xcart;
use Xcart\App\Orm\Model;

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
     * @var string[]
     */
    protected $title = [];

    /**
     * @var string|null
     */
    protected $metaTemplate = 'default';

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
        $this->canonical = Xcart::app()->request->getHostInfo() . '/' . ltrim($canonical, '/');
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
        $ba = Xcart::app()->breadcrumbs->getActive();
        Xcart::app()->breadcrumbs->setActive('metaTrait');
        Xcart::app()->breadcrumbs->add($name, $url);
        Xcart::app()->breadcrumbs->setActive($ba);

        return $this;
    }

    /**
     * @return array
     */
    public function getBreadcrumbs()
    {
        return Xcart::app()->breadcrumbs->get('metaTrait');
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
