<?php 
/** Fenom template 'menu/menu.tpl' compiled at 2017-10-19 06:13:45 */
return new Fenom\Render($fenom, function ($var, $tpl) {
?><?php  if(!empty($var["items"]) && (is_array($var["items"]) || $var["items"] instanceof \Traversable)) {
  foreach($var["items"] as $var["item"]) { ?>
<?php
/* menu/menu.tpl:2: {set $classes = [$item.class?$item.class:'']} */
 $var["classes"]=array((empty($var["item"]["class"]) ? '' : $var["item"]["class"])); ?>
<?php
/* menu/menu.tpl:3: {if $item.items} */
 if($var["item"]["items"]) { ?>
    <?php
/* menu/menu.tpl:4: {set $classes[] = 'has-subitems'} */
 $var["classes"][]='has-subitems'; ?>
<?php
/* menu/menu.tpl:5: {/if} */
 } ?>
<?php
/* menu/menu.tpl:6: {if $item.url && $.request->getPath() == $item.url} */
 if($var["item"]["url"] && $tpl->getStorage()->request->getPath() == $var["item"]["url"]) { ?>
    <?php
/* menu/menu.tpl:7: {set $classes[] = 'active'} */
 $var["classes"][]='active'; ?>
<?php
/* menu/menu.tpl:8: {/if} */
 } ?>

<li class="<?php
/* menu/menu.tpl:10: {$classes|implode:' '} */
 echo implode($var["classes"], ' '); ?>">
    <a href="<?php
/* menu/menu.tpl:11: {$item.url ? $item.url : "#" } */
 echo (empty($var["item"]["url"]) ? "#" : $var["item"]["url"]); ?>">
        <?php
/* menu/menu.tpl:12: {$item.name} */
 echo $var["item"]["name"]; ?>
    </a>

    <?php
/* menu/menu.tpl:15: {if $item.items} */
 if($var["item"]["items"]) { ?>
        <ul>
            <?php
/* menu/menu.tpl:17: {include "menu/menu.tpl" items=$item.items} */
 $t5052c293_1 = $var; $var = array("items" => $var["item"]["items"]) + $var; ?><?php  if(!empty($var["items"]) && (is_array($var["items"]) || $var["items"] instanceof \Traversable)) {
  foreach($var["items"] as $var["item"]) { ?>
<?php
/* menu/menu.tpl:2: {set $classes = [$item.class?$item.class:'']} */
 $var["classes"]=array((empty($var["item"]["class"]) ? '' : $var["item"]["class"])); ?>
<?php
/* menu/menu.tpl:3: {if $item.items} */
 if($var["item"]["items"]) { ?>
    <?php
/* menu/menu.tpl:4: {set $classes[] = 'has-subitems'} */
 $var["classes"][]='has-subitems'; ?>
<?php
/* menu/menu.tpl:5: {/if} */
 } ?>
<?php
/* menu/menu.tpl:6: {if $item.url && $.request->getPath() == $item.url} */
 if($var["item"]["url"] && $tpl->getStorage()->request->getPath() == $var["item"]["url"]) { ?>
    <?php
/* menu/menu.tpl:7: {set $classes[] = 'active'} */
 $var["classes"][]='active'; ?>
<?php
/* menu/menu.tpl:8: {/if} */
 } ?>

<li class="<?php
/* menu/menu.tpl:10: {$classes|implode:' '} */
 echo implode($var["classes"], ' '); ?>">
    <a href="<?php
/* menu/menu.tpl:11: {$item.url ? $item.url : "#" } */
 echo (empty($var["item"]["url"]) ? "#" : $var["item"]["url"]); ?>">
        <?php
/* menu/menu.tpl:12: {$item.name} */
 echo $var["item"]["name"]; ?>
    </a>

    <?php
/* menu/menu.tpl:15: {if $item.items} */
 if($var["item"]["items"]) { ?>
        <ul>
            <?php
/* menu/menu.tpl:17: {include "menu/menu.tpl" items=$item.items} */
 $tpl->getStorage()->getTemplate("menu/menu.tpl")->display(array("items" => $var["item"]["items"]) + $var); ?>
        </ul>
    <?php
/* menu/menu.tpl:19: {/if} */
 } ?>
</li>
&ensp;
<?php
/* menu/menu.tpl:22: {/foreach} */
   } } ?>
<?php $var = $t5052c293_1; unset($t5052c293_1); ?>
        </ul>
    <?php
/* menu/menu.tpl:19: {/if} */
 } ?>
</li>
&ensp;
<?php
/* menu/menu.tpl:22: {/foreach} */
   } } ?>
<?php
}, array(
	'options' => 64,
	'provider' => false,
	'name' => 'menu/menu.tpl',
	'base_name' => 'menu/menu.tpl',
	'time' => 1508400987,
	'depends' => array (
  0 => 
  array (
    'menu/menu.tpl' => 1508400987,
  ),
),
	'macros' => array(),

        ));
