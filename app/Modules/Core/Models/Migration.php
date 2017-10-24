<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 08/01/15 18:32
 */

namespace Modules\Core\Models;

use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\IntField;
use Mindy\Orm\Model;

/**
 * Class Core
 * @package Modules\Core
 * @method static \Modules\Core\Models\MigrationManager objects($instance = null)
 */
class Migration extends Model
{
    public static function getFields()
    {
        return [
            'module' => [
                'class' => CharField::className(),
                'editable' => false,
            ],
            'model' => [
                'class' => CharField::className(),
                'editable' => false,
            ],
            'timestamp' => [
                'class' => IntField::className(),
                'editable' => false,
            ],
        ];
    }

    public static function objectsManager($instance = null)
    {
        $className = get_called_class();
        return new MigrationManager($instance ? $instance : new $className);
    }
}
