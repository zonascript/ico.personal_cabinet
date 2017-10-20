{if !$admin->sort && $config['order']!}
    {var $active = false}
    {var $newOrder = $config['order']}
    {var $desc = false}

    {if $order! && $newOrder == $order['clean']}
        {var $active = true}
        {var $desc = $order['desc']}
        {if !$order['desc']}
            {var $newOrder = '-' ~ $newOrder}
        {/if}
    {/if}

    <a href="{build_url data=['order' => $newOrder]}" class="title">
        <span class="text">
            {$config['title']}
        </span>
        <span class="order {if $active}active{/if}">
            {if $order && $order['desc'] && $active}
                <i class="icon-triangle rotate"></i>
            {else}
                <i class="icon-triangle"></i>
            {/if}
        </span>
    </a>

{else}
    <span class="title">
        <span class="text">
            {$config['title']}
        </span>
    </span>
{/if}