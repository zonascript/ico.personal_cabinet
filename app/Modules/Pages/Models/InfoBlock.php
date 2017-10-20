<?php

namespace Modules\Pages\Models;

use Xcart\App\Orm\Fields\AutoField;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Orm\Fields\TextField;
use Xcart\App\Orm\Model;

/**
 * Class InfoBlock
 * @package Modules\Text\Models
 *
 * @property String name
 * @property String text
 * @property String key
 */
class InfoBlock extends Model
{
    public static function getFields()
    {
        return [
            'id' => AutoField::className(),
            'name' => [
                'class' => CharField::className(),
                'label' => 'Наименование'
            ],
            'text' => [
                'class' => TextField::className(),
                'label' => 'Текст'
            ],
            'key' => [
                'class' => CharField::className(),
                'label' => 'Ключ (для разработчика)',
                'null' => true
            ]
        ];
    }

    public function __toString()
    {
        return (string) $this->name;
    }
}