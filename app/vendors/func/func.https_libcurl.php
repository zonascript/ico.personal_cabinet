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

# OUTPUT:
#
# array($headers,$body );

## There is a bug in cURL version 7.9.4 that can cause problems form posts.
## http://curl.haxx.se/mail/lib-2002-02/0029.html

function __curl_headers() {
	static $headers = "";

	$args = func_get_args();
	if (count($args) == 1) {
		$return = "";
		if ($args[0] == true) $return = $headers;
		$headers = "";
		return $return;
	}

	if (trim($args[1]) != "") $headers .= $args[1];
	return strlen($args[1]);
}

function func_https_request_libcurl($method, $url, $data="", $join="&", $cookie="", $conttype="application/x-www-form-urlencoded", $referer="", $cert="", $kcert="", $headers="", $timeout = 0) {
	global $config;

	if (!function_exists('curl_init'))
		return array("0","X-Cart HTTPS: libcurl is not supported");

	if ($method != "POST" && $method!="GET")
		return array("0","X-Cart HTTPS: Invalid method");

	if (!preg_match("/^(https?:\/\/)(.*\@)?([a-z0-9_\.\-]+):(\d+)(\/.*)$/Ui",$url,$m))
		return array("0","X-Cart HTTPS: Invalid URL");

	if ($headers != '') {
		$_headers = array();
        foreach($headers as $k=>$v) {
			$_headers[] = is_integer($k) ? $v : ($k.": ".$v);
		}
		$headers = $_headers;
		unset($_headers);
	}
	$headers[] = "Content-Type: ".addslashes($conttype);

	$version = curl_version();
	if (is_array($version)) {
		$version = 'libcurl/'.$version['version'];
	}

	$supports_insecure = false;
	# insecure key is supported by curl since version 7.10
	if (preg_match('/libcurl\/([^ $]+)/', $version, $m) ){
		$parts = explode(".",$m[1]);
		if ($parts[0] > 7 || ($parts[0] = 7 && $parts[1] >= 10))
			$supports_insecure = true;
	}

	$ch = curl_init();
	if (!empty($config['General']['https_proxy'])) {
		curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
		curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		curl_setopt ($ch, CURLOPT_PROXY, $config['General']['https_proxy']);
	}
	curl_setopt ($ch, CURLOPT_URL, $url);
	if ($referer)
		curl_setopt ($ch, CURLOPT_REFERER, $referer);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
	if ($cert) {
		curl_setopt ($ch, CURLOPT_SSLCERT, $cert);
		if ($kcert)
			curl_setopt ($ch, CURLOPT_SSLKEY, $kcert);
	}

	if (!empty($cookie))
		curl_setopt ($ch, CURLOPT_COOKIE, implode("; ", $cookie));

	# Set TimeOut parameter
	$timeout = abs(intval($timeout));
	if (!empty($timeout)) {
		curl_setopt ($ch, CURLOPT_TIMEOUT, $timeout);
	}

	if ($supports_insecure) {
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 1);
	}
	if( $method == 'GET' )
		curl_setopt ($ch, CURLOPT_HTTPGET, 1);
	else {
		curl_setopt ($ch, CURLOPT_POST, 1);
		if($data) {
			if($join){
				foreach($data as $k=>$v){
					list($a,$b) = explode("=",trim($v),2);
					$data[$k]=$a."=".urlencode($b);
				}
			}
			curl_setopt ($ch, CURLOPT_POSTFIELDS, join($join,$data));
		}
	}
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_HEADERFUNCTION, "__curl_headers");
	__curl_headers(false);

	$body = curl_exec ($ch);
	$errno = curl_errno ($ch); $error = curl_error($ch);
	curl_close ($ch);
	if( $error )
		return array("0","X-Cart HTTPS: libcurl error($errno): $error");
	return array(__curl_headers(true), $body);
}
