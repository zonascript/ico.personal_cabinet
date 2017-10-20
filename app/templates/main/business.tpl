{extends 'base.tpl'}

{*{block 'wrapper-classes'}bg-dark-blue{/block}*}

{block "content"}
    <section class="page pages business-requests">

                <ul class="tabs" data-responsive-accordion-tabs="tabs small-accordion medium-tabs" id="example-tabs" data-allow-all-closed="true">
                    <li class="tabs-title {if $initial || $investorForm->getErrors()}is-active{/if}">
                      <a href="#investor" aria-selected="{if $initial || $investorForm->getErrors()}true{else}false{/if}">Investor Inquiries</a>
                    </li>
                    <li class="tabs-title {if $sellingForm->getErrors()}is-active{/if}">
                      <a href="#selling" aria-selected="{if $sellingForm->getErrors()}true{else}false{/if}">Sell with us</a>
                    </li>
                    <li class="tabs-title {if $mediaForm->getErrors()}is-active{/if}">
                      <a href="#media" aria-selected="{if $mediaForm->getErrors()}true{else}false{/if}">Media Inquiries</a>
                    </li>
                    <li class="tabs-title {if $partnersForm->getErrors()}is-active{/if}">
                      <a href="#partnership" aria-selected="{if $partnersForm->getErrors()}true{else}false{/if}">Partnership Proposals</a>
                    </li>
                </ul>

                <div class="tabs-content" data-tabs-content="example-tabs">
                    <div class="tabs-panel {if $initial || $investorForm->getErrors()}is-active{/if}" id="investor">
                        <div class="row">
                            <div class="columns small-12 text-center">
                                <h2 class="text-center">Investor Inquiries</h2>
                                <p>
                                    S3 Stores Inc. is a privately-held company. If you would like information about investing in S3 Stores, Inc., <br>
                                    please email your proposal and any other pertinent information to <a href="mailto:investors@s3stores.com?subject=Investor Inquiries">investors@s3stores.com.</a>
                                </p>
                            </div>
                        </div>

                        <form action="{url 'main:business'}" method="post">
                            {$investorForm->render('forms/frontend.tpl')}

                            <div class="row">
                                <div class="columns small-12 text-center">
                                    <button class="submit">{t 'Submit'}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="tabs-panel {if $sellingForm->getErrors()}is-active{/if}" id="selling">
                        <div class="row">
                            <div class="columns small-12 text-center">
                                <h2 class="text-center">Sell with us</h2>
                            </div>
                        </div>

                        <form action="{url 'main:business'}" method="post">
                        {$sellingForm->render('forms/frontend.tpl')}

                        <div class="row">
                                <div class="columns small-12 text-center">
                                    <button class="submit">{t 'Submit'}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="tabs-panel {if $mediaForm->getErrors()}is-active{/if}" id="media">
                        <div class="row">
                            <div class="columns small-12 text-center">
                                <h2 class="text-center">Media Inquiries</h2>
                                <p>
                                    For media inquiries, contact us at <a href="mailto:press@s3stores.com?subject=Media Inquiries">press@s3stores.com</a>
                                </p>
                            </div>
                        </div>
                        <form action="{url 'main:business'}" method="post">
                            {$mediaForm->render('forms/frontend.tpl')}

                            <div class="row">
                                <div class="columns small-12 text-center">
                                    <button class="submit">{t 'Submit'}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="tabs-panel {if $partnersForm->getErrors()}is-active{/if}" id="partnership">
                        <div class="row">
                            <div class="columns small-12 text-center">
                                <h2 class="text-center">Partnership Proposals</h2>
                            </div>
                        </div>

                        <form action="{url 'main:business'}" method="post">
                            {$partnersForm->render('forms/frontend.tpl')}

                            <div class="row">
                                <div class="columns small-12 text-center">
                                    <button class="submit">{t 'Submit'}</button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>

    </section>
{/block}

{block 'js'}
    {parent}
    {ignore}
    <script>
        (function(){
            $(document).on('down.zf.accordion', function(){

                $("body, html").animate({
                    scrollTop: $('.is-active .accordion-content').offset().top
                }, 600);
            })
        })();
    </script>
    {/ignore}
{/block}