{extends $.request->getIsAjax() != false ? 'ajax.tpl' : 'base.tpl'}

{block "content"}
    <section class="page index bg-dark-blue" data-background="/static/frontend/dist/images/page-bg/dark-page-bg-origin.jpg">
        <div class="row">
            <div class="column small-12">

                <div class="stores-list list" >
                    {if $storefronts}
                    {foreach $storefronts as $item }
                        <div class="item">
                            <a href="https://{$item->storefront->domain}" class="item-wrapper" target="_blank">
                                <div class="image-wrapper cont">
                                    <img data-original="{$item->list_image->sizeUrl('q85')}" class="lazy lazy-img background">
                                    <img data-original="{$item->list_icon->getUrl()}" alt="{$item->getName()}" class="lazy lazy-img logo">
                                </div>
                                <div class="content-wrapper cont">
                                    <h2>{$item->getName()}</h2>
                                    <div class="description dot">{$item->description}</div>
                                </div>
                            </a>
                        </div>
                    {/foreach}
                    {/if}
                </div>

            </div>
        </div>
    </section>
{/block}