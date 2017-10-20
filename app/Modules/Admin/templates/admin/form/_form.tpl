{var $fieldsets = $admin->getFormFieldsets()}
{if $fieldsets}
    {foreach $fieldsets as $name => $fieldsNames}
        <fieldset>
            <div class="fieldset-title">
                {$name}
            </div>
            <div class="fields">
                {foreach $fieldsNames as $fieldName}
                    {var $field = $form->getField($fieldName)}
                    <div class="form-field {$fieldName}">
                        {raw $field->render()}
                    </div>
                {/foreach}
            </div>
        </fieldset>
    {/foreach}
{else}
    <fieldset>
        {var $fields = $form->getFieldsInit()}
        <div class="fields">
            {foreach $fields as $field}
                <div class="form-field {$field->name}">
                    {raw $field->render()}
                </div>
            {/foreach}
        </div>
    </fieldset>
{/if}

{set $inlines = $form->renderInlines()}
{if $inlines}
    <hr>
    <h2>{t 'Inline forms'}</h2>
    <br>
    {foreach $inlines as $name => $iforms}
        {foreach $iforms as $inline}
            <fieldset>
                <div class="fieldset-title">
                    {$name}
                </div>

                {var $fields = $inline->getFieldsInit()}
                <div class="fields">
                    {foreach $fields as $field}
                        <div class="form-field {$field->name}">
                            {raw $field->render()}
                        </div>
                    {/foreach}
                    </div>
                </fieldset>
        {/foreach}

    {/foreach}
{/if}