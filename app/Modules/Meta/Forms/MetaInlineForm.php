<?php

namespace Modules\Meta\Forms;

use Xcart\App\Form\Fields\CharField;
use Xcart\App\Form\Fields\HiddenField;
use Xcart\App\Form\Fields\TextAreaField;
use Xcart\App\Helpers\Meta as MetaGenerator;
use Modules\Meta\MetaModule;
use Modules\Meta\Models\Meta;
use Xcart\App\Form\ModelForm;
use Xcart\App\Main\Xcart;
use Xcart\App\Orm\Model;

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 08/05/14.05.2014 19:22
 */
class MetaInlineForm extends ModelForm
{
    public $max = 1;

    public $exclude = [];

    public function getName()
    {
        return MetaModule::t('Meta');
    }

    public function getFields()
    {
        return [
            'title' => [
                'class' => CharField::className(),
                'label' => MetaModule::t('Title'),
            ],
            'description' => [
                'class' => TextAreaField::className(),
                'label' => MetaModule::t('Description')
            ],
            'keywords' => [
                'class' => TextAreaField::className(),
                'label' => MetaModule::t('Keywords')
            ],
            'url' => [
                'class' => HiddenField::className()
            ],
            'site' => [
                'class' => HiddenField::className(),
                'value' => $this->getIsMultiSite() ? $this->getSitePk() : null
            ]
        ];
    }

    /**
     * @return null|int
     */
    protected function getSitePk()
    {
        $site = Xcart::app()->getModule('Sites')->getSite();
        return $site ? $site->code : null;
    }

    public function getModel()
    {
        return new Meta;
    }

    public function beforeSetAttributes($owner, array $attributes)
    {
        $model = $owner->getInstance();

        if ($model && method_exists($model, 'getAbsoluteUrl')) {
            if (isset($model->metaConfig)) {
                $metaConfig = $model->metaConfig;

                if (empty($attributes['title'])) {
                    $attributes['title'] = $model->{$metaConfig['title']};
                }
            }
        }

        return $attributes;
    }

    /**
     * @param $owner
     * @void
     */
    public function afterOwnerSave($owner)
    {
        if (method_exists($owner, 'getAbsoluteUrl')) {
            $metaConfig = $owner->metaConfig;
            $attributes = $this->getAttributes();
            if (empty($attributes['is_custom'])) {
                $this->setAttributes([
                    'title' => $owner->{$metaConfig['title']},
                    'keywords' => MetaGenerator::generateKeywords($owner->{$metaConfig['keywords']}),
                    'description' => MetaGenerator::generateDescription($owner->{$metaConfig['description']}),
                    'url' => $owner->getAbsoluteUrl()
                ]);
            } else {
                $this->setAttributes([
                    'url' => $owner->getAbsoluteUrl()
                ]);
            }
        }
    }

    /**
     * @return bool
     */
    protected function getIsMultiSite()
    {
        return Xcart::app()->getModule('Meta')->onSite;
    }

    /**
     * @param array $attributes
     * @return array|Model[]
     */
    public function getLinkModels(array $attributes)
    {
        $models = [];
        $model = array_shift($attributes);
        if (!$model->getIsNewRecord() && method_exists($model, 'getAbsoluteUrl')) {
            $models = Meta::objects()->filter([
                'url' => $model->getAbsoluteUrl(),
                'site_code' => $this->getIsMultiSite() ? $this->getSitePk() : null
            ]);
        }
        return $models;
    }
}
