<?php

namespace Modules\Akara\Admin;


use Modules\Admin\Components\ModelAdmin;
use Modules\Akara\Models\Token;

class PoolAdmin extends ModelAdmin
{
    public $showPkColumn = false;

    public function getModel()
    {
        return new Token;
    }

    public function getColumns()
    {
        return [
            'token',
            'coin',
            'user',
        ];
    }
}