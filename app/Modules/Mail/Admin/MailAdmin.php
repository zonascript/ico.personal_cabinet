<?php
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
 * @date 17/11/14.11.2014 16:24
 */

namespace Modules\Mail\Admin;

use Mindy\Orm\Model;
use Modules\Admin\Components\ModelAdmin;
use Modules\Mail\Forms\MailForm;
use Modules\Mail\Models\Mail;

class MailAdmin extends ModelAdmin
{
    public function getSearchFields()
    {
        return ['subject', 'email', 'queue__name'];
    }

    /**
     * @param Model $model
     * @return \Mindy\Orm\QuerySet
     */
    public function getQuerySet(Model $model)
    {
        return parent::getQuerySet($model)->order(['-id']);
    }

    public function getColumns()
    {
        return [
            'email',
            'subject',
            'created_at',
            'readed_at',
            'queue'
        ];
    }

    public function getCanCreate()
    {
        return false;
    }

    /**
     * @return \Mindy\Orm\Model
     */
    public function getModel()
    {
        return new Mail;
    }

    public function getCreateForm()
    {
        return MailForm::className();
    }
}
