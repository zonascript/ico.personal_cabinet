<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 19/12/15 11:06
 */
namespace Modules\Meta\Admin;

use Modules\Admin\Components\ModelAdmin;
use Modules\Meta\Models\MetaTemplate;
use Modules\Meta\MetaModule;

class MetaTemplateAdmin extends ModelAdmin
{
    public function getColumns()
    {
        return ['code', 'title'];
    }
    
    public function getModel()
    {
        return new MetaTemplate;
    }
    
    public function getNames($model = null)
    {
        return [
            MetaModule::t('Meta Templates'),
            MetaModule::t('Create Meta Template'),
            MetaModule::t('Update Meta Template')
        ];
    }
}