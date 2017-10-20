<?php

namespace Modules\Meta;

use Modules\Admin\Traits\AdminTrait;
use Modules\Meta\Helpers\MetaHelper;
use Xcart\App\Main\Xcart;
use Xcart\App\Module\Module;

class MetaModule extends Module
{
    use AdminTrait;

    public $onSite;

    public function init()
    {
        if (is_null($this->onSite)) {
            $this->onSite = (bool)Xcart::app()->getModule('Sites');
        }
    }

    public static function onApplicationRun()
    {
        $tpl = Xcart::app()->template->getRenderer();
        $tpl->addFunction('meta', function($params){
            MetaHelper::getMeta($params['controller']);
        });
        $tpl->addFunction('meta_text', ['\Modules\Meta\Helpers\MetaTextHelper', 'getMetaText']);

    }
}
