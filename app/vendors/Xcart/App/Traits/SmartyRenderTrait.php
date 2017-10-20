<?php
namespace Xcart\App\Traits;

use Xcart\App\Main\Xcart;

trait SmartyRenderTrait
{
    /**
     * @param $template
     * @param array|null $params
     *
     * @return string
     */
    public function renderSmarty($template, array $params = [])
    {
        $render = \Templater::getInstance();

        if (!empty($params)) {
            foreach ($params as $name => $param) {
                $render->assign($name, $param);
            }
        }

        Xcart::app()->errorHandler->errHandler = false;

        return func_display($template, $render, false);
    }
}