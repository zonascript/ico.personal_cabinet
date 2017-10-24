<?php

namespace Modules\Menu\Models;

use Modules\Core\Fields\Orm\PositionField;
use Mindy\Orm\Fields\CharField;
use Mindy\Orm\TreeModel;
use Modules\Menu\MenuModule;

/**
 * Class Menu
 * @method static \Mindy\Orm\TreeManager tree($instance = null)
 * @method static \Mindy\Orm\Manager objects($instance = null)
 * @package Mindy\Orm
 */
class Menu extends TreeModel
{
    public static function getFields()
    {
        return array_merge(parent::getFields(), [
            'slug' => [
                'class' => CharField::className(),
                'verboseName' => MenuModule::t('Slug'),
                'null' => true
            ],
            'name' => [
                'class' => CharField::className(),
                'verboseName' => MenuModule::t('Name')
            ],
            'url' => [
                'class' => CharField::className(),
                'verboseName' => MenuModule::t('Url'),
                'null' => true
            ]
        ]);
    }

    public function __toString()
    {
        return (string)$this->name;
    }
}
