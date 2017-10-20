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

function func_https_request_curl($method, $url, $data="", $join="&", $cookie="", $conttype="application/x-www-form-urlencoded", $referer="", $cert="", $kcert="", $headers="", $timeout = 0) {
	global $config;

	if ($method != "POST" && $method != "GET")
		return array("0","X-Cart HTTPS: Invalid method");

	if (!preg_match("/^(https?:\/\/)(.*\@)?([a-z0-9_\.\-]+):(\d+)(\/.*)$/SUi",$url,$m))
		return array("0","X-Cart HTTPS: Invalid URL");

	$curl_binary = func_find_executable("curl");
	if (!$curl_binary)
		return array("0","X-Cart HTTPS: curl executable is not found");

	if (!X_DEF_OS_WINDOWS)
		putenv("LD_LIBRARY_PATH=".getenv("LD_LIBRARY_PATH").":".dirname($curl_binary));

	$tmpfile = func_temp_store("");

	if (empty($tmpfile))
		return array(0, "X-Cart HTTPS: cannot create temporaly file");

	$execline = func_shellquote($curl_binary)." --http1.0 -D-";
	@exec(func_shellquote($curl_binary)." --version", $output);
	$version = @$output[0];
	# -k|--insecure key is supported by curl since version 7.10
	$supports_insecure = false;
	if (preg_match('/curl ([^ $]+)/S', $version, $m) ){
		$parts = explode(".",$m[1]);
		if( $parts[0] > 7 || ($parts[0] = 7 && $parts[1] >= 10) )
			$supports_insecure = true;
	}

	if (!empty($config['General']['https_proxy']))
		$execline .= " --proxytunnel --proxy ".$config['General']['https_proxy'];

	# Set GET method flag
	if ($method=="GET")
		$execline.= " --get";

	# Set TimeOut parameter
	$timeout = abs(intval($timeout));
	if (!empty($timeout)) {
		$execline.= " --connect-timeout ".$timeout." -m ".$timeout;
	}

	# Combine REQUEST string
	$request_file = false;
	if ($data) {
		if ($join) {
			foreach($data as $k=>$v) {
				list($a,$b) = explode("=",trim($v),2);
				$data[$k] = $a."=".urlencode($b);
			}
		}

		$request_file = func_temp_store(join($join,$data));
		$execline .= " -d ".func_shellquote('@'.$request_file);
	}

	# Add SSL Certificate
	if ($cert) {
		$execline.= " --cert ".func_shellquote($cert);

		# Add SSL Key-Certificate
		if ($kcert)
			$execline.= " --key ".func_shellquote($kcert);
	}

	if ($supports_insecure )
		$execline.= " -k ";

	if ($cookie)
		$execline.=" --cookie ".func_shellquote(join(';',$cookie));

	# Add Content-Type...
	if ($conttype != "application/x-www-form-urlencoded") {
		$execline.=" -H ".func_shellquote('Content-Type: '.$conttype);
	}

	# Add referer
	if ($referer != "") {
		$execline.=" -H ".func_shellquote('Referer: '.$referer);
	}

	# Additional headers
	if ($headers != "") {
		foreach ($headers as $k=>$v) {
			if (is_integer($k)) {
				$execline .= " -H \"".addslashes($v)."\"";
			}
			else {
				$execline .= " -H \"$k: ".addslashes($v)."\"";
			}
		}
	}

	$fp = popen($execline." ".func_shellquote($url)." 2>".func_shellquote($tmpfile), "r");
	if (!$fp) {
		@unlink($tmpfile);
		return array(0, "X-Cart HTTPS: curl execution failed");
	}

	$res = func_https_receive_result($fp);

	pclose($fp);
	@unlink($tmpfile);
	if ($request_file !== false)
		@unlink($request_file);

	func_https_ctl('PUT', $res);

	return $res;
}
