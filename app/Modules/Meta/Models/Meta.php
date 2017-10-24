<?php

namespace Modules\Meta\Models;

use Mindy\Base\Mindy;
use Mindy\Orm\Fields\BooleanField;
use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Model;
use Modules\Meta\MetaModule;
use Modules\Sites\SitesModule;

class Meta extends Model
{
    public static function getFields()
    {
        $fields = [
            'is_custom' => [
                'class' => BooleanField::className(),
                'verboseName' => MetaModule::t('Is custom'),
                'helpText' => MetaModule::t('If "Set manually" field was not set, data will be generated automatically')
            ],
            'title' => [
                'class' => CharField::className(),
                'length' => 200,
                'verboseName' => MetaModule::t('Title')
            ],
            'keywords' => [
                'class' => CharField::className(),
                'length' => 200,
                'verboseName' => MetaModule::t('Keywords'),
                'null' => true
            ],
            'description' => [
                'class' => CharField::className(),
                'length' => 200,
                'verboseName' => MetaModule::t('Description'),
                'null' => true
            ],
            'url' => [
                'class' => CharField::className(),
                'verboseName' => MetaModule::t('Url'),
                'null' => true
            ],
        ];

        $onSite = Mindy::app()->getModule('Meta')->onSite;
        if ($onSite) {
            $fields['site'] = [
                'class' => ForeignField::className(),
                'modelClass' => Mindy::app()->getModule('Sites')->modelClass,
                'verboseName' => SitesModule::t('Site'),
                'required' => true,
                'null' => true
            ];
        }

        return $fields;
    }

    public function __toString()
    {
        return (string)$this->title;
    }

    public function getAbsoluteUrl()
    {
        return $this->url;
    }

    public function beforeSave($owner, $isNew)
    {
        $onSite = Mindy::app()->getModule('Meta')->onSite;
        if ($onSite) {
            $sitesModule = Mindy::app()->getModule('Sites');
            if (($isNew || empty($owner->site)) && $sitesModule) {
                $owner->site = $sitesModule->getSite();
            }
        }
    }

    public static function objectsManager($instance = null)
    {
        $className = get_called_class();
        return new MetaManager($instance ? $instance : new $className);
    }
}
