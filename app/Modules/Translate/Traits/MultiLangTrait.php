<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 22/05/15 10:40
 */

namespace Modules\Translate\Traits;

use Mindy\Base\Mindy;

trait MultiLangTrait
{
    public function __get($name)
    {
        $lang = Mindy::app()->locale['language'];
        $langAttr = $name . '_' . $lang;
        return $this->__getInternalOrm($this->__isset($langAttr) ? $langAttr : $name);
    }

    /**
     * Checks if a property value is null.
     * This method overrides the parent implementation by checking if the named attribute is null or not.
     * @param string $name the property name or the event name
     * @return boolean whether the property value is null
     */
    public function __isset($name)
    {
        try {
            return $this->__getInternalOrm($name) !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
}
