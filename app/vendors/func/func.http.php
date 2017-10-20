<?php

#
# Extract cookies from headers.
# Note: $cookies array should contain only "SET-COOKIE" headers.
#
function func_parse_cookie_array(&$http_header, $cookies) {
	$deleted = array();
	$valid = array();
	foreach ($cookies as $line) {
		if (!preg_match_all('!^\s*([^\n\r=]+)=([^\r\n; ]+)?!S', $line, $m))
			continue;

		if (empty($m[1]) || !is_array($m[1]))
			continue;

		foreach ($m[1] as $k=>$v) {
			if ($m[2][$k] == 'deleted') {
				$deleted[$v] = true;
				if (isset($valid[$v]))
					unset($valid[$v]);
			}
			else {
				$valid[$v] = $m[2][$k];
				if (isset($deleted[$v]))
					unset($deleted[$v]);
			}
		}
	}

	$http_header['cookies_deleted'] = $deleted;
	$http_header['cookies'] = $valid;
}

function func_http_get_request($host, $post_url, $post_str, $post_cookies=array()) {
	$hp = explode(':',$host);

	$cookie = "";

	$result = "";
	$header_passed = false;

	if (!isset($hp[1]) || !is_numeric($hp[1])) $hp[1] = 80;

	$host = $hp[1] == 80 ? $hp[0] : implode(':', $hp);

	$fp = fsockopen($hp[0], $hp[1], $errno, $errstr, 30);
	if (!$fp) {
		return array ("", "");
	}
	else {
		fputs ($fp, "GET $post_url?$post_str HTTP/1.0\r\n");
		fputs ($fp, "Host: $host\r\n");
		fputs ($fp, "User-Agent: Mozilla/4.5 [en]\r\n");
		if (!empty($post_cookies))
			fputs ($fp, "Cookie: ".join('; ',$post_cookies)."\r\n");

		fputs ($fp,"\r\n");

		$http_header = array ();
		$http_header["ERROR"] = chop(fgets($fp,4096));
		$cookies = array ();

		while (!feof($fp)) {
			if (!$header_passed)
				$line = fgets($fp, 4096);
			else
				$result .= fread($fp, 65536);

			if ($header_passed == false && ($line == "\n" || $line == "\r\n")) {
				$header_passed = true;
				continue;
			}

			if ($header_passed == false) {
				$header_line = explode(": ", $line, 2);
				$header_line[0] = strtoupper($header_line[0]);
				$http_header[$header_line[0]] = chop($header_line[1]);

				if ($header_line[0] == 'SET-COOKIE')
					array_push($cookies, chop($header_line[1]));
			}
		}

		fclose($fp);
	}

	func_parse_cookie_array($http_header, $cookies);

	return array($http_header, $result);
}

