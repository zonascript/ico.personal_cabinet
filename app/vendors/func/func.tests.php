<?php

x_load("files");

#
# Function to test Perl presence
#
function test_perl($details = false) {
	global $config;
	$perl_binary = func_find_executable("perl", $config["General"]["perl_binary"]);
	if( $perl_binary ) {
		$fn = func_temp_store('print $];');
		if (empty($fn)) return "";
		$tmpfn = func_temp_store("");

		@exec(func_shellquote($perl_binary)." < ".func_shellquote($fn)." 2>".func_shellquote($tmpfn), $output);
		@unlink($fn);
		@unlink($tmpfn);
		if (!empty($output[0])) {
			if ($details) {
				@exec(func_shellquote($perl_binary)." -V 2>".func_shellquote($tmpfn), $output);
				@unlink($tmpfn);
				return implode("<br />", $output);
			}
			return $output[0];
		}
    }
	return "";
}

#
# Function to test Perl Net:SSLeay module presence
#
function test_ssleay() {
	global $xcart_dir;
	global $config;

	$perl_binary = func_find_executable("perl", $config["General"]["perl_binary"]);
	if ($perl_binary) {
		$script = "require Net::SSLeay; Net::SSLeay->import(qw(get_https post_https sslcat make_headers make_form)); print Net::SSLeay->VERSION;";
		$fn = func_temp_store($script);
		if (empty($fn)) return "";

		$tmpfn = func_temp_store("");
		$includes  = " -I".func_shellquote($xcart_dir.'/payment');
		$includes .= " -I".func_shellquote($xcart_dir.'/payment/Net');
		@exec(func_shellquote($perl_binary).' '.$includes." -w < ".func_shellquote($fn)." 2>".func_shellquote($tmpfn), $output);
		@unlink($fn);
		@unlink($tmpfn);

		if (!empty($output))
			return $output[0];
	}
	return "";
}

#
# Function to test libCURL module presence
#
function test_libcurl() {
	if (function_exists('curl_init')) {
		$info = curl_version();
		if (is_array($info)) {
			if (in_array('https', $info['protocols']))
				return $info['version'];
		}
		elseif (stristr($info,'ssl')) {
			return $info;
		}
	}
	return "";
}

#
# Function to test CURL executable presence
#
function test_curl() {
	$curl = func_find_executable("curl");
	if( $curl ) {
		@exec(func_shellquote($curl)." --version", $output);
		if (!empty($output) && stristr($output[0],'ssl'))
			return $output[0];
	}
	return "";
}

#
# Function to test OpenSSL module presence
#
function test_openssl() {
	$bin = func_find_executable("openssl");
	if( $bin )
		return @exec(func_shellquote($bin)." version");
	return "";
}

#
# Function to test generic HTTPS module presence
#
function test_httpscli() {
	$cli = func_find_executable("https_cli");
	if( $cli ) return $cli;
	return "";
}

#
# This function selects which https module to use
#
function test_active_bouncer($force=false) {
	global $config;
	global $var_dirs;
	static $module_active = null;

	if (!$force && !is_null($module_active))
		return $module_active;

	$bouncers = array ('libcurl', 'curl', 'openssl', 'ssleay', 'httpscli');

	if ($config["General"]["httpsmod"])
		array_unshift($bouncers, $config["General"]["httpsmod"]);

	$result = false;
	foreach ($bouncers as $k=>$bouncer ){
		$fn = "test_$bouncer";
		if (function_exists($fn) && $fn()) {
			$result = $bouncer;
			break;
		}
	}

	$old_module = false;
	$data_file = $var_dirs["log"]."/data.httpsmodule.php";
	if (file_exists($data_file)) {
		ob_start();
		readfile($data_file);
		$old_module = ob_get_contents();
		ob_end_clean();
		$old_module = substr($old_module, strlen(X_LOG_SIGNATURE));
	}

	if (!empty($old_module) && strcmp($old_module, $result)) {
		x_log_add('ENV', "HTTPS module is changed to: $result (was: $old_module)");
	}

	if ($old_module === false || strcmp($old_module, $result)) {
		$_tmp_fp = @fopen($data_file, "wb");
		if ($_tmp_fp !== false) {
			@fwrite($_tmp_fp, X_LOG_SIGNATURE.$result);
			@fclose($_tmp_fp);
		}
	}


	$module_active = $result;

	return $result;
}

