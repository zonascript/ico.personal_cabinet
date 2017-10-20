<?php

# INPUT:

# $method		[string: POST|GET]

# $url			[string]
#	www.yoursite.com:443/path/to/script.asp

# $data			[array]
#	$data[] = "parametr=value";

# $join			[string]
#	$join = "\&";

# $cookie		[array]
#	$cookie = "parametr=value";

# $conttype		[string]
#	$conttype = "text/xml";

# $referer		[string]
#	$conttype = "http://www.yoursite.com/index.htm";

# $cert			[string]
#	$cert = "../certs/demo-cert.pem";

# $kcert		[string]
#	$keyc = "../certs/demo-keycert.pem";

# $rhead		[string]
#	$rhead = "...";

# $rbody		[string]
#	$rbody = "...";

# [15:53][mclap@rrf:S4][~]$ openssl version
# OpenSSL 0.9.7a Feb 19 2003

function func_https_request_openssl($method, $url, $data="", $join="&", $cookie="", $conttype="application/x-www-form-urlencoded", $referer="", $cert="", $kcert="", $headers="")
{
	if ($method != "POST" && $method != "GET")
		return array("0","X-Cart HTTPS: Invalid method");

	if (!preg_match("/^(https?:\/\/)(.*\@)?([a-z0-9_\.\-]+):(\d+)(\/.*)$/Ui",$url,$m))
		return array("0","X-Cart HTTPS: Invalid URL");

	$openssl_binary = func_find_executable("openssl");
	if (!$openssl_binary)
		return array("0","X-Cart HTTPS: openssl executable is not found");

	if (!X_DEF_OS_WINDOWS)
		putenv("LD_LIBRARY_PATH=".getenv("LD_LIBRARY_PATH").":".dirname($openssl_binary));

	$ui = parse_url($url);

	// build args
	$args[] = "-connect $ui[host]:$ui[port]";
	if ($cert) $args[] = '-cert '.func_shellquote($cert);
	if ($kcert) $args[] = '-key '.func_shellquote($kcert);

	$request = func_https_prepare_request($method, $ui,$data,$join,$cookie,$conttype,$referer,$headers);
	$tmpfile = func_temp_store($request);
	$tmpignore = func_temp_store('');

	if (empty($tmpfile))
		return array(0, "X-Cart HTTPS: cannot create temporaly file");

	$cmdline = func_shellquote($openssl_binary)." s_client ".join(' ',$args)." -quiet < ".func_shellquote($tmpfile)." 2>".func_shellquote($tmpignore);

	// make pipe
	$fp = popen($cmdline, "r");
	if( !$fp )
		return array(0, "X-Cart HTTPS: openssl execution failed");

	$res = func_https_receive_result($fp);
	pclose($fp);

	@unlink($tmpfile);
	@unlink($tmpignore);

	return $res;
}
