{if isset($breadcrumbs) && ($breadcrumbs|instanceof:'Xcart\App\Components\Breadcrumbs' || is_array($breadcrumbs))}

    {if $breadcrumbs|instanceof:'Xcart\App\Components\Breadcrumbs'}
        {set $breadcrumbs = $breadcrumbs->get()}
    {/if}

    {if $breadcrumbs|count > 0}
        <nav class="breadcrumbs-container">
            <ol class="breadcrumb-list no-bullet" itemscope itemtype="http://schema.org/BreadcrumbList" itemprop="breadcrumb">
                <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                    <a itemscope itemtype="http://schema.org/Thing" itemprop="item" href="/">
                        <span itemprop="name">
                            {*{set $sconfig = $.getSiteConfig}*}
                            {*{$sconfig.company_name.value}*}
                        </span>
                    </a>
                    <meta itemprop="position" content="0" />
                </li>

                {foreach $breadcrumbs as $item index=$index last=$last}
                    <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                        {if !$last && $item.url}
                            <a itemscope itemtype="http://schema.org/Thing" itemprop="item" href="{$item.url}">
                                <span itemprop="name">
                                    {$item.name}
                                </span>
                            </a>
                        {else}
                            <span itemscope itemtype="http://schema.org/Thing" itemprop="item">
                                <span itemprop="name">
                                    {$item.name}
                                </span>
                            </span>
                        {/if}

                        <meta itemprop="position" content="{$index +1}" />
                    </li>
                {/foreach}
            </ol>
        </nav>
    {/if}
{/if}

