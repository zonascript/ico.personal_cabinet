<div class="form-model-field-block">
    <div class="row">
        <div class="columns large-4">
            <label for="m_{$field}">{$model->getField($field)->getVerboseName()}:</label>
        </div>

        <div class="columns large-6">
            {if $type == 'textarea'}
                <textarea name="{$model->classNameShort()}[{$field}]" id="m_{$field}" {if $model->getField($field)->isRequired()}required{/if}>{$model.$field}</textarea>
            {elseif $type == 'select'}
                <select name="{$model->classNameShort()}[{$field}]" id="m_{$field}" {if $multiple}multiple{/if} class="{$class}">
                    <option value=""></option>

                    {if !$choises && $model->getField($field)->choices}
                        {set $choises = $model->getField($field)->choices}
                    {/if}

                    {if !$selected}
                        {set $selected = $model->getField($field)->getValue()}
                    {/if}

                    {foreach $choises as $key => $value}
                        {if is_object($value)}
                            {if is_array($selected)}
                                <option value="{$value.pk}" {if $value.pk|in:$selected }selected{/if}>{$value}</option>
                            {else}
                                <option value="{$value.pk}" {if $selected == $value.pk }selected{/if}>{$value}</option>
                            {/if}
                        {else}
                            {if is_array($selected)}
                                <option value="{$key}" {if $key|in:$selected }selected{/if}>{$value}</option>
                            {else}
                                <option value="{$key}" {if $selected == $key }selected{/if}>{$value}</option>
                            {/if}
                        {/if}
                    {/foreach}
                </select>
            {elseif $type == 'checkbox'}
                <input type="hidden" value="0" name="{$model->classNameShort()}[{$field}]">
                <input type="{if $type}{$type}{else}text{/if}"
                       name="{$model->classNameShort()}[{$field}]"
                       id="m_{$field}"
                       value="1"
                       class="{$class}"
                       {if $model->getField($field)->isRequired()}required{/if}
                       {if $model.$field || ($model->getIsNewRecord() && $model->getField($field)->getValue())}checked{/if}
                >
            {else}
                <input type="{if $type}{$type}{else}text{/if}"
                       name="{$model->classNameShort()}[{$field}]"
                       id="m_{$field}" value="{$model.$field}"
                       class="{$class}"
                       {if $model->getField($field)->isRequired()}required{/if}
                >
            {/if}
        </div>

    </div>
</div>