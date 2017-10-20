<?php 
/** Fenom template 'main/index.tpl' compiled at 2017-10-19 06:20:07 */
return new Fenom\Render($fenom, function ($var, $tpl) {
?><?php $tpl->getStorage()->display(array('main/index.tpl', (($tpl->getStorage()->request->getIsAjax() != false) ? 'ajax.tpl' : 'base.tpl')), $var); ?><?php
}, array(
	'options' => 64,
	'provider' => false,
	'name' => 'main/index.tpl',
	'base_name' => 'main/index.tpl',
	'time' => 1508400987,
	'depends' => array (
  0 => 
  array (
    'main/index.tpl' => 1508400987,
  ),
),
	'macros' => array(),

        ));
