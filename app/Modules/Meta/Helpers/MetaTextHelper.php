<?php

/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 20/12/15 07:44
 */
namespace Modules\Meta\Helpers;

use Modules\Meta\Models\MetaText;

class MetaTextHelper
{
    public static function getMetaText($code, $params = [])
    {
        $metaText = MetaText::objects()->filter(['code' => $code])->limit(1)->get();
        if ($metaText) {
            $metaText->params = $params;
            return $metaText;
        }
        return null;
    }
}