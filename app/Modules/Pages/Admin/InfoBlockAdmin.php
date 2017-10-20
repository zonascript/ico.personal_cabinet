<?php

namespace Modules\Pages\Admin;

use Modules\Admin\Contrib\Admin;
use Modules\Pages\Forms\InfoBlockForm;
use Modules\Pages\Models\InfoBlock;
use Modules\Pages\PagesModule;
use Xcart\App\Orm\Model;

class InfoBlockAdmin extends Admin
{
    public function getSearchColumns()
    {
        return ['name', 'key'];
    }

    public function getForm()
    {
        return new InfoBlockForm();
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return new InfoBlock();
    }

    public static function getName()
    {
        return PagesModule::t('Text blocks');
    }

    public static function getItemName()
    {
        return PagesModule::t('Text block');
    }
}