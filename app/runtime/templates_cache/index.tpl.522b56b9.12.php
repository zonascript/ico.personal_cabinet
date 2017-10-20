<?php 
/** Fenom template 'main/index.tpl' compiled at 2017-10-19 08:01:17 */
return new Fenom\Render($fenom, function ($var, $tpl) {
?><?php $tpl->getStorage()->display(array('main/index.tpl', (($tpl->getStorage()->request->getIsAjax() != false) ? 'ajax.tpl' : 'base.tpl')), $var); ?><?php
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
  ),
),
	'macros' => array(),

        ));
