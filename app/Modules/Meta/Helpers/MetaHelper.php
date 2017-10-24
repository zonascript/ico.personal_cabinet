<?php

namespace Modules\Meta\Helpers;

use Mindy\Base\ErrorHandler;
use Mindy\Base\Mindy;
use Mindy\Utils\RenderTrait;
use Modules\Core\Components\ParamsHelper;
use Modules\Core\Controllers\Controller;
use Modules\Meta\Models\Meta;
use Modules\Meta\Models\MetaTemplate;

class MetaHelper
{
    use RenderTrait;

    /**
     * @param \Modules\Meta\Components\MetaTrait $controller
     * @param null $canonical
     */
    public static function getMeta($controller, $canonical = null)
    {
        if ($controller instanceof ErrorHandler) {
            return;
        }
        
        $uri = Mindy::app()->request->http->requestUri;
        $meta = self::fetchMeta($uri);
        if ($meta === null && ($pos = strpos($uri, '?')) !== false) {
            // Remove query params from uri
            $meta = self::fetchMeta(substr($uri, 0, $pos));
        }

        $metaTemplateName = $controller->getMetaTemplate();
        /** @var \Modules\Meta\Models\MetaTemplate $metaTemplate */
        $metaTemplate = MetaTemplate::objects()->filter(['code' => $metaTemplateName])->limit(1)->get();
        if ($metaTemplate) {
            $metaTemplate->params = $controller->getMetaTemplateParams();
        }

        $site = null;
        if (Mindy::app()->getModule('Meta')->onSite) {
            $site = Mindy::app()->getModule('Sites')->getSite();
        }

        if ($meta) {
            echo self::renderTemplate('meta/meta_helper.html', [
                'title' => self::formatTitle($controller, $meta->title, $site),
                'canonical' => $canonical,
                'description' => $meta->description,
                'keywords' => $meta->keywords,
                'site' => $site
            ]);
        } elseif ($metaTemplate) {
            echo self::renderTemplate('meta/meta_helper.html', [
                'title' => self::formatTitle($controller, $metaTemplate->renderTitle(), $site),
                'canonical' => $canonical,
                'description' => $metaTemplate->renderDescription(),
                'keywords' => $metaTemplate->renderKeywords(),
                'site' => $site
            ]);
        } else {
            echo self::renderTemplate('meta/meta_helper.html', [
                'title' => self::formatTitle($controller, null, $site),
                'canonical' => $canonical,
                'keywords' => $controller->getKeywords(),
                'description' => $controller->getDescription(),
                'site' => $site
            ]);
        }
    }

    /**
     * @param $controller
     * @param null $title
     * @param null $site
     * @return string
     */
    protected static function formatTitle($controller, $title = null, $site = null)
    {
        $data = [];

        if ($title) {
            $data[] = $title;
        } else if ($controller && $controller->title) {
            foreach ($controller->title as $title) {
                $data[] = $title;
            }
        }

        $data[] = $site ? (string)$site : ParamsHelper::get('meta.meta.sitename');
        return implode(' - ', $data);
    }

    protected static function fetchMeta($uri)
    {
        $qs = Meta::objects()->filter(['url' => $uri]);
        if (Mindy::app()->getModule('Meta')->onSite) {
            $qs = $qs->currentSite();
        }
        return $qs->limit(1)->get();
    }
} 