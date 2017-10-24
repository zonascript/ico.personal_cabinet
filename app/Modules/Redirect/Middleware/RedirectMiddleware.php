<?php

namespace Modules\Redirect\Middleware;

use Mindy\Http\Request;
use Mindy\Middleware\Middleware;
use Modules\Redirect\Models\Redirect;

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 14/04/14.04.2014 15:20
 */
class RedirectMiddleware extends Middleware
{
    protected function decode($value)
    {
        $value = preg_replace("/%u([0-9a-f]{3,4})/i", "&#x\\1;", urldecode($value));
        return html_entity_decode($value, null, 'UTF-8');
    }

    public function processRequest(Request $request)
    {
        $pathInfo = $this->decode($request->path);
        if (!empty($pathInfo)) {
            $model = Redirect::objects()->filter([
                'from_url' => '/' . ltrim($pathInfo, '/')
            ])->get();
            if ($model === null) {
                $model = Redirect::objects()->filter([
                    'from_url' => '/' . ltrim(strtok($pathInfo, '?'), '/') . '*'
                ])->get();
                if ($model === null) {
                    return false;
                } else {
                    $request->redirect($model->to_url, null, $model->type);
                }
            } else {
                $request->redirect($model->to_url, null, $model->type);
            }
        }
        return false;
    }
}
