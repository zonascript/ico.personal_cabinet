<?php

namespace Xcart\App\Form\Fields;

/**
 * Class RadioField
 * @package Mindy\Form
 */
class RadioField extends CharField
{
    public $template = "<input type='{type}' id='{id}' value='{value}' name='{name}'{html}/>";

    public $type = "radio";

    public function render()
    {
        $label = $this->renderLabel();
        $input = $this->renderInput();
        $hint = $this->hint ? $this->renderHint() : '';
        $errors = $this->renderErrors();

        if (empty($this->choices)) {
            return implode("\n", [$input, $label, $hint, $errors]);
        } else {
            return implode("\n", [$label, $input, $hint, $errors]);
        }
    }

    public function renderInput()
    {
        if (!empty($this->choices)) {
            $inputs = [];
            $i = 0;
            foreach ($this->choices as $value => $labelStr) {
                $label = strtr("<label for='{for}'>{label}</label>", [
                    '{for}' => $this->getHtmlId() . '_' . $i,
                    '{label}' => $labelStr
                ]);

                $checked = false;
                if (is_array($this->value)) {
                    foreach ($this->value as $v) {
                        if ($v == $value) {
                            $checked = true;
                        }
                    }
                } else {
                    if ($this->value == $value) {
                        $checked = true;
                    }
                }

                $input = $this->renderInputInternal($this->getHtmlId() . '_' . $i, $value,  ($checked ? " checked='checked'" : ''));
                $i++;
                $hint = $this->hint ? $this->renderHint() : '';
                $inputs[] = implode("\n", [
                    $input,
                    $label,
                    $hint
                ]);
            }
            return implode("\n", $inputs);
        } else {
            if ($this->value) {
                $this->html['checked'] = 'checked';
            }
            $input = $this->renderInputInternal($this->getHtmlId(), 1);
            return implode("\n", [
                "<input type='hidden' value='' name='" . $this->getHtmlName() . "' />",
                $input
            ]);
        }
    }

    protected function renderInputInternal($id, $value, $html = '')
    {
        return strtr($this->template, [
            '{type}' => $this->type,
            '{id}' => $id,
            '{name}' => $this->getHtmlName(),
            '{value}' => $value,
            '{html}' => $this->buildAttributesInput() . $html
        ]);
    }
}
