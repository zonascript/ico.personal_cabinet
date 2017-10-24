<?php

namespace Modules\Pages\Admin;

use Modules\Admin\Components\ModelAdmin;
use Modules\Pages\Forms\BlockForm;
use Modules\Pages\Models\Block;
use Modules\Pages\PagesModule;

/**
 * Class BlockAdmin
 * @package Modules\User
 */
class BlockAdmin extends ModelAdmin
{
    public function getColumns()
    {
        return ['slug', 'name'];
    }

    public function getCreateForm()
    {
        return BlockForm::class;
    }

    public function getModel()
    {
        return new Block;
    }

    public function getVerboseName()
    {
        return PagesModule::t('text block');
    }

    public function getVerboseNamePlural()
    {
        return PagesModule::t('text blocks');
    }
}

