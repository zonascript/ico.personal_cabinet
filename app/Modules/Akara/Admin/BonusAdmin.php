<?php

namespace Modules\Akara\Admin;


use Modules\Admin\Components\ModelAdmin;
use Modules\Akara\Forms\BonusForm;
use Modules\Akara\Models\Bonus;

class BonusAdmin extends ModelAdmin
{
    public function getModel()
    {
        return new Bonus;
    }

    public function getColumns()
    {
        return [
            'name',
            'percent',
            'end_date',
            'ico',
            'created_at',
        ];
    }

    public function getCreateForm()
    {
        return BonusForm::class;
    }
}