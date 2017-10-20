<?php

namespace Xcart\App\Form\Fields;

use Exception;
use Symfony\Component\Validator\Constraints\NotBlank;
use Xcart\App\Exceptions\InvalidConfigException;
use Xcart\App\Form\BaseForm;
use Xcart\App\Form\ModelForm;
use Xcart\App\Helpers\Accessors;
use Xcart\App\Traits\Configurator;
use Xcart\App\Traits\RenderTrait;
use Xcart\App\Validation\Interfaces\IValidateField;
use Xcart\App\Validation\RequiredValidator;
use Xcart\App\Validation\Traits\ValidateField;

/**
 * Class Field
 * @package Mindy\Form
 */
abstract class Field implements IValidateField
{
    use Accessors, Configurator, ValidateField, RenderTrait;

    /**
     * @var bool Технические аттрибуты для inline моделей
     */
    public $hidden = false;
    /**
     * @var bool Технические аттрибуты для inline моделей
     */
    public $delete = false;
    /**
     * @var mixed
     */
    public $value;
    /**
     * @var bool
     */
    public $required = false;
    /**
     * @var TODO
     */
    public $widget;
    /**
     * HTML attributes of input
     * @var array
     */
    protected $_attributes = [];
    /**
     * Class, that appends to all blocks if field is required
     * @var string
     */
    public $requiredClass = 'required';

    /**
     * Class, that appends to all blocks if field is invalid
     * @var string
     */
    public $invalidClass = 'invalid';

    /**
     * @var string
     */
    public $errorsClass = 'errors';

    /**
     * @var string
     */
    public $hintClass = 'hint';
    /**
     * @var string
     */
    public $labelClass = 'label';

    /**
     * @var string
     */
    public $inputTemplate = 'forms/field/default/input.tpl';

    /**
     * @var string
     */
    public $errorsTemplate = 'forms/field/default/errors.tpl';

    /**
     * @var string
     */
    public $labelTemplate = 'forms/field/default/label.tpl';

    /**
     * @var string
     */
    public $hintTemplate = 'forms/field/default/hint.tpl';

    /**
     * @var string
     */
    public $fieldTemplate = 'forms/field/default/field.tpl';
    /**
     * @var string
     */
    public $hint;
    /**
     * @var string
     */
    public $type = 'text';
    /**
     * @var array
     */
    public $choices = [];
    /**
     * @var
     */
    public $label;
    /**
     * @var
     */
    private $_name;
    /**
     * @var BaseForm
     */
    private $_form;
    /**
     * @var string
     */
    private $_validatorClass = '\Xcart\App\Form\Validator\Validator';
    /**
     * @var string
     */
    private $_prefix;

    public function init()
    {
        if (!($this->getForm() instanceof ModelForm) && $this->required) {
            $this->validators[] = new RequiredValidator();
        }
        foreach ($this->validators as $validator) {
            if (is_subclass_of($validator, $this->_validatorClass)) {
                /** @var $validator \Xcart\App\Validation\Validator */
                $validator->setName($this->label ? $this->label : $this->name);
            }
        }
    }

    public function __toString()
    {
        try {
            return (string)$this->render();
        } catch (Exception $e) {
            echo (string)$e;
            die();
        }
    }

    /**
     * @param BaseForm $form
     * @return $this
     */
    public function setForm(BaseForm $form)
    {
        $this->_form = $form;
        return $this;
    }

    /**
     * @return BaseForm|ModelForm
     */
    public function getForm()
    {
        return $this->_form;
    }

    public function setPrefix($value)
    {
        $this->_prefix = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        $prefix = $this->_prefix ?: $this->getForm()->getPrefix();

        if ($prefix) {
            return $prefix . '[' . $this->form->classNameShort() . '][' . $this->getId() . ']';
        } else {
            return $this->getForm()->classNameShort();
        }
    }

