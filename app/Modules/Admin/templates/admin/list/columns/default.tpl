{if $column == '(string)'}
    {var $value = $.php.strval($item)}
{else}
    {var $value = $admin->getItemProperty($item, $column)}
{/if}

{$value}