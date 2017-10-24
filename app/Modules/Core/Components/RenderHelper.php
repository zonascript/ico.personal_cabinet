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
 * @date 16/07/14.07.2014 12:19
 */

namespace Modules\Core\Components;

class RenderHelper
{
    public static function notsafe($value)
    {
        return htmlspecialchars($value);
    }
}
