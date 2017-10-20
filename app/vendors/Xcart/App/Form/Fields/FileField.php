<?php

namespace Xcart\App\Form\Fields;

use League\Flysystem\File;
use Xcart\App\Form\ModelForm;
use Xcart\App\Form\PrepareData;
use Xcart\App\Validation\FileValidator;

/**
 * Class FileField
 * @package Mindy\Form
 */
class FileField extends Field
{
    /**
     * @var string
     */
    public $type = 'file';
    /**
     * @var bool
     */
    public $cleanValue = '1';
    /**
     * @var string
     */
    public $inputTemplate = 'forms/field/file/input.tpl';
    /**
     * @var null
     */
    public $oldValue = null;
    /**
     * List of allowed file types
     * @var array|null
     */
    public $types = [];
    /**
     * @var null|int maximum file size or null for unlimited. Default value 2 mb.
     */
    public $maxSize = 2097152;

    public function init()
    {
        parent::init();

        $hasFileValidator = false;
        foreach ($this->validators as $validator) {
            if ($validator instanceof FileValidator) {
                $hasFileValidator = true;
                break;
            }
        }

        if ($hasFileValidator === false) {
            $fileValidator = new FileValidator($this->required, $this->types, $this->maxSize);
            $fileValidator->setName($this->name);
            $this->validators = array_merge([
                $fileValidator
            ], $this->validators);
        }
    }

    public function getHtmlAccept()
    {
        if (!empty($this->types)) {
            return implode(',', $this->types);
        } else {
            return '*/*';
        }
    }

    public function getCurrentFileUrl()
    {
        $value = $this->getValue();

        if ($value instanceof File) {
            $fs = $value->getFilesystem();
            return $fs->getAdapter()->getUrl($value->getPath());
        }

        if (is_string($value)) {
            if ($this->getForm() instanceof ModelForm) {
                /** @var ModelForm $form */
                $form = $this->getForm();
                /** @var \Xcart\App\Orm\Fields\FileField $field */
                $field = $form->getInstance()->getField($this->getName());
                return $field->getFilesystem()->getAdapter()->getUrl($value);
            }

            return $value;

        }

        return null;
    }


    public function canClear()
    {
        $canClear = true;

        if ($this->required) {
            $canClear = false;
        }

        if ($this->getForm() instanceof ModelForm) {
            /** @var ModelForm $form */
            $form = $this->getForm();
            if ($form->getInstance()->getIsNewRecord() || $this->getValue() == null) {
                $canClear = false;
            }
        }
        return $canClear;
    }


    public function setValue($value)
    {
        if ( PrepareData::checkFilesStruct($value) ) {
            if ($value['error'] == UPLOAD_ERR_NO_FILE) {
                return $this;
            }
        }

        if ($value) {
            if ($value instanceof \Xcart\App\Orm\Fields\FileField) {
                $value = $value->path();
            }

            $this->setOldValue();
            parent::setValue($value);
        }

        if ($this->canClear() && is_string($value) && $value == $this->getClearValue()) {
            $this->setClearValue();
        }

        return $this;
    }

    public function getOldValue()
    {
        return $this->oldValue;
    }

    public function setOldValue()
    {
        if (is_string($this->value) || !$this->oldValue) {
            $this->oldValue = $this->value;
        }
    }

    public function setClearValue()
    {
        $this->setOldValue();
        parent::setValue(null);
    }

    public function getClearValue()
    {
        return $this->cleanValue;
    }
}