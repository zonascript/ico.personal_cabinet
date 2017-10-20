<?php

x_load('files');

#
# For testing purpose: outputs contents of requested variables
# example:
#  func_print_r($categories,$cart,$userinfo,$GLOBALS);
#
function func_print_r() {
	static $count = 0;
	global $login;

	$args = func_get_args();

	$msg = "<div align=\"left\"><pre><font>";
	$log = "Logged as: $login\n";
	if (!empty($args)) {
		foreach ($args as $index=>$variable_content){
			$msg .= "<b>Debug [".$index."/".$count."]:</b> ";
			$log .= "Debug [".$index."/".$count."]: ";
			ob_start();
			print_r($variable_content);
			$data = ob_get_contents(); ob_end_clean();
			$msg .= htmlspecialchars($data)."\n";
			$log .= $data."\n";
		}
	}
	else {
		$msg .= '<b>Debug notice:</b> try to use func_print_r($varname1,$varname2); '."\n";
		$log .= 'Debug notice: try to use func_print_r($varname1,$varname2); '."\n";
	}

	$msg .= "</font></pre></div>";

	if (x_debug_ctl('P') === true) {
		echo $msg;
	}

//	x_log_flag('log_debug_messages', 'DEBUG', $log, true, 1);

	$count++;
}

function func_dump()
{
    $cron  = \Xcart\App\Cli\Cli::isCli();

    foreach (func_get_args() as $arg)
    {
        if (!$cron) { echo "<pre>"; }

        print_r($arg);

        if (!$cron) { echo "</pre>"; }
    }
}

#
# For testing purpose: outputs contents of requested global variables
# example:
#   global $categories, $cart, $userinfo;
#   func_print_d("categories","cart","userinfo","GLOBALS");
#
function func_print_d() {
	global $login;

	$varnames = func_get_args();

	$msg = "<div align=\"left\"><pre><font>";
	$log = "Logged as: $login\n";
	if (!empty($varnames)) {
		foreach ($varnames as $variable_name){
			if (!is_string($variable_name) || empty($variable_name)) {
				$msg .= '<b>Debug notice:</b> try to use func_print_d("varname1","varname2") instead of func_print_d($varname1,$varname2); '."\n";
				$log .= 'Debug notice: try to use func_print_d("varname1","varname2") instead of func_print_d($varname1,$varname2); '."\n";
			}
			else {
				$msg .= "<b>$variable_name</b> = ";
				$log .= "$variable_name = ";
				ob_start();
				if ($variable_name == 'GLOBALS')
					print_r($GLOBALS);
				else {
					if (!@isset($GLOBALS[$variable_name])) {
						echo "is unset!";
					}
					else
						print_r($GLOBALS[$variable_name]);
				}

				$data = ob_get_contents(); ob_end_clean();
				$msg .= htmlspecialchars($data)."\n";
				$log .= $data."\n";
			}
		}
	}
	else {
		$msg .= '<b>Debug notice:</b> try to use func_print_d("varname1","varname2") instead of func_print_d($varname1,$varname2); '."\n";
		$log .= 'Debug notice: try to use func_print_d("varname1","varname2") instead of func_print_d($varname1,$varname2); '."\n";
	}

	$msg .= "</font></pre></div>";

	if (x_debug_ctl('P') === true) {
		echo $msg;
	}

	x_log_flag('log_debug_messages', 'DEBUG', $log, true, 1);
}

#
# For testing purpose: outputs contents using format string like sprintf() does
# example:
#   func_print_f("var1=%f, var2=%f, array3=%s",$var1,$var2,$array3);
#
function func_print_f() {
	global $login;
	global $xcart_dir;

	$args = func_get_args();
	foreach ($args as $k=>$v) {
		if (is_array($v) || is_object($v)) {
			ob_start();
			print_r($v);
			$args[$k] = ob_get_contents();
			ob_end_clean();
		}
	}

	$bt = func_get_backtrace(1);
	$suffix = $bt[0];
	if (func_pathcmp($suffix, $xcart_dir.DIRECTORY_SEPARATOR, 2)) {
		$suffix = substr($suffix, strlen($xcart_dir)+1);
	}

	$suffix = ' ('.$suffix.')';

	$str = call_user_func_array('sprintf', $args);
	if (strlen($str) < 1) $str = '(empty debug message)';

	$log = "Logged as: $login\nDebug: ".$str."\n";

	$msg = "<div align=\"left\"><pre><font>";
	$msg .= "<b>Debug:</b> ".htmlspecialchars($str.$suffix)."\n";
	$msg .= "</font></pre></div>\n";

	if (x_debug_ctl('P') === true) {
		echo $msg;
	}

	x_log_flag('log_debug_messages', 'DEBUG', $log, true, 1);
}

#
# This function displays how much memory currently is used
#
function func_get_memory_used($label="") {
	$backtrace = debug_backtrace();
	echo $label . " File: " . $backtrace[0]["file"] . "<br />Line: " . $backtrace[0]["line"] . "<br />Memory is used: " . memory_get_usage() . "<hr />";
}
