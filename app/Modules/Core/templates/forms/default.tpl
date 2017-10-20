{foreach $fields as $name}
    {set $field = $form->getField($name)}
    <div class="form-field {$name}">
        {raw $field->render()}
    </div>
{/foreach}