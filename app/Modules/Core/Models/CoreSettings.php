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
 * @date 10/03/16 09:12
 */
namespace Modules\Core\Models;

use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\EmailField;
use Modules\Core\CoreModule;
use Modules\Core\Models\SettingsModel;

class CoreSettings extends SettingsModel
{
    public static function getFields() 
    {
        return [
            'sitename' => [
                'class' => CharField::className(),
                'verboseName' => self::t("Sitename"),
                'null' => true
            ],
            'email_owner' => [
                'class' => EmailField::className(),
                'verboseName' => self::t("Owner email"),
                'null' => true
            ],
        ];
    }
    
    public function __toString()
    {
        return (string) CoreModule::t('Core settings');
    }
}