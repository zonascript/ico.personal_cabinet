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
 * @date 12/06/14.06.2014 19:43
 */

namespace Modules\Sites;


use Mindy\Base\Module;
use Modules\Sites\Models\Site;

class SitesModule extends Module
{
    public $modelClass = 'Modules\Sites\Models\Site';
    public $formClass = 'Modules\Sites\Forms\SiteForm';

    /**
     * @var \Modules\Sites\Models\Site
     */
    private $_site;

    public function setSite(Site $model)
    {
        $this->_site = $model;
    }

    public function getSite()
    {
        return $this->_site;
    }

    public function getMenu()
    {
        return [
            'name' => $this->getName(),
            'items' => [
                [
                    'name' => self::t('Sites'),
                    'adminClass' => 'SiteAdmin',
                ]
            ]
        ];
    }
}
