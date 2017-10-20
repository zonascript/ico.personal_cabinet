<!doctype html>
<html lang="en" class="no-js {if $.detector->isMobile()} mobile {/if}{if $.detector->isTablet()} tablet {/if}" itemscope itemtype="http://schema.org/WebSite">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0" />
    <meta name="format-detection" content="telephone=no">
    <meta name="format-detection" content="date=no">
    <meta name="format-detection" content="address=no">
    <meta name="format-detection" content="email=no">

    <link rel="dns-prefetch" href="https://www.google-analytics.com">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">

    <link rel="manifest" href="manifest.json">

    <meta name="google-site-verification" content="zCfqc6Qs_qT7eKEZsAHGAjlRN3ngdzrSsd-hkTE280o" />

    <meta name="apple-touch-fullscreen" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="S3 Stores">

    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="S3 Stores">

    {*<meta name="url" itemprop="url" content="https://s3stores.com/" >*}
    {*<meta name="name" itemprop='name' content="S3 Stores">*}
    <link rel="shortcut icon" href="/favicon.png" type="image/png" />

    {block 'seo'}{meta controller=$this!:null}{/block}

    <script type="application/ld+json">
    {
      "@context": "http://schema.org",
      "@type": "WebSite",
      "url": "http://www.s3stores.com/",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "http://www.s3stores.com/shop?q={ignore}{search_term_string}{/ignore}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>

    <style type="text/css">{inline file="static/frontend/dist/css/base.css"}</style>


    <link rel="stylesheet" href="/static/frontend/dist/css/styles.css?v={frontend_version resource='css/styles.css'}" media="all" onload="if(media!='all')media='all'">
    {*<noscript>*}
    {*<link rel="stylesheet" href="/static/frontend/dist/css/styles.css?v={frontend_version resource='css/styles.css'}">*}
    {*</noscript>*}

    {block 'head'}{/block}

    {filter|unescape}
    {get_assets type="css" position='head'}
    {get_assets type="js" position='head'}
    {/filter}
</head>
<body itemscope itemprop="mainEntity" {block 'schema_page_type'}itemtype="http://schema.org/WebPage"{/block} class="loading loading-active">

{filter|strip:true}
{autoescape true}
{block 'preloader'}
    <div class="loader-bg waves waves-dark">
        <div class="loader-wrapper">
            <div class="loader-spinner"></div>
            <div class="loader-container"></div>
        </div>
    </div>
{/block}

{block "wrapper"}{/block}
{/autoescape}
{/filter}
{ignore}
    <script>
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
          m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
      ga('create', 'UA-952715-27', 'auto');
      ga('send', 'pageview');
    </script>
{/ignore}


    <link rel="stylesheet" href="/static/frontend/dist/css/main.css?v={frontend_version resource='css/main.css'}" media="none" onload="if(media!='all')media='all'">

    <script src="/static/frontend/dist/js/vendors.js?v={frontend_version resource='vendors.js'}" defer></script>
    <script src="/static/frontend/dist/js/main.js?v={frontend_version resource="js/main.js"}" defer></script>

{block 'js'}{/block}

{filter|unescape}
{get_assets type="css"}
{get_assets type="js"}
{/filter}

</body>
</html>