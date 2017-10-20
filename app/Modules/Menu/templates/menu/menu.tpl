{foreach $items  as $item }
{set $classes = [$item.class?$item.class:'']}
{if $item.items}
    {set $classes[] = 'has-subitems'}
{/if}
{if $item.url && $.request->getPath() == $item.url}
    {set $classes[] = 'active'}
{/if}

<li class="{$classes|implode:' '}">
    <a href="{$item.url ? $item.url : "#" }">
        {$item.name}
    </a>

    {if $item.items}
        <ul>
            {include "menu/menu.tpl" items=$item.items}
        </ul>
    {/if}
</li>
&ensp;
{/foreach}
