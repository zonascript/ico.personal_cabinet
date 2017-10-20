{extends 'base.tpl'}

{block 'preloader'}{/block}

{block "content"}
    <section class="page pages receipt-confirmation bg-dark-blue" style="background-image: url(/static/frontend/dist/images/page-bg/dark-page-bg-origin.jpg)">

        {if $model}
            <div class="vertical-middle">
                <div class="row w1280 align-middle">
                    <div class="column small-12 medium-6 medium-order-1 align-self-middle">
                        <h1>PO # {$model->order_prefix}{$model->orderid} Receipt Confirmation</h1>
                        <p>Thank you for confirming that you have received our PO. <br>Have a lovely day!</p>
                    </div>
                    <div class="column show-for-medium medium-2 vertical-middle">
                        &nbsp;
                    </div>
                    <div class="column small-12 medium-3 freddy">
                        <img alt="Freddy" src="/static/frontend/dist/images/freddy.png">
                    </div>

                </div>
            </div>
        {else}
            <div class="onlyFreddy vertical-middle">
                <div class="row w1280 align-middle align-center">
                    <div class="column show-for-medium medium-2 vertical-middle">
                        &nbsp;
                    </div>
                    <div class="column small-12 medium-3 freddy text-center">
                        <img alt="Freddy" src="/static/frontend/dist/images/freddy.png">
                    </div>
                    <div class="column show-for-medium medium-2 vertical-middle">
                        &nbsp;
                    </div>
                </div>
            </div>
        {/if}
    </section>
{/block}