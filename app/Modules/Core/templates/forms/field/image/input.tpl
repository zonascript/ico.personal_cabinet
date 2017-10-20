<input type="{$type}" accept="{$field->getHtmlAccept()}" value="{$value}" id="{$id}" name="{$name}" {raw $html}>

{if $value}
    <br>
    <a class="current-image" style="background-image: url('{$field->getSizeImage()}')" href="{$field->getCurrentFileUrl()}"></a>
{/if}

{if $field->canClear()}
    <input value="{$field->getClearValue()}" id="{$id}_clear" type="checkbox" name="{$name}">
    <label for="{$id}_clear">{t 'Clean'}</label>
{/if}