#
# Function to test EXPAT module presence
#
function test_expat() {
	ob_start();
	phpinfo(INFO_MODULES);
	$php_info = ob_get_contents();
	ob_end_clean();

	if (preg_match('/EXPAT.+>([\.\d]+)/mi', $php_info, $m))
		return $m[1];

	return function_exists("xml_parser_create") ? "found" : "";
}

#
# Function to test Saferpay client
#
function test_saferpay() {
	global $sql_tbl;
	$module_params = func_query_first("select * from $sql_tbl[ccprocessors] where processor='cc_saferpay.php'");
	if (is_array($module_params)) {
		return func_is_executable($module_params["param04"].(X_DEF_OS_WINDOWS ? "saferpay.exe" : "saferpay"));
	}

	return false;
}

#
# This function tests the requirements of payment methods.
# It will disable the method, if its requirements aren't fulfilled.
#
function test_payment_methods($methods, $hide_disfunctional=false) {
	global $sql_tbl, $config, $xcart_dir, $httpsmod_active;

	if (!is_array($methods))
		return "";

	$result = array();

	foreach ($methods as $index=>$method) {
		$is_down = false;
		$in_testmode = false;

		if ($method["processor"]) {
			$rc = test_ccprocessor(func_query_first("SELECT * FROM $sql_tbl[ccprocessors] WHERE processor='".$method["processor"]."'"));
			$is_down = !$rc["status"];
			if ($is_down && $hide_disfunctional)
				continue;

			$in_testmode = $rc["in_testmode"];
		}

		$method["is_down"] = $is_down;
		$method["in_testmode"] = $in_testmode;
		$result[] = $method;
	}

	return $result;
}

#
# This function tests the requirements of group of CC processors.
#
function test_ccprocessors($ccprocessors, $hide_disfunctional=false) {
	$result = "";
	foreach ($ccprocessors as $index=>$processor) {
		$result = test_ccprocessor($processor);

		$processor["is_down"] = !$result["status"];
		if (!$is_down || !$hide_disfunctional)
			$result[] = $processor;
	}
	return $result;
}

#
# This function tests the requirements of single CC processor.
#
# Note:
# if file $xcart_dir."/payment/test."$module_params["processor"] is found, it should define the following variables:
#     $good ::= true | false
#     $requirement ::= testfunc | testexec | httpsmod
#     $param = param of failed requirement
#
function test_ccprocessor($module_params) {
	global $httpsmod_active;
	global $xcart_dir;
	$good = true;

	if (empty($module_params)) return array("status"=>true,"in_testmode"=>false);

	if (!isset($httpsmod_active) || is_null($httpsmod_active)) {
		$httpsmod_active = test_active_bouncer();
	}

	$in_testmode = get_cc_in_testmode($module_params);

	if (file_exists($xcart_dir."/payment/test.".basename($module_params["processor"]))) {
		@include $xcart_dir."/payment/test.".basename($module_params["processor"]);
	}
	else {
		$requirements = get_ccrequirements($module_params);

		if (empty($requirements))
			return array("status"=>true,"in_testmode"=>$in_testmode);

		foreach($requirements as $requirement=>$param) {
			switch($requirement) {
			case "testfunc": $good = $good && $param($module_params); break;
			case "testexec": $good = $good && func_is_executable($param); break;
			case "httpsmod": $good = $good && !empty($httpsmod_active); break;
			}

			if (!$good) break;
		}
	}

	return array("status"=>$good,"failed_func"=>$requirement,"failed_param"=>$param,"in_testmode"=>$in_testmode);
}

