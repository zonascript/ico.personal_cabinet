<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 30/01/15 14:42
 */

namespace Modules\Admin\Tables;

use Mindy\Table\Columns\Column;

class SortingColumn extends Column
{
    public $virtual = true;

    public $title = '';

    public $html = [
        'class' => 'sorting',
    ];

    public function getValue($record)
    {
        return '<i class="icon sort"></i>';
    }
}
