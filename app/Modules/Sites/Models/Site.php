<?php
/**
 * 
 *
 * All rights reserved.
 * 
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 12/06/14.06.2014 19:48
 */

namespace Modules\Sites\Models;

use Mindy\Base\Mindy;
use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\TextField;
use Mindy\Orm\Model;
use Modules\Sites\SitesModule;

class Site extends Model
{
    public static function getFields()
    {
        return [
            'domain' => [
                'class' => CharField::className(),
                'verboseName' => SitesModule::t('Domain')
            ],
            'name' => [
                'class' => CharField::className(),
                'verboseName' => SitesModule::t('Name')
            ],
            'robots' => [
                'class' => TextField::className(),
                'verboseName' => 'robots.txt',
                'null' => true
            ],
        ];
    }

    public function __toString()
    {
        return (string) $this->name;
    }

    public function getAbsoluteUrl()
    {
        return Mindy::app()->request->http->getSchema() . '://' . $this->domain;
    }
}
