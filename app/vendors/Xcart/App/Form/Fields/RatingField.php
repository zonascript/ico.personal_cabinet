<?php


namespace Xcart\App\Form\Fields;

use Xcart\App\Form\ModelForm;
use Xcart\App\Helpers\JavaScript;
use Xcart\App\Helpers\JavaScriptExpression;

/**
 * Class RatingField
 * @package Mindy\Form
 */
class RatingField extends HiddenField
{
    public $options = [];

    public function renderLabel()
    {
        if ($this->label === false) {
            return '';
        }

        if ($this->label) {
            $label = $this->label;
        } else {
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

        return strtr("<label for='{for}'>{label}</label>", [
            '{for}' => $this->getHtmlId(),
            '{label}' => $label
        ]);
    }

    public function render()
    {
        $jsOptions = JavaScript::encode(array_merge([
            'starType' => 'i',
            'numberMax' => 5,
            'score' => $this->getValue(),
            'click' => new JavaScriptExpression('function(score, evt) {
                $("#' . $this->getHtmlId() . '").val(score);
            }')
        ], $this->options));
        $js = "<div id='{$this->getHtmlId()}_rating' class='rating-input'></div><script type='text/javascript'>$('#{$this->getHtmlId()}_rating').raty({$jsOptions});</script>";
        return parent::render() . $js;
    }
}
