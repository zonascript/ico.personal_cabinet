<?php

namespace Modules\Pages\Models;

use Closure;
use Modules\Pages\PagesModule;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Xcart\App\Helpers\Paths;
use Xcart\App\Main\Xcart;
use Xcart\App\Orm\Fields\BooleanField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\DateTimeField;
use Xcart\App\Orm\Fields\ImageField;
use Xcart\App\Orm\Fields\TextField;
use Xcart\App\Orm\SlugFields\AutoSlugField;
use Xcart\App\Orm\TreeModel;

/**
 * Class Page
 * @package Modules\Pages
 * @method static \Modules\Pages\Models\PageManager objects($instance = null)
 */
class Page extends TreeModel
{
    const PAGE = 0;
    const PAGESET = 1;

    public $metaConfig = [
        'title' => 'name',
        'keywords' => 'content',
        'description' => 'content_short'
    ];

    /**
     * Prefix for cache
     */
    const CACHE_PREFIX = 'pages_';

    public static function getFields()
    {
        $sizes = Xcart::app()->getModule('Pages')->sizes;

        return array_merge(parent::getFields(), [
            'name' => [
                'class' => CharField::className(),
                'required' => true,
                'verboseName' => PagesModule::t('Name')
            ],
            'url' => [
                'class' => AutoSlugField::className(),
                'source' => 'name',
                'verboseName' => PagesModule::t('Url'),
                'unique' => true
            ],
            'content' => [
                'class' => TextField::className(),
                'null' => true,
                'verboseName' => PagesModule::t('Content')
            ],
            'content_short' => [
                'class' => TextField::className(),
                'null' => true,
                'verboseName' => PagesModule::t('Short content')
            ],
            'file' => [
                'class' => ImageField::className(),
                'null' => true,
                'sizes' => $sizes,
                'verboseName' => PagesModule::t('File'),
            ],
            'created_at' => [
                'class' => DateTimeField::className(),
                'autoNowAdd' => true,
                'verboseName' => PagesModule::t("Created at")
            ],
            'updated_at' => [
                'class' => DateTimeField::className(),
                'autoNow' => true,
                'verboseName' => PagesModule::t("Updated at")
            ],
            'published_at' => [
                'class' => DateTimeField::className(),
                'null' => true,
                'verboseName' => PagesModule::t("Published at"),
            ],
            'view' => [
                'class' => CharField::className(),
                'null' => true,
                'verboseName' => PagesModule::t('View')
            ],
            'view_children' => [
                'class' => CharField::className(),
                'null' => true,
                'verboseName' => PagesModule::t('View children')
            ],
            'is_index' => [
                'class' => BooleanField::className(),
                'verboseName' => PagesModule::t('Is index')
            ],
            'is_published' => [
                'class' => BooleanField::className(),
                'verboseName' => PagesModule::t('Is published'),
                'default' => true
            ],
            'sorting' => [
                'class' => CharField::className(),
                'null' => true,
                'choices' => [
                    'published_at' => PagesModule::t('Published time ASC'),
                    '-published_at' => PagesModule::t('Published time DESC'),
                    'lft' => PagesModule::t('Position ASC'),
                    '-lft' => PagesModule::t('Position DESC'),
                ],
                'verboseName' => PagesModule::t("Sorting")
            ],
        ]);
    }

    public static function objectsManager($instance = null)
    {
        /** @var  TreeModel $instance */
        $className = get_called_class();
        $instance = $instance ? $instance : new $className;
        return new PageManager($instance, $instance->getConnection());
    }

    public function __toString()
    {
        return (string)$this->name;
    }

    /**
     * @return array of page types
     */
    public function getTypes()
    {
        return [
            self::PAGE => PagesModule::t('Page'),
            self::PAGESET => PagesModule::t('Set of pages'),
        ];
    }

    /**
     * Return view for this model
     * @return string
     */
    public function getView()
    {
        if (empty($this->view)) {
            // Если представления не найдены берем стандартные
            $parent = $this->objects()->ancestors()->filter(['view_children__isnull' => false])->exclude(['view_children' => ''])->limit(1)->get();
            if ($parent) {
                $this->view = $parent->view_children;
            } else {
                $this->view = $this->getIsLeaf() ? 'page.tpl' : 'pageset.tpl';
            }
        }

        return $this->view;
    }

    /**
     * Get available views
     * @return array
     */
    public static function getViews()
    {
        $finder = Xcart::app()->getComponent('finder');
        $theme = $finder->theme;
        if ($theme instanceof Closure) {
            $theme = $theme->__invoke();
        }
        $pathApp = Paths::get($theme ? 'base.themes.' . $theme . '.templates.pages' : 'base.templates.pages');
        $pathModule = Paths::get('base.modules.pages.templates.pages');

        $templates_app = self::getTemplates($pathApp);
        $templates_module = self::getTemplates($pathModule);

        $templates = [null => ''];
        foreach ($templates_app as $template) {
            $templates[$template] = $template;
        }
        foreach ($templates_module as $template) {
            $templates[$template] = $template;
        }

        return $templates;
    }

    /**
     * Get templates
     * @param $dir
     * @return array
     */
    public static function getTemplates($dir)
    {
        if (!is_dir($dir)) {
            return [];
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        $files = [];
        /** @var RecursiveDirectoryIterator $it */
        while ($it->valid()) {
            if (!$it->isDot() && substr($it->getSubPathName(), 0, 1) !== '_') {
                $files[] = $it->getSubPathName();
            }
            $it->next();
        }
        return $files;
    }

    /**
     * Find parent views if this view is not set
     * @return bool|mixed
     */
    protected function getParentView()
    {
        $model = $this->tree()
            ->filter([
                'lft__lt' => $this->lft,
                'rgt__gt' => $this->rgt,
                'root' => $this->root,
                'view_children__isnull' => false
            ])
            ->order('-lft')
            ->get();

        return $model ? $model->view_children : null;
    }

    public function getAbsoluteUrl()
    {
        return Xcart::app()->router->url('page:view', ['url' => $this->url]);
    }

    /**
     * @return \Xcart\App\Orm\QuerySet
     */
    public function getChildrenQuerySet()
    {
        $qs = $this->objects()->published()->children();
        if ($this->sorting) {
            $qs = $qs->order([$this->sorting]);
        }
        return $qs;
    }

    /**
     * @param \Modules\Pages\Models\Page $owner
     * @param bool $isNew
     */
    public function beforeSave($owner, $isNew)
    {
        if ($owner->is_index) {
            $owner->objects()->update(['is_index' => false]);
        }

        if ($this->is_published) {
            if (empty($owner->published_at)) {
                $owner->published_at = new \DateTime();
            }
        }

    }

    /**
     * @param \Modules\Pages\Models\Page $owner
     */
    public function afterSave($owner, $isNew)
    {
//        Xcart::app()->cache->set(self::CACHE_PREFIX . $owner->getAbsoluteUrl(), $owner);
    }
}
