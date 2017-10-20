<div class="info-field">
    <div class="row w1280 align-right">
        <div class="column medium-6">
            {t 'The fields marked with <span class="color-red">*</span> are mandatory.'}
        </div>
    </div>
</div>


{foreach $fields as $name}
    {set $field = $form->getField($name)}
    <div class="form-field {$name}">
        <div class="row w1280">
            <div class="columns small-12 medium-6 title-column">
                <p class="label">
                    {$field->renderLabel()}

                    {if $field->required}
                        <span class="color-red">*</span>
                    {else}
                        <span class="optional">{t '(optional)'}</span>
                    {/if}
                </p>

                {$field->renderHint()}

            </div>
            <div class="columns small-12 medium-6 input-column">
                    {$field->renderInput()}
            </div>
        </div>
    </div>
{/foreach}