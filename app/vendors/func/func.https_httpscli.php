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

# bin/https_cli.c

function func_https_request_httpscli($method, $url, $data="", $join="&", $cookie="", $conttype="application/x-www-form-urlencoded", $referer="", $cert="", $kcert="", $headers="")
{
	if ($method != "POST" && $method != "GET")
		return array("0","X-Cart HTTPS: Invalid method");

	if (!preg_match("/^(https?:\/\/)(.*\@)?([a-z0-9_\.\-]+):(\d+)(\/.*)$/Ui",$url,$m))
		return array("0","X-Cart HTTPS: Invalid URL");

	$ui = parse_url($url);
	$binary = func_find_executable("https_cli");
	if (!$binary)
		return array("0","X-Cart HTTPS: https_cli executable is not found");

	if (!X_DEF_OS_WINDOWS)
		putenv("LD_LIBRARY_PATH=".getenv("LD_LIBRARY_PATH").":".dirname($binary));

	$request = func_https_prepare_request($method, $ui,$data,$join,$cookie,$conttype,$referer,$headers);
	$tmpfile = func_temp_store($request);

	if (empty($tmpfile))
		return array(0, "X-Cart HTTPS: cannot create temporaly file");

	$cmdline = func_shellquote($binary)." $ui[host] $ui[port] ".func_shellquote($cert)." ".func_shellquote($kcert)." < ".func_shellquote($tmpfile);

	// make pipe
	$fp = popen($cmdline, "r");
	if (!$fp)
		return array(0, "X-Cart HTTPS: https_cli execution failed");

	$res = func_https_receive_result($fp);
	pclose($fp);

	@unlink($tmpfile);

	return $res;
}