    public function getId()
    {
        return $this->form->getId();
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    public function setHtml($html)
    {
        if (!is_array($html)) {
            $html = [$html];
        }

        $this->setAttributes($html);
    }

    /**
     * Set HTML attributes
     * @param $attributes
     * @return $this
     * @throws InvalidConfigException
     */
    public function setAttributes($attributes)
    {
        if (!is_array($attributes)) {
            throw new InvalidConfigException('Attributes must be an array');
        }
        $this->_attributes = $attributes;
        return $this;
    }

    /**
     * Get HTML attributes
     * @return array
     */
    public function getAttributes()
    {
        $t = [];
        if ($this->required) {
            $t['required'] = null;
        }

        return array_replace($this->_attributes, $t);
    }

    public function getCommonClasses()
    {
        $classes = [];
        if ($this->required) {
            $classes[] = $this->requiredClass;
        }

        if ($this->hasErrors()) {
            $classes[] = $this->invalidClass;
        }

        return implode(' ', $classes);
    }

    /**
     * Get HTML attributes of input with required, invalid classes and other additional information
     * @return array
     */
    public function getAttributesInput()
    {
        $attributes = $this->getAttributes();
        $attributes = $this->extendAttribute($attributes, 'class', $this->getCommonClasses());
        return $attributes;
    }

    /**
     * @return array
     */
    public function getAttributesCommon()
    {
        return [
            'class' => $this->getCommonClasses()
        ];
    }

    public function getAttributesLabel()
    {
        $attributes = $this->getAttributesCommon();
        $attributes = $this->extendAttribute($attributes, 'class', $this->labelClass);
        return $attributes;
    }

    public function getAttributesErrors()
    {
        $attributes = $this->getAttributesCommon();
        $attributes = $this->extendAttribute($attributes, 'class', $this->errorsClass);
        return $attributes;
    }

    public function getAttributesHint()
    {
        $attributes = $this->getAttributesCommon();
        $attributes = $this->extendAttribute($attributes, 'class', $this->hintClass);
        return $attributes;
    }

    /**
     * Builds HTML attributes of input
     */
    public function buildAttributesInput()
    {
        $attributes = $this->getAttributesInput();
        return $this->buildAttributes($attributes);
    }

    /**
     * Builds HTML attributes of label
     */
    public function buildAttributesLabel()
    {
        $attributes = $this->getAttributesLabel();
        return $this->buildAttributes($attributes);
    }

    /**
     * Builds HTML attributes of errors
     */
    public function buildAttributesErrors()
    {
        $attributes = $this->getAttributesErrors();
        return $this->buildAttributes($attributes);
    }

    /**
     * Builds HTML attributes of hint
     */
    public function buildAttributesHint()
    {
        $attributes = $this->getAttributesHint();
        return $this->buildAttributes($attributes);
    }

    /**
     * Builds HTML attributes of errors to hint
     */
    public function buildLabelAttributes()
    {
        $attributes = $this->getAttributesLabel();
        return $this->buildAttributes($attributes);
    }

    public function extendAttribute($attributes, $name, $value, $glue = ' ')
    {
        if ($value) {
            $attribute = isset($attributes[$name]) ? $attributes[$name] : '';
            if ($attribute) {
                $attribute .= $glue;
            }
            $attributes[$name] = $attribute . $value;
        }
        return $attributes;
    }

    /**
     * @param $attributes
     * @return string
     * @throws InvalidConfigException
     */
    public function buildAttributes($attributes)
    {
        $builtAttributes = '';
        foreach ($attributes as $key => $value)
        {
            if (!is_scalar($value) && !is_null($value)) {
                throw new InvalidConfigException('Values of attributes must be a scalar types');
            }

            if ($key) {
                if (is_null($value)) {
                    $builtAttributes .= htmlspecialchars($key);
                }
                else {
                    if ($value === true || $value === false) {
                        $value = $value ? 'true' : 'false';
                    }

                    $builtAttributes .= htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
                }
            }
            else {
                $builtAttributes .= htmlspecialchars($value);
            }

            $builtAttributes .= ' ';
        }
        return $builtAttributes;
    }

    public function getType()
    {
        return $this->type ?: 'text';
    }

    /**
     * Render value for output
     * Date, datetime for example
     *
     * Value: 2012-09-23
     * Render value: 23.09.2012
     *
     * @return mixed
     */
    public function getRenderValue()
    {
        return $this->getValue();
    }

    public function renderInput()
    {
        return $this->renderTemplate($this->inputTemplate, [
            'field' => $this,
//            'html' => $this->getHtmlAttributes(),
            'html' => $this->buildAttributesInput(),
            'id' => $this->getHtmlId(),
            'value' => $this->getRenderValue(),
            'name' => $this->getHtmlName(),
            'type' => $this->getType(),
        ]);
    }

    public function renderErrors()
    {
        return $this->renderTemplate($this->errorsTemplate, [
            'field' => $this,
            'html' => $this->buildAttributesErrors(),
            'id' => $this->getHtmlId(),
            'errors' => $this->getErrors()
        ]);
    }

    public function renderLabel()
    {
        return $this->renderTemplate($this->labelTemplate, [
            'field' => $this,
            'html' => $this->buildAttributesLabel(),
            'id' => $this->getHtmlId(),
            'label' => $this->getLabel()
        ]);
    }

    public function renderHint()
    {
        return $this->renderTemplate($this->hintTemplate, [
            'field' => $this,
            'html' => $this->buildAttributesHint(),
            'id' => $this->getHtmlId(),
            'hint' => $this->hint
        ]);
    }

    public function render()
    {
        return $this->renderTemplate($this->fieldTemplate, [
            'label' => $this->renderLabel(),
            'input' => $this->renderInput(),
            'errors' => $this->renderErrors(),
            'hint' => $this->renderHint()
        ]);
    }

    public function getHtmlName()
    {
        return $this->getPrefix() . '[' . $this->name . ']';
    }

    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getLabel()
    {
        if ($this->label === false) {
            return '';
        }

        if ($this->label) {
            $label = $this->label;
        }
        else {
            if ($this->form instanceof ModelForm) {
                $instance = $this->form->getModel();
                if ($instance->hasField($this->name)) {
                    $verboseName = $instance->getField($this->name)->verboseName;
                    if ($verboseName) {
                        $label = $verboseName;
                    }
                }
            }

            if (!isset($label)) {
                $label = ucfirst($this->name);
            }
        }

        return $label;
    }

    /**
     * Format:
     * [
     *     "Main" => [
     *         "Name", "Url", "Content"
     *     ],
     *     "Extra" => [ ... ]
     * ]
     * @return array
     */
    public function getFieldSets()
    {
        return [];
    }

    public function getHtmlId()
    {
        return $this->getHtmlPrefix() . $this->getName();
    }

    public function getHtmlPrefix()
    {
        return rtrim(str_replace(['][', '[]', '[', ']'], '_', $this->getPrefix()), '_') . '_';
    }
}
