<?php

namespace Xcart\App\Form\Fields;

/**
 * Class CheckboxField
 * @package Mindy\Form
 */
class CheckboxField extends CharField
{
    public $inputTemplate = 'forms/field/checkbox/input.tpl';
    public $fieldTemplate = 'forms/field/checkbox/field.tpl';
    public $inputsListTemplate = 'forms/field/checkbox/inputs_list.tpl';
    public $fieldListTemplate = 'forms/field/checkbox/field_list.tpl';

    public $type = "checkbox";


    public function getHtmlName()
    {
        return $this->getPrefix() . '[' . $this->name . ']' . ($this->choices ? '[]' : '');
    }

    public function render()
    {
        if ($this->choices) {
            return $this->renderTemplate($this->fieldListTemplate, [
                'inputs' => $this->renderInput(),
                'errors' => $this->renderErrors(),
                'hint' => $this->renderHint()
            ]);
        }

        return parent::render();
    }

    public function renderInput()
    {
        if ($this->choices) {
            $inputs = [];
            $i = 0;
            $values = $this->value;

            if (!is_array($values)) {
                if ($values) {
                    $values = [$values];
                } else {
                    $values = [];
                }
            }

            $required = $this->required;

            foreach ($this->choices as $value => $labelStr) {
                $label = $this->renderLabel($this->getHtmlId() . '_' . $i, $labelStr);

                $input = $this->renderTemplate($this->inputTemplate, [
                    'field' => $this,
                    'html' => $this->buildAttributesInput(),
                    'id' => $this->getHtmlId(),
                    'value' => (is_array($values) && in_array($value, $values)),
                    'name' => $this->getHtmlName(),
                    'type' => $this->getType(),
                ]);


                $i++;
                $inputs[] = [
                    'input' => $input,
                    'label' => $label,
                ];
            }

            $this->required = $required;

            return $this->renderTemplate($this->inputsListTemplate, [
                'inputs' => $inputs,
            ]);
        }

        return parent::renderInput();
    }

    public function renderLabel($for = null, $label = null)
    {
        if ($for && $label) {
            return $this->renderTemplate($this->labelTemplate, [
                'field' => $this,
                'html' => $this->buildAttributesLabel(),
                'id' => $for,
                'label' => $label
            ]);
        }

        return parent::renderLabel();
    }

    public function setValue($value)
    {
        return parent::setValue((bool)$value);
    }
}
