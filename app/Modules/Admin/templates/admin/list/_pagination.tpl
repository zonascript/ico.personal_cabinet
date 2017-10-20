{extends 'pagination/default.tpl'}

{block 'prev'}
    <i class="icon-paginator_arrow_left"></i>
{/block}

{block 'next'}
    <i class="icon-paginator_arrow_right"></i>
{/block}

{block 'before_pagination_block'}
    <div class="select-block right">
        <select data-pagesize name="{$pager->getPageSizeKey()}" >
            {foreach $view->getPageSizes() as $pageSize }
                <option value="{$view->urlPageSize($pageSize)}" {if $view->getPageSize() == $pageSize }selected="selected"{/if}>
                    {$pageSize}
                </option>
            {/foreach}
        </select>
    </div>
{/block}