{if $breadcrumbs|length > 0}
    <div class="breadcrumbs-block">
        <ul class="breadcrumbs-list">
            <li>
                <a href="{url route="admin:index"}">
                    Главная
                </a>
            </li>

            {foreach $breadcrumbs as $item}
                <li class="delimiter">
                    &rarr;
                </li>

                <li>
                    {if $item['url']}
                        <a href="{$item['url']}">
                            {$item['name']}
                        </a>
                    {else}
                        {$item['name']}
                    {/if}
                </li>
            {/foreach}
        </ul>
    </div>
{/if}