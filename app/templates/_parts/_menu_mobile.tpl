<div class="menu-title">
    <h3>Menu</h3>
</div>
<ul class="accordion" data-accordion data-allow-all-closed="true" data-multi-expand="true">
    {foreach $.get_menu_items('main-menu') as $item}
        <li class="accordion-item" {if $item.items }data-accordion-item{/if}>
            <a class="accordion-title" {if !$item.items}href="{$item.url}" {/if}>
                <div class="row">
                    <div class="columns small-12">
                        <span>{$item.name}</span>
                    </div>
                </div>
            </a>
            {*{if $item.items}*}
                {*<div class="accordion-content" data-tab-content>*}
                    {*{include "_parts/_submenu_mobile.tpl" items=$subcats}*}
                {*</div>*}
            {*{/if}*}
        </li>
    {/foreach}
</ul>