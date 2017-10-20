{extends 'base.tpl'}

{block "content"}
    <section class="page shop bg-dark-blue" data-background="/static/frontend/dist/images/page-bg/dark-page-bg-origin.jpg">

        <section class="search-block-wrapper">
                <div class="search-block">
                    <form action="{url 'main:shop'}" method="post">
                        <input type="search"
                               name="q"
                               value="{$q}"
                               placeholder="{t 'Product search'}"
                               data-products-pp="{$products_per_page}"
                               data-search-url="{url 'main:api:search'}"
                               data-suggestion-url="{url 'catalog:api:search:suggestion'}"
                        />

                    </form>
                </div>
        </section>

    </section>
{/block}