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
 * @date 12/06/14.06.2014 19:44
 */

namespace Modules\Sites\Middleware;

use Mindy\Base\Mindy;
use Mindy\Helper\Console;
use Mindy\Http\Request;
use Mindy\Middleware\Middleware;

class CurrentSiteMiddleware extends Middleware
{
    public function processRequest(Request $request)
    {
        if (!Console::isCli()) {
            $modelClass = Mindy::app()->getModule('Sites')->modelClass;
            $model = $modelClass::objects()->filter([
                'domain' => $this->decode($request->http->getHost())
            ])->get();
            if ($model !== null) {
                Mindy::app()->getModule('Sites')->setSite($model);
            }
        }
    }

    public function decode($value)
    {
        if (function_exists('idn_to_utf8')) {
            return idn_to_utf8($value);
        } else if (class_exists('\True\Punycode')) {
            $pc = new \True\Punycode(Mindy::app()->locale['charset']);
            return $pc->decode($value);
        } else {
            Mindy::app()->logger->error("CurrentSiteMiddleware required php intl or \\True\\Punycode packages");
            return $value;
        }
    }
}
