<?php

namespace Modules\Akara\Admin;


use Modules\Admin\Components\ModelAdmin;
use Modules\Akara\Models\Pool;

class PoolAdmin extends ModelAdmin
{
    public function getModel()
    {
        return new Pool;
    }

    public function getColumns()
    {
        return [
            'token',
            'coin',
        ];
    }
}