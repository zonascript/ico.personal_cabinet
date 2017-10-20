<?php

namespace Xcart\App\Form\Fields;

use Mindy\Form\ModelForm;
use Mindy\Locale\Translate;

/**
 * Class MarkdownField
 * @package Mindy\Form
 */
class MarkdownField extends TextAreaField
{
    public $html = [
        'rows' => 10
    ];

    protected function getRenderedLabel()
    {
        if ($this->label === false) {
            return '';
        }

        if ($this->label) {
            $label = $this->label;
        } else {
            if ($this->form instanceof ModelForm) {
                $instance = $this->form->getInstance();
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

    public function render()
    {
        $t = Translate::getInstance();

        $label = $this->getRenderedLabel();
        $input = $this->renderInput();

        $hint = $this->hint ? $this->renderHint() : '';
        $errors = $this->renderErrors();
        $out = implode("\n", [$input, $hint, $errors]);

        $html = <<<HTML
<dl class="tabs" data-tab>
    <dd class="active"><a href="#editor" onclick="$('#{$this->getHtmlId()}').focus();">{$label}</a></dd>
    <dd><a href="#preview" onclick="preview();">{$t->t('form', 'Preview')}</a></dd>
</dl>
<div class="tabs-content">
    <div class="content active" id="editor">{$out}</div>
    <div class="content" id="preview"></div>
</div>
HTML;

        $js = <<<JS
<script type="text/javascript">
    var md = new Remarkable({
        breaks: false,
        html: false,
        typographer: false,
        highlight: function (str, lang) {
            if (lang && hljs.getLanguage(lang)) {
                try {
                    return hljs.highlight(lang, str).value;
                } catch (err) {}
            }

            try {
                return hljs.highlightAuto(str).value;
            } catch (err) {}

            return '';
        }
    });
    var preview = function() {
        var source = $('#{$this->getHtmlId()}').val();
        $('#preview').html(md.render(source));
    };
</script>
JS;
        return $html . $js;
    }
}
