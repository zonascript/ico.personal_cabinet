<select name="{$pager->getPageSizeKey()}" onchange="window.location=this.value">
    {foreach $view->getPageSizes() as $pageSize }
        <option value="{$view->urlPageSize($pageSize)}" {if $view->getPageSize() == $pageSize }selected="selected"{/if}>
            {$pageSize}
        </option>
    {/foreach}
</select>
