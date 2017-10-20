<span class='select-holder'>
        <select name="{$name}" id="{$id}" {raw $html}>
            {if $field->empty}
                <option value="{$field->empty}">{$field->empty}</option>
            {/if}
            {foreach $field->getChoises() as $key => $name}
                <option value="{$key}" {if $key == $value}selected="selected"{/if} {if $value in list $field->disabled}disabled{/if}>{$name}</option>
            {/foreach}
        </select>
</span>