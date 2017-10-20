<tr class="{cycle ["", "TableSubHead"] index=$index}" data-brand-id="{$brand->brandid}">
    <td align="center">
        {if $parent}
            <input type="checkbox" name="brand_group[{$parent->brandid}][]" value="{$brand->brandid}">
        {/if}
    </td>
    <td>
        <a target="_blank" href="{$brand->getAdminUrl()}"><b>{$brand->brand}</b></a>
    </td>
    <td align="center">
        {foreach $brand->brand_storefront as $bsf}
            {set $sf = $bsf->storefront}
            {if $bsf->products_count}
                <a href="//{$sf->domain}{$brand->getUrl()}" target="_blank">{$sf->code}
                    - {$bsf->products_count}</a>
                <br/>
            {/if}
        {/foreach}
    </td>
    <td>
        {set $b_count = $brand->child_brands->count()}
        <div {if $b_count > 5}class="brands_overflow"{/if}>
        {foreach $brand->child_brands as $child}
            <a href="{$child->getAdminUrl()}" target="_blank"><b>{$child->brand}</b></a>
            <br/>
        {/foreach}
        </div>
        {if $b_count > 5}
            <a class="brands_more" href="#">...</a>
        {/if}
    </td>
    <td>
        {if !$parent}
            <a class="add-group" href="/">Add child</a>
        {/if}
    </td>
</tr>