<?php 
/** Fenom template 'base/_breadcrumbs.tpl' compiled at 2017-10-19 06:13:45 */
return new Fenom\Render($fenom, function ($var, $tpl) {
?><?php
/* base/_breadcrumbs.tpl:1: {if isset($breadcrumbs) && ($breadcrumbs|instanceof:'Xcart\App\Components\Breadcrumbs' || is_array($breadcrumbs))} */
 if(isset($var["breadcrumbs"]) && (call_user_func($tpl->getStorage()->getModifier("instanceof"), $var["breadcrumbs"], 'Xcart\App\Components\Breadcrumbs') || is_array($var["breadcrumbs"]))) { ?>

    <?php
/* base/_breadcrumbs.tpl:3: {if $breadcrumbs|instanceof:'Xcart\App\Components\Breadcrumbs'} */
 if(call_user_func($tpl->getStorage()->getModifier("instanceof"), $var["breadcrumbs"], 'Xcart\App\Components\Breadcrumbs')) { ?>
        <?php
/* base/_breadcrumbs.tpl:4: {set $breadcrumbs = $breadcrumbs->get()} */
 $var["breadcrumbs"]=$var["breadcrumbs"]->get(); ?>
    <?php
/* base/_breadcrumbs.tpl:5: {/if} */
 } ?>

    <?php
/* base/_breadcrumbs.tpl:7: {if $breadcrumbs|count > 0} */
 if(count($var["breadcrumbs"]) > 0) { ?>
        <nav class="breadcrumbs-container">
            <ol class="breadcrumb-list no-bullet" itemscope itemtype="http://schema.org/BreadcrumbList" itemprop="breadcrumb">
                <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                    <a itemscope itemtype="http://schema.org/Thing" itemprop="item" href="/">
                        <span itemprop="name">
                            
                            
                        </span>
                    </a>
                    <meta itemprop="position" content="0" />
                </li>

                <?php  if(!empty($var["breadcrumbs"]) && (is_array($var["breadcrumbs"]) || $var["breadcrumbs"] instanceof \Traversable)) {
 $t321af66c_1_index = 0; $var["index"] = &$t321af66c_1_index; $t321af66c_2_last = false; $t321af66c_3 = count($var["breadcrumbs"]); $var["last"] = &$t321af66c_2_last; foreach($var["breadcrumbs"] as $var["item"]) { if(!--$t321af66c_3) $t321af66c_2_last = true;?>
                    <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                        <?php
/* base/_breadcrumbs.tpl:22: {if !$last && $item.url} */
 if(!$var["last"] && $var["item"]["url"]) { ?>
                            <a itemscope itemtype="http://schema.org/Thing" itemprop="item" href="<?php
/* base/_breadcrumbs.tpl:23: {$item.url} */
 echo $var["item"]["url"]; ?>">
                                <span itemprop="name">
                                    <?php
/* base/_breadcrumbs.tpl:25: {$item.name} */
 echo $var["item"]["name"]; ?>
                                </span>
                            </a>
                        <?php
/* base/_breadcrumbs.tpl:28: {else} */
 } else { ?>
                            <span itemscope itemtype="http://schema.org/Thing" itemprop="item">
                                <span itemprop="name">
                                    <?php
/* base/_breadcrumbs.tpl:31: {$item.name} */
 echo $var["item"]["name"]; ?>
                                </span>
                            </span>
                        <?php
/* base/_breadcrumbs.tpl:34: {/if} */
 } ?>

                        <meta itemprop="position" content="<?php
/* base/_breadcrumbs.tpl:36: {$index +1} */
 echo $var["index"] + 1; ?>" />
                    </li>
                <?php
/* base/_breadcrumbs.tpl:38: {/foreach} */
  $t321af66c_1_index++; } } ?>
            </ol>
        </nav>
    <?php
/* base/_breadcrumbs.tpl:41: {/if} */
 } ?>
<?php
/* base/_breadcrumbs.tpl:42: {/if} */
 } ?>

<?php
}, array(
	'options' => 64,
	'provider' => false,
	'name' => 'base/_breadcrumbs.tpl',
	'base_name' => 'base/_breadcrumbs.tpl',
	'time' => 1508400987,
	'depends' => array (
  0 => 
  array (
    'base/_breadcrumbs.tpl' => 1508400987,
  ),
),
	'macros' => array(),

        ));
