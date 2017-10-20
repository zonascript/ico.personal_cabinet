{extends "base/head.tpl"}
{block "wrapper"}
<section id="main_wrapper" class="off-canvas-wrapper">
    <div class="off-canvas position-left" id="offCanvasLeft" data-off-canvas data-transition="push">
        {insert "_parts/_menu_mobile.tpl"}
    </div>
    <div class="off-canvas-content" data-off-canvas-content>
        <section class="wrapper">
             <header itemscope itemtype="http://schema.org/WPHeader">
                <section class="logo_menu">
                    <div class="row">
                        <div class="column small-3 hide-for-medium">
                            <span data-toggle="offCanvasLeft" class="mobile_menu middle-inline-block hamburger"></span>
                        </div>
                        <div class="column small-6 medium-2 logo-container" >
                            <a href="/" class="logo">
                                <img src="" data-original="/static/frontend/dist/images/logo.svg" alt="s3stores" class="logo show-for-medium lazy lazy-img">
                                <img src="" data-original="/static/frontend/dist/images/logo_small.svg" alt="s3stores" class="logo hide-for-medium lazy lazy-img">
                            </a>
                        </div>
                        <div class="column show-for-medium medium-10 large-8 menu-container">
                            <ul class="no-bullet align-justify main-menu">
                                {get_menu code='main-menu'}
                            </ul>
                        </div>
                        <div class="column medium-2 show-for-xl"></div>
                    </div>
                </section>
            </header>

            <section id="content">
                    <section class="before-content">
                        {block "before-content"}
                            {render_breadcrumbs:raw template="base/_breadcrumbs.tpl"}
                            {render_flash:raw template='base/_flash.tpl'}
                        {/block}
                    </section>

                {block "content"}{/block}

                <section class="after-content">
                        {block "after-content"}{/block}
                </section>

            </section>
            <div class="push"></div>
        </section>


        <footer  itemscope itemtype="http://schema.org/WPFooter">
            <div class="row">
                <div class="column small-12">
                    <ul class="footer-menu no-bullet">
                        {get_menu code='footer-menu'}
                    </ul>
                </div>
            </div>
        </footer>
    </div>
</section>
{/block}