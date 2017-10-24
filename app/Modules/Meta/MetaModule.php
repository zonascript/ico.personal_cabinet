<?php

namespace Modules\Meta;

use Mindy\Base\Mindy;
use Mindy\Base\Module;
use Modules\Admin\Traits\AutoAdminTrait;

class MetaModule extends Module
{
    use AutoAdminTrait;

    public $onSite;

    public function init()
    {
        if (is_null($this->onSite)) {
            $this->onSite = Mindy::app()->hasModule('Sites');
        }
    }

    public static function preConfigure()
    {
        $tpl = Mindy::app()->template;
        $tpl->addHelper('meta', ['\Modules\Meta\Helpers\MetaHelper', 'getMeta']);
        $tpl->addHelper('meta_text', ['\Modules\Meta\Helpers\MetaTextHelper', 'getMetaText']);
    }
}
