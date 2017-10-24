<?php

namespace Modules\Akara\Admin;


use Mindy\Orm\Model;
use Modules\Admin\Components\ModelAdmin;
use Modules\Akara\Models\Ico;

class IcoAdmin extends ModelAdmin
{
    public function getModel()
    {
        return new Ico;
    }

    public function getColumns()
    {
        return [
            'name',
            'start_date',
            'end_date',
            'coin',
        ];
    }
}