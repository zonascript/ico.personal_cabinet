{if $items|count}
<ul class="no-bullet">
    {foreach $items as $item}
        {if $item->getFromQueryAttribute('pcount')}
        <li>
            <a href="{$item->getAbsoluteUrl()}">
                {$item->category}
                <span class="count">
                    ({$item->getFromQueryAttribute('pcount')})
                </span>
            </a>
        </li>
        {/if}
    {/foreach}
</ul>
{/if}