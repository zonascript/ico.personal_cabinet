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
 * @date 19/11/14.11.2014 18:21
 */

namespace Modules\User\Helpers;

use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Utils\RenderTrait;
use Modules\User\Forms\LoginForm;

class UserHelper
{
    use RenderTrait, Configurator;

    public static function render($request, $template = "user/_login.html")
    {
        return self::renderTemplate($template, [
            'form' => new LoginForm,
            'request' => $request
        ]);
    }
}
