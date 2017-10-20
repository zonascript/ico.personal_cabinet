<section class="pager-container">
    <section class="row">
        <section class="total column large-2">Total: {$view->getTotal()}</section>

        <ul class="pager column large-8 no-bullet">
            {if $view->getPagesCount() > 1}

                {if $view->hasPrevPage()}
                    <li class="first">
                            <a href="{$view->getUrl(1)}"><<</a>
                    </li>
                {/if}

                <li class="prev">
                    {if $view->hasPrevPage()}
                        <a href="{$view->getUrl($pager->getPage() - 1)}">&larr;</a>
                    {else}
                        <span class="prev">&larr;</span>
                    {/if}
                </li>

                {if $view->hasPrevPage()}
                    {foreach $view->iterPrevPage() as $page }
                        <li>
                            <a href="{$view->getUrl($page)}">{$page}</a>
                        </li>
                    {/foreach}
                {/if}

                <li>
                    <span class="current">{$pager->getPage()}</span>
                </li>

                {if $view->hasNextPage()}
                    {foreach $view->iterNextPage() as $page}
                        <li>
                            <a href="{$view->getUrl($page)}">{$page}</a>
                        </li>
                    {/foreach}
                {/if}

                <li class="next">
                    {if $view->hasNextPage()}
                        <a href="{$view->getUrl($view->getPage() + 1)}">&rarr;</a>
                    {else}
                        <span class="next">&rarr;</span>
                    {/if}
                </li>

                {if $view->hasNextPage()}
                    <li class="last">
                        <a href="{$view->getUrl($pager->getPagesCount())}">>></a>
                    </li>
                {/if}
            {else}
                &nbsp;
            {/if}
        </ul>

        <section class="page-size column large-2">
            {insert "core/pager/sizes.tpl"}
        </section>
    </section>
</section>