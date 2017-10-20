{extends 'mail/base.tpl'}

{block 'content'}
    {foreach $form->getFieldsInit() as $field}
        {if $field->getValue()}
        <span style="font-weight: bold;">
            {$field->getLabel()}:
        </span>
        <span class="">
            {$field->getValue()|escape}
        </span>
        <br/>
        {/if}
    {/foreach}
{/block}