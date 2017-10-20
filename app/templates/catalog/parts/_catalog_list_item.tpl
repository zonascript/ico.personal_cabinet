{*<div class="item product{if $item->isOutOfStock()} out_of_stock{/if}" data-product="{$item->productid}" itemscope itemtype="http://schema.org/Product" itemprop="itemListElement">*}
        {set $title = $item->getFrontendName()}

        <div class="image_container container">
            <a href="{$item->getAbsoluteUrl(true)}" title="{$title|escape}" class="link">

                {set $site = $item->sites->limit(1)->get()}
                {set $image = $item->images->limit(1)->get()}
                {if $image!}
                    {if $.isBot || $noLazy!}
                        <img src="//cdn.{$site->getBaseDomain()}{$image->getURL()}"
                             data-original="//cdn.{$site->getBaseDomain()}{$image->getURL()}"
                             width="{$image->image_x}"
                             height="{$image->image_y}"
                             alt="{$item.product|escape}"
                             {*class="lazy lazy-img"*}
                             itemscope itemprop="image">
                    {else}
                        <img data-original="//cdn.{$site->getBaseDomain()}{$image->getURL()}"
                             width="{$image->image_x}"
                             height="{$image->image_y}"
                             alt="{$title|escape}"
                             class="lazy lazy-img" itemprop="image">
                    {/if}
                {else}
                    
                    {*<img src="http://via.placeholder.com/200x200/efefef/a6a6a6/?text=No+image" alt="Image not available">*}
                    <div class="not-avail">
                        <span class="text">
                            Image not available
                        </span>
                    </div>
                {/if}

                {if $item->isNewProduct()}
                    <span class="splash splash-new show-for-large">New</span>
                {/if}

                {if $item->isSaleSticker()}
                    <span class="splash splash-sale show-for-large">Sale</span>
                {/if}

            </a>
        </div>
        <div class="info_container container">
            <h4 class="title " itemprop="name">
                <a href="{$item->getAbsoluteUrl(true)}" title="{$title|escape}">
                    {if $q!}
                        {raw $title|words_highlight:$q:"span.highlight"}
                    {else}
                        {raw $title}
                    {/if}
                </a>
            </h4>
            {*<div class="sku show-for-large">*}
            <div class="sku show-for-large">
                <span class="value">
                    SKU:&nbsp;<span class="style" itemprop="sku">{$item.productcode}</span>
                </span>
                {*<a data-tooltip class="has-tip right " title="What is SKU">?</a>*}
            </div>

            <div class="brand show-for-small">
                {if $brand_page!}
                    {set $brand = $brand_page}
                {else}
                    {set $brand = $item->cache()->brand}
                {/if}

                Brand:
                <a class="value" itemprop="brand"  href="{$brand->getAbsoluteUrl()}">
                    {$brand->brand}
                </a>
            </div>
            {if $item->getFrontendDescription()}
                {set $description = $item->getFrontendDescription()}

                <div class="description show-for-medium" >
                    <span itemprop="description">
                        {set $description = $description|br2nl|strip_tags|truncate:140:'...'|nl2space}

                        {if $q!}
                            {raw $description|words_highlight:$q:"span.highlight"}
                        {else}
                            {raw $description}
                        {/if}
                    </span>

                    <a href="{$item->getAbsoluteUrl()}" class="show-for-medium see">See details</a>
                </div>

                <noindex>
                    <div class="description show-for-small hide-for-medium">
                        {set $description = $description|br2nl|strip_tags|truncate:70:'...'|nl2space}

                        {if $q!}
                            {raw $description|words_highlight:$q:"span.highlight"}
                        {else}
                            {raw $description}
                        {/if}
                    </div>
                </noindex>
            {/if}



        </div>


        <div class="cart_price_container container">
            <div class="price_container" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                {if $item->list_price > $item->getFrontendPrice()}
                    <span class="old">
                        <span class="title">List Price:</span>
                        <span class="price">US$ {$item->list_price}</span>
                    </span>
                {/if}
                <span class="current">
                    <span class="title">Price:</span>
                    <span class="price">
                        <span itemprop="priceCurrency" content="USD">US$</span>
                        <span itemprop="price">{$item->getFrontendPrice()|number_format:2}</span>
                        {if $item->isOutOfStock()}
                            <link itemprop="availability" href="http://schema.org/OutOfStock" />
                        {else}
                            <link itemprop="availability" href="http://schema.org/InStock" />
                        {/if}

                    </span>
                </span>
            </div>

            <div class="overflow_container">
                {if !$item->isOutOfStock()}

                    <div class="info_container">
                        {if $item.lead_time_message|trim}
                            <div class="lead-time icon info">
                                {$item.lead_time_message}
                            </div>
                        {/if}

                        {if $item->mult_order_quantity == 'Y'}
                            <div class="multiply-quantity icon info padding">
                                Order in multiples of {$item->min_amount} items
                            </div>
                        {/if}

                        {if $item->min_amount >= $item->avail}
                            <div class="last-items icon info">
                                Order at least {$item->avail} items
                            </div>
                        {/if}
                    </div>

                {else}
                    <div class="out-of-stock">
                        <div class="title icon">
                            <i></i> Out of stock
                        </div>

                        {if $item.eta_date_mm_dd_yyyy && $item.eta_date_mm_dd_yyyy > time()}
                        <div class="eta-date">
                            Eta date: {$item.eta_date_mm_dd_yyyy|date_format:"%d %b %Y"}
                        </div>
                        {/if}
                        <div class="notify">

                        </div>
                    </div>
                {/if}
            </div>

        </div>
{*</div>*}