#
# This function defines the requirements of CC processors
#
# Possible requirements:
#   $result["testexec"] = "filename" - try to execute specified executable
#   $result["testfunc"] = "function name" - try to execute specified function
#   $result["httpsmod"] = true - processor depends on https modules
#
# Note: if file $xcart_dir."/payment/req."$module_params["processor"] is found, it should define the $result variable.
#
function get_ccrequirements($module_params) {
	global $sql_tbl, $xcart_dir, $httpsmod_active;
	global $config;

	if (empty($module_params) || empty($module_params["processor"])) return array(true,"");

	$result = "";

	if (file_exists($xcart_dir."/payment/req.".basename($module_params["processor"]))) {
		@include $xcart_dir."/payment/req.".basename($module_params["processor"]);
	}
	else
	switch ($module_params["processor"]) {
		case "ch_wtsbank.php":
		case "ch_authorizenet.php":
		case 'cc_payzip.php':
		case 'cc_epdq.php':
		case 'ps_paypal.php':
		case 'ps_nochex.php':
			$result["httpsmod"] = true;
			break;
		case 'ps_paypal_pro.php':
			$result["httpsmod"] = true;
			$result["testfunc"] = "test_paypal_pro";
			break;
		case 'cc_payflow_pro.php':
			break;

		case 'cc_saferpay.php':
			$result["testexec"] = $module_params["param04"]."saferpay";
			break;

		case 'cc_paybox.php':
			$result["testexec"] = $xcart_dir."/payment/bin/paybox.cgi";
			break;

		case 'cc_trustcommerce.php':
			if (!empty($httpsmod_active))
				$result["httpsmod"] = true;
			else
				$result["testfunc"] = "test_trustcommerce";
			break;

		case 'cc_csrc.php':
			if (file_exists($xcart_dir."/payment/ics/bin/ics"))
				$result["testexec"] = $xcart_dir."/payment/ics/bin/ics";
			else
				$result["testexec"] = func_find_executable("perl",$config["General"]["perl_binary"]);
			break;

		default:
			if ($module_params["background"]=="Y")
				$result["httpsmod"] = true;
	}

	return $result;
}

#
# Function to test TrustCommerce client
#
function test_trustcommerce() {
	global $xcart_dir;

	if (!extension_loaded("tclink")) {
		$extfname = $xcart_dir."/payment/tclink/tclink.so";
#
# The excessive "../" bit allows us to back up out of the extension dir
# so that we can access the current working directory.  It is only done
# this way so that tctest.php may work out of the box on any system, you
# can get rid of this if you do a global install of TCLink.
#
		$extension_dir = realpath(ini_get("extension_dir"));
		$relative_path = str_repeat("..".DIRECTORY_SEPARATOR,substr_count($extension_dir,DIRECTORY_SEPARATOR));
		if (!preg_match("/[\/\\\]$/",$extension_dir))
			$extension_dir .= DIRECTORY_SEPARATOR;

		$extfname = $relative_path.preg_replace('/(.:\\\)|(^\/)/i','',$extfname);

		if (@file_exists($extension_dir.$extfname) && function_exists('dl'))
			@dl($extfname);
	}

	return function_exists("tclink_send");
}

function test_paypal_pro($module_params) {
	global $xcart_dir;

	if (empty($module_params) || !is_array($module_params))
		return false;

	# API Signature
	if ($module_params['param07'] == 'S') {
		return !empty($module_params['param05']);
	}

	# API Certificate file
	$pp_cert_file = $xcart_dir.'/payment/certs/'.$module_params['param04'];
	return @file_exists($pp_cert_file) && @is_file($pp_cert_file) && @is_readable($pp_cert_file);
}

#
# This function returns Live/Test mode status:
# true then cc in test mode and false overwise.
#
function get_cc_in_testmode($module_params) {
	if (empty($module_params) || $module_params["processor"]=="cc_test.php") return true;

	return $module_params["testmode"] != "N";
}

