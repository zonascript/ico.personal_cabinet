<?php
namespace Modules\Sites;

use Mindy\QueryBuilder\Q\QOr;
use Modules\Admin\Traits\AdminTrait;
use Modules\Sites\Helpers\CurrentSiteHelper;
use Modules\Sites\Models\SiteModel;
use Xcart\App\Cli\Cli;
use Xcart\App\Main\Xcart;
use Xcart\App\Module\Module;

class SitesModule extends Module
{
    use AdminTrait;

    public $defaultStore = 'AR';
    public $modelClass = 'Modules\Sites\Models\SiteModel';

    /**
     * @var \Modules\Sites\Models\SiteModel
     */
    private $_site;
    private $_default_site;
    private $_setted = false;

    /**
     * @var \Modules\Sites\Models\SiteConfigModel
     */
    private $_config;

    public function setSite(SiteModel $model)
    {
        $this->_setted = true;
        $this->_site = $model;
    }

    /**
     * @return \Modules\Sites\Models\SiteModel|null
     * @throws \Exception
     */
    public function getSite($default = true)
    {
        return $this->_site;
    }

    public function getSiteConfig()
    {
        if (!$this->_config) {
            $SiteModel = $this->getSite();
            $this->_config = [];

            foreach ($SiteModel->config->all() as $item) {
                $this->_config[$item->name] = $item;
            }
        }

        return $this->_config;
    }

    public function initDefaultSite()
    {
        /** @var SiteModel $model */
        if ($model = SiteModel::objects()->get(['code' => $this->defaultStore]))
        {
//            $this->setSite($model);
            $this->_default_site = $model;
        }
        else {
            throw new \Exception("Default site not found for store '{$this->defaultStore}'");
        }
    }


    public static function onApplicationRun()
    {
        $renderer = Xcart::app()->template->getRenderer();

        $renderer->addAccessorCallback('getSiteConfig', function(){
            return Xcart::app()->getModule('Sites')->getSiteConfig();
        });

        $renderer->addAccessorCallback('getSite', function(){
            return Xcart::app()->getModule('Sites')->getSite();
        });
    }
}