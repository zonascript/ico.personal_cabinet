<?php

/**
 * User: max
 * Date: 05/08/15
 * Time: 20:50
 */

namespace Modules\User\Middleware;

use Mindy\Base\Mindy;
use Mindy\Http\Request;
use Mindy\Http\Traits\HttpErrors;
use Mindy\Middleware\Middleware;
use Modules\User\Models\User;

class AuthKeyCheckMiddleware extends Middleware
{
    use HttpErrors;

    public $authKey = 'X-Auth-Key';

    public $allowUrls = [];

    public function processRequest(Request $request)
    {
        $uri = strtok($request->getPath(), '?');
        if (in_array($uri, $this->allowUrls) === false) {
            $authKey = $request->http->getHeaderValue($this->authKey);
            if (!empty($authKey)) {
                $user = User::objects()->get(['key__key' => $authKey]);
                if ($user === null) {
                    $this->error(403, 'Not authorized');
                }

                /** @var \Modules\User\Components\Auth $auth */
                $auth = Mindy::app()->auth;
                $auth->login($user);
            } else {
                $this->error(403, 'Empty auth key');
            }
        }
    }
}
