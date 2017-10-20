{set $firstPage = 1}
{set $lastPage = $pager->getPagesCount()}

{set $prevPage = $view->getUrl($pager->getPage() - 1)}
{set $nextPage = $view->getUrl($pager->getPage() + 1)}

<div class="pagination-wrapper" data-id="{$pager->getPageKey()}">
    {if $firstPage != $lastPage}
        {block 'before_pagination_block'}{/block}

        <div class="pagination-block">
            {set $activeSize = 2}
            {set $activeFrame = $activeSize * 2 + 1}

            {set $page = $pager->getPage()}
            {set $firstPage = 1}
            {set $lastPage = $pager->getPagesCount()}

            {set $firstFramePage = $page - $activeSize}
            {if $firstFramePage > $lastPage - $activeSize * 2}
                {set $firstFramePage = $lastPage - $activeSize * 2}
            {/if}
            {if $firstFramePage < $firstPage}
                {set $firstFramePage = $firstPage}
            {/if}

            {set $lastFramePage = $firstFramePage + $activeSize * 2}
            {if $lastFramePage > $lastPage}
                {set $lastFramePage = $lastPage}
            {/if}

            <ul class="pagination-list">
                {if $prevPage}
                    <li class="prev">
                        <a href="{$view->getUrl($prevPage)}">
                            {block 'prev'}
                                &larr;
                            {/block}
                        </a>
                    </li>
                {/if}

                <li class="first {if $page == $firstPage}active{/if}">
                    <a href="{$view->getUrl($firstPage)}">
                        {$firstPage}
                    </a>
                </li>

                {if $firstFramePage > $firstPage + 1}
                    <li class="dots">
                        {block 'dots'}
                            ...
                        {/block}
                    </li>
                {/if}

                {foreach $firstFramePage..$lastFramePage as $item}
                    {if $item != $firstPage and $item != $lastPage}
                        <li class="page {if $page == $item}active{/if}">
                            <a href="{$view->getUrl($item)}">
                                {$item}
                            </a>
                        </li>
                    {/if}
                {/foreach}

                {if $lastFramePage < $lastPage - 1}
                    <li class="dots">
                        {block 'dots'}
                            ...
                        {/block}
                    </li>
                {/if}

                {if $lastPage > $firstPage}
                    <li class="last {if $page == $lastPage}active{/if}">
                        <a href="{$view->getUrl($lastPage)}">
                            {$lastPage}
                        </a>
                    </li>
                {/if}

                {if $nextPage}
                    <li class="next">
                        <a href="{$view->getUrl($nextPage)}">
                            {block 'next'}
                                &rarr;
                            {/block}
                        </a>
                    </li>
                {/if}
            </ul>
        </div>

        {block 'after_pagination_block'}{/block}
    {/if}
</div>