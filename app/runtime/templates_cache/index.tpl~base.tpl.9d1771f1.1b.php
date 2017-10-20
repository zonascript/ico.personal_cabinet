<?php 
/** Fenom template 'main/index.tpl' compiled at 2017-10-19 08:01:17 */
return new Fenom\Render($fenom, function ($var, $tpl) {
?><!doctype html>
<html lang="en" class="no-js <?php
/* base/head.tpl:2: {if $.detector->isMobile()} */
 if($tpl->getStorage()->detector->isMobile()) { ?> mobile <?php
/* base/head.tpl:2: {/if} */
 } ?><?php
/* base/head.tpl:2: {if $.detector->isTablet()} */
 if($tpl->getStorage()->detector->isTablet()) { ?> tablet <?php
/* base/head.tpl:2: {/if} */
 } ?>" itemscope itemtype="http://schema.org/WebSite">
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

    
    
    <link rel="shortcut icon" href="/favicon.png" type="image/png" />

    <?php
/* base/head.tpl:30: {meta controller=$this!:null} */
 $info = $tpl->getStorage()->getTag('meta');
echo call_user_func_array($info["function"], array(array("controller" => (isset($var["this"]) ? $var["this"] : (null))), $tpl, &$var)); ?>

    <script type="application/ld+json">
    {
      "@context": "http://schema.org",
      "@type": "WebSite",
      "url": "http://www.s3stores.com/",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "http://www.s3stores.com/shop?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>

    <style type="text/css"><?php
/* base/head.tpl:45: {inline file="static/frontend/dist/css/base.css"} */
 $info = $tpl->getStorage()->getTag('inline');
echo call_user_func_array($info["function"], array(array("file" => "static/frontend/dist/css/base.css"), $tpl, &$var)); ?></style>


    <link rel="stylesheet" href="/static/frontend/dist/css/styles.css?v=<?php
/* base/head.tpl:48: {frontend_version resource='css/styles.css'} */
 $info = $tpl->getStorage()->getTag('frontend_version');
echo call_user_func_array($info["function"], array(array("resource" => 'css/styles.css'), $tpl, &$var)); ?>" media="all" onload="if(media!='all')media='all'">
    
    
    

    

    <?php
/* base/head.tpl:55: {filter|unescape} */
 ob_start(); ?>
    <?php
/* base/head.tpl:56: {get_assets type="css" position='head'} */
 $info = $tpl->getStorage()->getTag('get_assets');
echo call_user_func_array($info["function"], array(array("type" => "css","position" => 'head'), $tpl, &$var)); ?>
    <?php
/* base/head.tpl:57: {get_assets type="js" position='head'} */
 $info = $tpl->getStorage()->getTag('get_assets');
echo call_user_func_array($info["function"], array(array("type" => "js","position" => 'head'), $tpl, &$var)); ?>
    <?php
/* base/head.tpl:58: {/filter} */
 echo Fenom\Modifier::unescape(ob_get_clean()); ?>
</head>
<body itemscope itemprop="mainEntity" itemtype="http://schema.org/WebPage" class="loading loading-active">

<?php
/* base/head.tpl:62: {filter|strip:true} */
 ob_start(); ?>


    <div class="loader-bg waves waves-dark">
        <div class="loader-wrapper">
            <div class="loader-spinner"></div>
            <div class="loader-container"></div>
        </div>
    </div>



<section id="main_wrapper" class="off-canvas-wrapper">
    <div class="off-canvas position-left" id="offCanvasLeft" data-off-canvas data-transition="push">
        <?php
/* base.tpl:5: {insert "_parts/_menu_mobile.tpl"} */
 ?><div class="menu-title">
    <h3>Menu</h3>
</div>
<ul class="accordion" data-accordion data-allow-all-closed="true" data-multi-expand="true">
    <?php $t6606dd67_1 = Modules\Menu\TemplateLibraries\MenuLibrary::getMenuItems('main-menu'); if(is_array($t6606dd67_1) && count($t6606dd67_1) || ($t6606dd67_1 instanceof \Traversable)) {
  foreach($t6606dd67_1 as $var["item"]) { ?>
        <li class="accordion-item" <?php
/* _parts/_menu_mobile.tpl:6: {if $item.items } */
 if($var["item"]["items"]) { ?>data-accordion-item<?php
/* _parts/_menu_mobile.tpl:6: {/if} */
 } ?>>
            <a class="accordion-title" <?php
/* _parts/_menu_mobile.tpl:7: {if !$item.items} */
 if(!$var["item"]["items"]) { ?>href="<?php
/* _parts/_menu_mobile.tpl:7: {$item.url} */
 echo $var["item"]["url"]; ?>" <?php
/* _parts/_menu_mobile.tpl:7: {/if} */
 } ?>>
                <div class="row">
                    <div class="columns small-12">
                        <span><?php
/* _parts/_menu_mobile.tpl:10: {$item.name} */
 echo $var["item"]["name"]; ?></span>
                    </div>
                </div>
            </a>
            
                
                    
                
            
        </li>
    <?php
/* _parts/_menu_mobile.tpl:20: {/foreach} */
   } } ?>
</ul><?php ?>
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
                                <?php
/* base.tpl:23: {get_menu code='main-menu'} */
 $info = $tpl->getStorage()->getTag('get_menu');
echo call_user_func_array($info["function"], array(array("code" => 'main-menu'), $tpl, &$var)); ?>
                            </ul>
                        </div>
                        <div class="column medium-2 show-for-xl"></div>
                    </div>
                </section>
            </header>

            <section id="content">
                    <section class="before-content">
                        
                            <?php
/* base.tpl:34: {render_breadcrumbs:raw template="base/_breadcrumbs.tpl"} */
 $info = $tpl->getStorage()->getTag('render_breadcrumbs');
echo call_user_func_array($info["function"], array(array("template" => "base/_breadcrumbs.tpl"), $tpl, &$var)); ?>
                            <?php
/* base.tpl:35: {render_flash:raw template='base/_flash.tpl'} */
 $info = $tpl->getStorage()->getTag('render_flash');
echo call_user_func_array($info["function"], array(array("template" => 'base/_flash.tpl'), $tpl, &$var)); ?>
                        
                    </section>

                
    <section class="page index bg-dark-blue" data-background="/static/frontend/dist/images/page-bg/dark-page-bg-origin.jpg">
        <div class="row">
            <div class="column small-12">

                <div class="stores-list list" >
                    <?php
/* main/index.tpl:9: {if $storefronts} */
 if($var["storefronts"]) { ?>
                    <?php  if(!empty($var["storefronts"]) && (is_array($var["storefronts"]) || $var["storefronts"] instanceof \Traversable)) {
  foreach($var["storefronts"] as $var["item"]) { ?>
                        <div class="item">
                            <a href="https://<?php
/* main/index.tpl:12: {$item->storefront->domain} */
 echo $var["item"]->storefront->domain; ?>" class="item-wrapper" target="_blank">
                                <div class="image-wrapper cont">
                                    <img data-original="<?php
/* main/index.tpl:14: {$item->list_image->sizeUrl('q85')} */
 echo $var["item"]->list_image->sizeUrl('q85'); ?>" class="lazy lazy-img background">
                                    <img data-original="<?php
/* main/index.tpl:15: {$item->list_icon->getUrl()} */
 echo $var["item"]->list_icon->getUrl(); ?>" alt="<?php
/* main/index.tpl:15: {$item->getName()} */
 echo $var["item"]->getName(); ?>" class="lazy lazy-img logo">
                                </div>
                                <div class="content-wrapper cont">
                                    <h2><?php
/* main/index.tpl:18: {$item->getName()} */
 echo $var["item"]->getName(); ?></h2>
                                    <div class="description dot"><?php
/* main/index.tpl:19: {$item->description} */
 echo $var["item"]->description; ?></div>
                                </div>
                            </a>
                        </div>
                    <?php
/* main/index.tpl:23: {/foreach} */
   } } ?>
                    <?php
/* main/index.tpl:24: {/if} */
 } ?>
                </div>

            </div>
        </div>
    </section>


                <section class="after-content">
                        
                </section>

            </section>
            <div class="push"></div>
        </section>


        <footer  itemscope itemtype="http://schema.org/WPFooter">
            <div class="row">
                <div class="column small-12">
                    <ul class="footer-menu no-bullet">
                        <?php
/* base.tpl:54: {get_menu code='footer-menu'} */
 $info = $tpl->getStorage()->getTag('get_menu');
echo call_user_func_array($info["function"], array(array("code" => 'footer-menu'), $tpl, &$var)); ?>
                    </ul>
                </div>
            </div>
        </footer>
    </div>
</section>


<?php
/* base/head.tpl:75: {/filter} */
 echo Fenom\Modifier::strip(ob_get_clean(), true); ?>

    <script>
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
          m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
      ga('create', 'UA-952715-27', 'auto');
      ga('send', 'pageview');
    </script>



    <link rel="stylesheet" href="/static/frontend/dist/css/main.css?v=<?php
/* base/head.tpl:88: {frontend_version resource='css/main.css'} */
 $info = $tpl->getStorage()->getTag('frontend_version');
echo call_user_func_array($info["function"], array(array("resource" => 'css/main.css'), $tpl, &$var)); ?>" media="none" onload="if(media!='all')media='all'">

    <script src="/static/frontend/dist/js/vendors.js?v=<?php
/* base/head.tpl:90: {frontend_version resource='vendors.js'} */
 $info = $tpl->getStorage()->getTag('frontend_version');
echo call_user_func_array($info["function"], array(array("resource" => 'vendors.js'), $tpl, &$var)); ?>" defer></script>
    <script src="/static/frontend/dist/js/main.js?v=<?php
/* base/head.tpl:91: {frontend_version resource="js/main.js"} */
 $info = $tpl->getStorage()->getTag('frontend_version');
echo call_user_func_array($info["function"], array(array("resource" => "js/main.js"), $tpl, &$var)); ?>" defer></script>



<?php
/* base/head.tpl:95: {filter|unescape} */
 ob_start(); ?>
<?php
/* base/head.tpl:96: {get_assets type="css"} */
 $info = $tpl->getStorage()->getTag('get_assets');
echo call_user_func_array($info["function"], array(array("type" => "css"), $tpl, &$var)); ?>
<?php
/* base/head.tpl:97: {get_assets type="js"} */
 $info = $tpl->getStorage()->getTag('get_assets');
echo call_user_func_array($info["function"], array(array("type" => "js"), $tpl, &$var)); ?>
<?php
/* base/head.tpl:98: {/filter} */
 echo Fenom\Modifier::unescape(ob_get_clean()); ?>

</body>
</html><?php
}, array(
	'options' => 320,
	'provider' => false,
	'name' => 'main/index.tpl',
	'base_name' => 'main/index.tpl',
	'time' => 1508412264,
	'depends' => array (
  0 => 
  array (
    'main/index.tpl' => 1508412264,
    'base.tpl' => 1508400987,
  ),
),
	'macros' => array(),

        ));
