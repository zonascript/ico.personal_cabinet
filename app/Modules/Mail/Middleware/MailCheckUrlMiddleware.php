<?php

/**
 * User: max
 * Date: 31/08/15
 * Time: 16:36
 */

namespace Modules\Mail\Middleware;

use Mindy\Http\Request;
use Mindy\Middleware\Middleware;
use Modules\Mail\Models\Mail;
use Modules\Mail\Models\UrlChecker;

class MailCheckUrlMiddleware extends Middleware
{
    public function processRequest(Request $request)
    {
        $uniqueId = $request->get->get('uniqueId', null);
        if ($uniqueId !== null) {
            $mail = Mail::objects()->get(['unique_id' => $uniqueId]);
            if ($mail !== null) {
                $model = new UrlChecker([
                    'mail' => $mail,
                    'url' => $request->getPath()
                ]);
                $model->save();
            }
        }
    }
}