function func_http_post_request($host, $post_url, $post_str, $cook = "") {
	$hp = explode(':',$host);

	$result = "";
	$header_passed = false;

	if (!isset($hp[1]) || !is_numeric($hp[1])) $hp[1] = 80;

	$host = $hp[1] == 80 ? $hp[0] : implode(':', $hp);

	$fp = fsockopen($hp[0], $hp[1], $errno, $errstr, 30);
	if (!$fp) {
		return array ("", "");
	}
	else {
		fputs($fp, "POST http://$host$post_url HTTP/1.0\r\n");
		fputs($fp, "Host: $host\r\n");

		if (!empty($cook))
			fputs($fp, "Cookie: ".$cook."\r\n");

		fputs($fp, "User-Agent: Mozilla/4.5 [en]\r\n");
		fputs($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
		fputs($fp, "Content-Length: ".strlen($post_str)."\r\n");
		fputs($fp, "\r\n");
		fputs($fp, $post_str."\r\n\r\n");

		$http_header = array();
		$http_header["ERROR"] = chop(fgets($fp,4096));

		$cookies = array();
		while (!feof($fp)) {
			$line = fgets($fp,4096);

			if ($header_passed == false && ($line == "\n" || $line == "\r\n")) {
				$header_passed = true;
				continue;
			}

			if ($header_passed == false) {
				$header_line = explode(": ", $line, 2);
				$header_line[0] = strtoupper($header_line[0]);
				$http_header[$header_line[0]] = chop($header_line[1]);

				if ($header_line[0] == 'SET-COOKIE')
					array_push($cookies, chop($header_line[1]));

				continue;
			}

			$result .= $line;
		}

		fclose ($fp);
	}

	func_parse_cookie_array($http_header, $cookies);

	return array($http_header, $result, $cookies);
}

#
# Prepare request for HTTPS request
#
function func_https_prepare_request($method, $parsed_url, $data="", $join="&", $cookie="", $conttype="application/x-www-form-urlencoded", $referer="", $headers="") {
	if ($parsed_url["query"])
		$parsed_url["location"] = $parsed_url["path"]."?".$parsed_url["query"];
	else
		$parsed_url["location"] = $parsed_url["path"];

	#
	# HTTP/1.0 protocol (RFC1945) does not support the "Host:" header,
	# so HTTP/1.0 server should ignore them. Currently all
	# HTTP/1.1 (RFC2616) servers accept these headers without errors.
	#
	# "Host:" header is important for virtual "name-based" hostings.
	#
	$request = array();
	$request[] = $method." ".$parsed_url["location"]." HTTP/1.0";
	$request[] = "Host: ".$parsed_url["host"];
	$request[] = "User-Agent: Mozilla/4.5 [en]";

	# Additional headers
	if ($headers != "") {
		foreach($headers as $k=>$v) {
			if (is_integer($k)) {
				$request[] = $v;
			}
			else {
				$request[] =$k.": ".$v;
			}
		}
	}

	if ($method == 'POST') {
		if ($data) {
			if ($join) {
				foreach ($data as $k=>$v){
					list($a,$b) = explode("=",trim($v),2);
					$data[$k]=$a."=".urlencode($b);
				}
			}

			if (is_array($data))
				$data = join($join,$data);
		}

		$request[] = "Content-Type: $conttype";
		$request[] = "Content-Length: ".strlen($data);
	}

	if ($cookie)
		$request[] = "Cookie: ".join('; ',$cookie);

	if ($referer)
		$request[] = "Referer: $referer";

	if (!empty($parsed_url["user"]) && !empty($parsed_url["pass"]))
		$request[] = "Authorization: Basic ".base64_encode($parsed_url["user"].":".$parsed_url["pass"]);

	$request[] = "";
	if ($method == 'POST') {
		$request[] =  $data;
		$request[] = "";

	}

	$request[] = "";

	return join("\r\n",$request);
}

#
# Receive response from pipe and separate it into headers and data
#
function func_https_receive_result($connection) {
	$get_state = 0;
	$headers = "";
	$result = "";
	$debug = "";
	while (!feof($connection)) {
		$line = fgets($connection, 65536);
		switch ($get_state){
		case 0: # strip out any possible debug output
			if (!strncmp($line, 'HTTP', 4)) {
				$debug .= $line;
				break;
			}
			# FALL-THROUGH
		case 1: # get headers
			if (trim($line) === "" ) { # end of headers
				$get_state = 2;
			}
			else {
				$headers .= $line;
				$get_state = 1;
			}
			break;
		case 2: # get data
			$result .= $line;
		}
	}

	if (empty($headers))
		return array("0",$debug);
	else
		return array($debug.$headers, $result);
}

#
# Generic transport function for HTTPS
#
function func_https_tunnel_request($connection, $method, $parsed_url, $data="", $join="&", $cookie="", $conttype="application/x-www-form-urlencoded", $referer="", $headers="") {
	$request = func_https_prepare_request($method, $parsed_url,$data,$join,$cookie,$conttype,$referer,$headers);
	fputs($connection, $request);

	return func_https_receive_result($connection);
}

#
# Control function for HTTPS subsystem
#
# Currently used as internal buffer. Contents of internal buffer are used for
# logging reasons later. (e.g. when payment transaction is failed)
#
# Available commands:
#  PUT - store content in internal buffer
#  GET - get content from internal buffer
#  IGNORE - ignore 'PUT' commands
#  STORE - do not ignore 'PUT' commands
#  PURGE - clean internal buffer
#
function func_https_ctl($command, $arg=false) {
	static $responses = array();
	static $store_responses = true;

	switch ($command) {
	case 'GET':
		return $responses;
	case 'PUT':
		if ($store_responses) {
			list($sec, $usec) = explode(' ', microtime());
			$label = date('d-m-Y H:i:s', $sec).' '.$usec;
			$responses[$label] = $arg;
		}
		return true;
	case 'PURGE':
		$responses = array();
		break;
	case 'STORE':
		$store_responses = true;
		break;
	case 'IGNORE':
		$store_responses = false;
		break;
	}

	return false;
}

#
# Perform HTTPS request using GET or POST methods
# For full list of parameters see include/func/func.https_*.php
#
function func_https_request() {
	global $httpsmod_active;

	if (is_null($httpsmod_active) || !isset($httpsmod_active)) {
		x_load('tests');
		$httpsmod_active = test_active_bouncer();
	}

	if (!empty($httpsmod_active))
		x_load('https_'.$httpsmod_active);

	$func = 'func_https_request_'.$httpsmod_active;
	if (empty($httpsmod_active) || !function_exists($func)) {
		$result = array("0","X-Cart HTTPS: could not find suitable HTTPS module to commit secure transaction.");
	}
	else {
		$args = func_get_args();
		$args[1] = func_https_fix_url($args[1]); # fix URL (e.g. add :443). for details see include/func/func.https_*.php
		$result = call_user_func_array($func, $args);
	}

	func_https_ctl('PUT', $result);

	return $result;
}

#
# Correct function URL for https modules. Work only with "https://" scheme.
# Currently add port (:443) when not mentioned
#
function func_https_fix_url($url) {
	$p = parse_url($url);
	if (empty($p['scheme']) || $p['scheme'] != 'https') return false;

	if (empty($p['port'])) $p['port'] = 443;
	if (empty($p['path'])) $p['path'] = '/';

	$r = 'https://';
	if (!empty($p['user'])) {
		$r .= $p['user'];
		if (!empty($p['pass'])) $r .= ':'.$p['pass'];
		$r .= '@';
	}

	$r .= $p['host'].':'.$p['port'].$p['path'];
	if (!empty($p['query'])) $r .= '?'.$p['query'];
	if (!empty($p['fragment'])) $r .= '#'.$p['fragment'];

	return $r;
}
