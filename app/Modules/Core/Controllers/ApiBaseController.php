<?php

namespace Modules\Core\Controllers;

use Mindy\Base\Mindy;
use Mindy\Locale\Translate;
use Modules\User\Permissions\Rule;

class ApiBaseController extends Controller
{
    protected function formatReactRoute(array $data)
    {
        list($routeData, $paramsData) = $data;
        $tmp = explode('\\', array_shift($routeData));
        $reactRoute = $tmp[2] . ucfirst(array_shift($routeData));
        return [
            'route' => $reactRoute,
            'data' => $paramsData
        ];
    }

    protected function getReactRoute($httpMethod, $uri)
    {
        $router = Mindy::app()->urlManager;
        $uri = strtok($uri, '?');
        $uri = ltrim($uri, '/');
        $data = $router->dispatchRoute($httpMethod, $uri);
        if ($data === false) {
            if ($router->trailingSlash && substr($uri, -1) !== '/') {
                $data = $router->dispatchRoute($httpMethod, $uri . '/');
                if ($data === false) {
                    return false;
                }
                return $this->formatReactRoute($data);
            } else {
                return false;
            }
        }

        return $this->formatReactRoute($data);
    }

    /**
     * @param null|Rule $rule
     */
    public function accessDenied($rule = null)
    {
        http_response_code(403);
        echo $this->json([
            'status' => false,
            'error' => Translate::getInstance()->t('main', 'You are not authorized to perform this action.'),
        ]);
    }

    public function end()
    {
        if (defined('MINDY_TESTS')) {
            return;
        }
        Mindy::app()->end();
    }
}