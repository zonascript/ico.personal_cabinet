<?php

namespace Modules\Sites\Admin;

use Modules\Admin\Contrib\Admin;
use Modules\Sites\Forms\ListConfigForm;
use Modules\Sites\Models\ListConfigModel;
use Modules\Sites\SitesModule;
use Modules\Text\Forms\InfoBlockForm;
use Modules\Text\Models\InfoBlock;
use Xcart\App\Module\Module;
use Xcart\App\Orm\Model;

class ListConfigAdmin extends Admin
{
    public $sort = 'position';

    public function getSearchColumns()
    {
        return ['sf_code', 'name', ];
    }

    public function getListColumns()
    {
        return ['(string)', 'storefront'];
    }

    public function getForm()
    {
        return new ListConfigForm();
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return new ListConfigModel();
    }

    public static function getName()
    {
        return SitesModule::t('List storefronts');
    }

    public static function getItemName()
    {
        return SitesModule::t('Name');
    }
}