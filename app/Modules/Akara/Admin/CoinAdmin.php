<?php

namespace Modules\Akara\Admin;


use Modules\Admin\Components\ModelAdmin;
use Modules\Akara\Models\Coin;

class CoinAdmin extends ModelAdmin
{
    public function getModel()
    {
        return new Coin;
    }
}