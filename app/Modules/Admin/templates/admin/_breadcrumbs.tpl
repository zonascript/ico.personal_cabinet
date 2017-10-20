{if $breadcrumbs|length > 0}
    <div class="breadcrumbs-block">
        <ul class="breadcrumbs-list">
            <li>
                <a href="{url route="admin:index"}">
                    {t 'Home'}
                </a>
            </li>

            {foreach $breadcrumbs as $item}
                <li class="delimiter">
                    /
                </li>

                <li>
                    <a href="{$item['url']}">
                        {$item['name']}
                    </a>
                </li>
            {/foreach}
        </ul>
    </div>
{/if}