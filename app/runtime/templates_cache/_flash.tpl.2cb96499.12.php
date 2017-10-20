<?php 
/** Fenom template 'base/_flash.tpl' compiled at 2017-10-19 06:13:45 */
return new Fenom\Render($fenom, function ($var, $tpl) {
?><div class="row w1280">
    <div class="columns small-12">
        <div class="flash-messages-block">
            <div class="flash-list"></div>
        </div>
    </div>
</div>

<script>
    window['flashStack'] = [];

    <?php  if(!empty($var["messages"]) && (is_array($var["messages"]) || $var["messages"] instanceof \Traversable)) {
  foreach($var["messages"] as $var["item"]) { ?>
    window['flashStack'].push({ 'message': <?php
/* base/_flash.tpl:13: {$item['message']} */
 echo $var["item"]['message']; ?>, 'type': <?php
/* base/_flash.tpl:13: {$item['type']} */
 echo $var["item"]['type']; ?> });
    <?php
/* base/_flash.tpl:14: {/foreach} */
   } } ?>
</script><?php
}, array(
	'options' => 64,
	'provider' => false,
	'name' => 'base/_flash.tpl',
	'base_name' => 'base/_flash.tpl',
	'time' => 1508400987,
	'depends' => array (
  0 => 
  array (
    'base/_flash.tpl' => 1508400987,
  ),
),
	'macros' => array(),

        ));
