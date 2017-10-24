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
 * @date 10/03/16 08:30
 */
namespace Modules\Meta\Models;

use Mindy\Orm\Fields\CharField;
use Modules\Core\Models\SettingsModel;

class MetaSettings extends SettingsModel
{
    public static function getFields() 
    {
        return [
            'sitename' => [
                'class' => CharField::className(),
                'verboseName' => self::t("Sitename"),
                'helpText' => self::t('Will be used in title'),
                'null' => true
            ],
        ];
    }
    
    public function __toString()
    {
        return (string)$this->t('Meta settings');
    }
}