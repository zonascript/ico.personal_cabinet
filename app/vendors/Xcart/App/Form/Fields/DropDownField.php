<?php

namespace Xcart\App\Form\Fields;

use Closure;
use Xcart\App\Form\Form;
use Xcart\App\Form\ModelForm;
use Xcart\App\Orm\Fields\ForeignField;
use Xcart\App\Orm\Fields\HasManyField;
use Xcart\App\Orm\Fields\ManyToManyField;
use Xcart\App\Orm\Manager;
use Xcart\App\Orm\Model;

/**
 * Class DropDownField
 * @package Mindy\Form
 */
class DropDownField extends Field
{
    /**
     * @var array
     */
    public $choices = [];
    /**
     * Span tag needed because: http://stackoverflow.com/questions/23920990/firefox-30-is-not-hiding-select-box-arrows-anymore
     * @var string
     */
    public $inputTemplate = 'forms/field/dropdown/input.tpl';
    /**
     * @var bool
     */
    public $multiple = false;
    /**
     * @var string
     */
    public $empty = '';
    /**
     * @var array
     */
    public $disabled = [];

//    public function render()
//    {
//        $label = $this->renderLabel();
//        $input = $this->renderInput();
//
//        $hint = $this->hint ? $this->renderHint() : '';
//        $errors = $this->renderErrors();
//
//        $name = $this->getHtmlName();
//        return implode("\n", ["<input type='hidden' value='' name='{$name}' />", $label, $input, $hint, $errors]);
//    }
//
//    public function renderInput()
//    {
//        return strtr($this->template, [
//            '{type}' => $this->type,
//            '{id}' => $this->getHtmlId(),
//            '{input}' => $this->getInputHtml(),
//            '{name}' => $this->multiple ? $this->getHtmlName() . '[]' : $this->getHtmlName(),
//            '{html}' => $this->getHtmlAttributes()
//        ]);
//    }

    public function getChoises()
    {
        $out = '';
        $data = [];
        $selected = [];
        $choices = [];

        if ($this->choices) {
            $choices = $this->choices;
        }
        elseif ($this->getForm() instanceof ModelForm) {
            $choices = $this->getForm()->getInstance()->getField($this->name)->choices;
        }

        if ($choices) {
            if ($choices instanceof Closure) {
                $data = $choices->__invoke();
            }
            else {
                $data = $choices;
            }

            $value = $this->getValue();
            if ($value) {
                if ($value instanceof Manager) {
                    $selected = $value->valuesList(['pk'], true);
                } else if ($value instanceof Model) {
                    $selected[] = $value->pk;
                } else {
                    $selected[] = $value;
                }
            }

            if ($this->form instanceof ModelForm) {
                $model = $this->getForm()->getInstance();

                $field = $model->getField($this->name);
                if ($field->null && !$this->multiple) {
                    $data = ['' => ''] + $data;
                }

                if (is_a($field, ForeignField::className())) {
                    $from = $field->getFrom();
                    $to = $field->getTo();
                    $related = $model->{$from};
                    if ($related) {
                        $selected[] = $related;
                    }
                } else if (is_a($field, ManyToManyField::className())) {
                    $this->multiple = true;

                    $selectedTmp = $field->getManager()->all();
                    foreach ($selectedTmp as $model) {
                        $selected[] = $model->pk;
                    }
                } else {
                    $selected[] = $model->{$this->name};
                }
            } elseif ($this->form instanceof Form) {
                if (!is_array($this->value)) {
                    if ($this->value) {
                        $selected = [$this->value];
                    }
                } else {
                    $selected = $this->value;
                };
            }

            if ($this->multiple) {
                $this->html['multiple'] = 'multiple';
            }

            return $data;
//            return $this->valueToHtml($data, $selected);
        }

        if ($this->form instanceof ModelForm && $this->form->getModel()->hasField($this->name)) {
            $model = $this->form->getModel();
            $field = $model->getField($this->name);

            if (is_a($field, ManyToManyField::className())) {
                $this->multiple = true;

                $modelClass = $field->modelClass;
                $models = $modelClass::objects()->all();

                if ($value = $this->getValue()) {
                    if ($value instanceof Manager) {
                        $selectedTmp = $value->all();
                        foreach ($selectedTmp as $item) {
                            $selected[] = $item->pk;
                        }
                    } else {
                        $selected = is_array($value) ? $value : [$value];
                    }
                }

                $this->_attributes['multiple'] = 'multiple';

                foreach ($models as $item) {
                    $data[$item->pk] = (string)$item;
                }
            }
            elseif (is_a($field, HasManyField::className())) {
                $this->multiple = true;

                $modelClass = $field->modelClass;
                $models = $modelClass::objects()->all();

                $this->html['multiple'] = 'multiple';

                foreach ($models as $item) {
                    $data[$item->pk] = (string)$item;
                }
            }
            elseif (is_a($field, ForeignField::className())) {
                //@TODO: CHECK FOR CORRECTLY;
                /** @var ForeignField $from */
                $from = $field->getFrom();
                $to = $field->getTo();

                $modelClass = $field->modelClass;
                $qs = $modelClass::objects();
                if (get_class($model) == $modelClass && $model->getIsNewRecord() === false) {
                    $qs = $qs->exclude([$to => $model->{$to}]);
                }
                /* @var $modelClass \Xcart\App\Orm\Model */
                if (!$this->required) {
                    $data[''] = $this->empty;
                }
                if ($value = $this->getValue()) {
                    $selected[] = $value instanceof Model ? $value->{$to} : $value;
                }
                foreach ($qs->all() as $item) {
                    $data[$item->{$to}] = (string)$item;
                }
            }
            else {
                $data = parent::getValue();
            }
        }
        else {
            $data = parent::getValue();
        }

//        d($data);

        return $data;

//        if (is_array($data)) {
//            return $this->valueToHtml($data, $selected);
//        } else {
//            return $out;
//        }
    }
}
