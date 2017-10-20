<?php

namespace Modules\Meta\Models;

use Modules\Meta\MetaModule;
use Modules\Sites\SitesModule;
use Xcart\App\Orm\Fields\CharField;
use Xcart\App\Main\Xcart;
use Xcart\App\Orm\Fields\BooleanField;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Model;

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

        $onSite = Xcart::app()->getModule('Meta')->onSite;
        if ($onSite) {
            $fields['site'] = [
                'field' => 'site_code',
                'class' => ForeignField::className(),
                'modelClass' => Xcart::app()->getModule('Sites')->modelClass,
                'verboseName' => SitesModule::t('Site'),
                'link' => ['site_code' => 'code'],
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
        $onSite = Xcart::app()->getModule('Meta')->onSite;
        if ($onSite) {
            $sitesModule = Xcart::app()->getModule('Sites');
            if (($isNew || empty($owner->site)) && $sitesModule) {
                $owner->site = $sitesModule->getSite()->code;
            }
        }
    }

    public static function objectsManager($instance = null)
    {
        $className = get_called_class();
        /** @var Model $instance */
        $instance = ($instance ?: new $className);
        return new MetaManager($instance, $instance->getConnection());
    }
}
