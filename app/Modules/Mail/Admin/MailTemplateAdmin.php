<?php

namespace Modules\Mail\Admin;

use Modules\Admin\Components\ModelAdmin;
use Modules\Mail\Forms\MailTemplateForm;
use Modules\Mail\MailModule;
use Modules\Mail\Models\MailTemplate;

/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 16/05/14.05.2014 15:12
 */
class MailTemplateAdmin extends ModelAdmin
{
    /**
     * @return string
     */
    public function getCreateForm()
    {
        return MailTemplateForm::className();
    }

    public function getColumns()
    {
        return ['code', 'subject'];
    }
    /**
     * @return \Mindy\Orm\Model
     */
    public function getModel()
    {
        return new MailTemplate;
    }

    public function getVerboseName()
    {
        return MailModule::t('mail template');
    }

    public function getVerboseNamePlural()
    {
        return MailModule::t('mail templates');
    }
}
