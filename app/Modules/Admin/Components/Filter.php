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
 * @date 27/01/16 09:42
 */

namespace Modules\Admin\Components;


interface Filter
{
    /**
     * @return mixed
     */
    public function getQsFilter();
}