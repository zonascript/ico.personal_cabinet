<?php

function text_crypt($s, $type = "B", $key = false) {
	global $blowfish, $encryption_types;

	if (strlen($s) == 0)
		return $s;

	if (!in_array((string)$type, $encryption_types))
		$type = "B";

	$s = trim($s);
	$s .= func_crc32(md5($s));

	if ($type == "B" || $type == "C") {
		# Blowfish
		if ($key === false)
			$key = func_get_crypt_key($type);

		if (!$blowfish || empty($key))
			return $s;

		$s = func_bf_crypt($s, $key);

	}

	return $type."-".$s;
}

function text_decrypt($s, $key = false) {
	global $blowfish;

	if (strlen($s) == 0)
		return $s;

	# Parse crypted data
	$type = func_get_crypt_type($s);
	if ($type === false) {
		$type = "N";
		$crc32 = false;

	} elseif (substr($s, 1, 1) == '-') {
		$crc32 = true;
		$s = substr($s, 2);

	} else {
		$crc32 = substr($s, 1, 8);
		$s = substr($s, 9);
	}

	# Blowfish
	if ($type == 'B' || $type == 'C') {
		if ($key === false)
			$key = func_get_crypt_key($type);

		if (!$blowfish) {
			x_log_flag("log_decrypt_errors", "DECRYPT", "The Blowfish service object is missing", true);
			return false;

		} elseif (empty($key)) {
			x_log_flag("log_decrypt_errors", "DECRYPT", "The key for the selected type ('".$type."') of encryption is missing", true);
			return false;
		}

		$result = trim(func_bf_decrypt($s, $key));

	} elseif ($type == 'N') {
		# Non-encrypted
		$result = $s;
	}

	# CRC32 check
	if ($crc32 === true) {
		# Inner CRC32
		$crc32 = substr($result, -8);
		$result = substr($result, 0, -8);
		if (func_crc32(md5($result)) != $crc32)
			$result = NULL;

	} elseif ($crc32 !== false) {
		# Outer CRC32
		if (func_crc32($result) != $crc32)
			$result = NULL;
	}

	return $result;
}

#
# Get encryptiond/decrtyption key
#
function func_get_crypt_key($type) {
	global $blowfish_key, $merchant_password;

	if ($type == 'B') {
		return $blowfish_key;

	} elseif ($type == "C") {
		x_load('order');
		return func_check_merchant_password() ? $merchant_password : false;
	}

	return false;
}

#
# Get CRC32 as HEX representation of integer
#
function func_crc32($str) {
	$crc32 = crc32($str);
	if (crc32("test") != -662733300 && $crc32 > 2147483647)
		$crc32 -= 4294967296;
	$hex = dechex(abs($crc32));

	return str_repeat("0", 8-strlen($hex)).$hex;
}

#
# Get crypted string type
#
function func_get_crypt_type($str) {
	global $encryption_types;

	$s = substr($str, 0, 1);

	if (!in_array((string)$s, $encryption_types))
		$s = false;

	return $s;
}
