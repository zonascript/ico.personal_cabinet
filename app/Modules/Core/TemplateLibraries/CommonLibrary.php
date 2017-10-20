<?php

namespace Modules\Core\TemplateLibraries;

use Xcart\App\Main\Xcart;
use Xcart\App\Template\Renderer;
use Xcart\App\Template\TemplateLibrary;

class CommonLibrary extends TemplateLibrary
{
    use Renderer;

    /**
     * @name render_breadcrumbs
     * @kind function
     * @return string
     */
    public static function renderBreadcrumbs($params)
    {
        $template = isset($params['template']) ? $params['template'] : '_breadcrumbs.tpl';
        $name = isset($params['name']) ? $params['name'] : 'DEFAULT';

        return self::renderTemplate($template, [
            'breadcrumbs' => Xcart::app()->breadcrumbs->get($name)
        ]);
    }

    /**
     * @name render_flash
     * @kind function
     * @return string
     */
    public static function renderFlash($params)
    {
        $template = isset($params['template']) ? $params['template'] : '_flash.tpl';

        return self::renderTemplate($template, [
            'messages' => Xcart::app()->flash->read()
        ]);
    }

    /**
     * @name build_url
     * @kind function
     * @return string
     * @throws \Xcart\App\Exceptions\InvalidConfigException
     */
    public static function buildUrl($params)
    {
        $data = isset($params['data']) ? $params['data'] : [];
        $query = Xcart::app()->request->getQueryArray();
        $query = array_replace_recursive($query, $data);
        foreach ($data as $key => $value) {
            $query[$key] = $value;
        }
        return Xcart::app()->request->getPath() . '?' . http_build_query($query);
    }
}