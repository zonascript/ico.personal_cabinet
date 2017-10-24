<?php

namespace Modules\Meta\Forms;

use Mindy\Base\Mindy;
use Mindy\Form\Fields\CharField;
use Mindy\Form\Fields\HiddenField;
use Mindy\Form\Fields\TextAreaField;
use Mindy\Form\ModelForm;
use Mindy\Helper\Meta as MetaGenerator;
use Modules\Meta\MetaModule;
use Modules\Meta\Models\Meta;

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
                'label' => MetaModule::t('Title')
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
        $site = Mindy::app()->getModule('Sites')->getSite();
        return $site ? $site->pk : null;
    }

    public function getModel()
    {
        return new Meta;
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
        return Mindy::app()->getModule('Meta')->onSite;
    }

    /**
     * @param array $attributes
     * @return array|\Mindy\Orm\Model[]
     */
    public function getLinkModels(array $attributes)
    {
        $models = [];
        $model = array_shift($attributes);
        if (!$model->getIsNewRecord() && method_exists($model, 'getAbsoluteUrl')) {
            $models = Meta::objects()->filter([
                'url' => $model->getAbsoluteUrl(),
                'site_id' => $this->getIsMultiSite() ? $this->getSitePk() : null
            ]);
        }
        return $models;
    }
}
