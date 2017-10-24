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
 * @date 19/12/15 11:16
 */
namespace Modules\Meta\Admin;

use Modules\Admin\Components\ModelAdmin;
use Modules\Meta\Models\MetaText;
use Modules\Meta\MetaModule;

class MetaTextAdmin extends ModelAdmin
{
    public function getColumns()
    {
        return ['code', 'title'];
    }
    
    public function getModel()
    {
        return new MetaText;
    }
    
    public function getNames($model = null)
    {
        return [
            MetaModule::t('Meta Texts'),
            MetaModule::t('Create Meta Text'),
            MetaModule::t('Update Meta Text')
        ];
    }
}