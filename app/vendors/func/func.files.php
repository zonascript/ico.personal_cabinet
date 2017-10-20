<?php

#
# Get image size abstract function
#
function func_get_image_size($filename, $is_image = false) {
	static $img_types = array (
		"1" => "image/gif",
		"2" => "image/jpeg",
		"3" => "image/png",
		"4" => "application/x-shockwave-flash",
		"5" => "image/psd",
		"6" => "image/bmp",
		"13" => "application/x-shockwave-flash",
	);

	if (empty($filename))
		return false;

	if ($is_image) {
		global $file_temp_dir;
		$size = strlen($filename);
		$filename = func_temp_store($filename);
		if (!$filename)
			return false;
	}

	list($width, $height, $type) = @getimagesize($filename);

	if (!empty($img_types[$type])) {
		$type = $img_types[$type];
	}
	else {
		if ($is_image)
			@unlink($filename);

		return false;
	}

	if ($is_image) {
		@unlink($filename);
	} else {
		$size = func_filesize($filename);
	}

	return array(intval($size),$width,$height,$type);
}

#
# Determine that $userfile is image file with non zero size
#
function func_is_image_userfile($userfile, $userfile_size, $userfile_type) {
	return ($userfile != "none")
		&& ($userfile != "")
		&& ($userfile_size > 0)
		&& (substr($userfile_type, 0, 6) == 'image/' || $userfile_type == 'application/x-shockwave-flash');
}

#
# Recursively deletes category with all its contents
#
function func_rm_dir_files ($path) {
	$dir = @opendir ($path);
	if (!$dir)
		return false;

	while ($file = readdir ($dir)) {
		if (($file == ".") || ($file == ".."))
			continue;

		if (filetype ("$path/$file") == "dir") {
			func_rm_dir("$path/$file");

		} else {
			@unlink ("$path/$file");
		}
	}

	closedir($dir);
}

function func_rm_dir ($path) {
	func_rm_dir_files ($path);
	@rmdir ($path);
}

#
# This function compare file extension with disallowed extensions list
#
function func_is_allowed_file($file) {
	global $config;

	$info = pathinfo($file);

	# usage of 'S' preg flag do not give any additional speed
	return !preg_match('!,\s*'.preg_quote($info['extension'],'!').'\s*,!Ui',
		','.$config["Security"]["disallowed_file_exts"].',');
}

#
# Emulator for the is_executable function if it doesn't exists (f.e. under windows)
#
function func_is_executable($file) {
	$count = 0;
	while (strlen($file) > 0 && @file_exists($file) && @is_link($file) && $count < 2) {
		$file = @readlink($file);
		$count++;
	}

	if (function_exists("is_executable"))
		return @is_executable($file);

	return @is_readable($file);
}

#
# Executable lookup
# Check prefered file first, then do search in PATH environment variable.
# Will return false if no executable is found.
#
function func_find_executable($filename, $prefered_file = false) {
	global $xcart_dir;

	if (ini_get("open_basedir") != "" && !empty($prefered_file))
		return $prefered_file;

	$path_sep = X_DEF_OS_WINDOWS ? ';' : ':';

	if ($prefered_file) {
		if (!X_DEF_OS_WINDOWS && func_is_executable($prefered_file))
			return $prefered_file;

		if (X_DEF_OS_WINDOWS) {
			$info = pathinfo($prefered_file);
			if (empty($info["extension"])) $prefered_file .= ".exe";
			if (func_is_executable($prefered_file)) return $prefered_file;
		}
	}

	$directories = preg_split("/$path_sep/", getenv("PATH"));
	array_unshift($directories, $xcart_dir.DIRECTORY_SEPARATOR."payment");

	foreach ($directories as $dir){
		$file = $dir.DIRECTORY_SEPARATOR.$filename;
		if (!X_DEF_OS_WINDOWS && func_is_executable($file) ) return $file;
		if (X_DEF_OS_WINDOWS && func_is_executable($file.".exe") ) return $file.".exe";
	}

	return false;
}

#
# Get thumbnail URL (if images are stored on the FS only)
#
function func_get_image_url($id, $type = 'T', $image_path = false) {
	global $config, $sql_tbl, $xcart_dir, $current_location, $HTTPS, $current_area;

	if (zerolen($image_path)) {
		$field = ($config['available_images'][$type] == "U") ? "id" : "imageid";
		$info = func_query_first("SELECT filename,image_path,(image IS NOT NULL AND LENGTH(image)>0) AS in_db FROM ".$sql_tbl['images_'.$type]." WHERE $field='$id'");

		# content of image ($image) takes precedence on
		# reading file from filesystem ($image_path)
		if ($info['in_db'] || zerolen($info['filename']))
			return false;

		$image_path = $info['image_path'];
		if (zerolen($image_path)) {
			x_load('image');
			$image_path = func_image_dir($type)."/".$info['filename'];
		}
	}

#
##
###
	if ($current_area == "C" && !$HTTPS && !empty($image_path) && !empty($config["Appearance"]["CDN_domain"]) && $config["Appearance"]["Enable_CDN"] == "Y" && strpos($image_path, "./")!== false){

		$tmp_full_image_path = str_replace("./", $xcart_dir."/", $image_path);

		if (@file_exists($tmp_full_image_path)){



			//$image_path = str_replace("./", $config["Appearance"]["CDN_domain"]."/", $image_path);
			$image_path = ltrim($image_path, '.');

			###  https://basecamp.com/2070980/projects/1577907/messages/39190109
			global $current_storefront;
			if ($current_storefront == "" && strpos($image_path, "://") === false && strpos($image_path, "cdn.") !== false){
				$image_path = "http://".$image_path;
			}
			###

			return $image_path;
		}
	}
###
##
#


	if (!empty($image_path)) {
		# image_path is an URL
		return ltrim($image_path, '.');
	}

	$image_path = func_realpath($image_path);
	
	if (!strncmp($xcart_dir, $image_path, strlen($xcart_dir)) && @file_exists($image_path)) {
		# image_path is an locally placed image
		return $current_location.str_replace(DIRECTORY_SEPARATOR, "/", substr($image_path, strlen(preg_replace("/".preg_quote(DIRECTORY_SEPARATOR, "/")."$/S", "", $xcart_dir))));
	}

#
##
###
	$get_default_image = func_get_default_image($type);

        if ($current_area == "C" && !$HTTPS && !empty($get_default_image) && !empty($config["Appearance"]["CDN_domain"]) && $config["Appearance"]["Enable_CDN"] == "Y" && strpos($get_default_image, "default_image.gif")!== false && strpos($get_default_image, $config["Appearance"]["CDN_domain"]) ===  false){
		$get_default_image = $config["Appearance"]["CDN_domain"].$get_default_image;
	}
###
##
#

//if ($type == "T"){
//func_print_r($get_default_image);
//}

//	return func_get_default_image($type);
	return $get_default_image;
}

#
# This function creates a temporary file and store some data in it
# It will return filename if successful and "false" if it fails.
#
function func_temp_store($data) {
	global $file_temp_dir;
	$tmpfile = @tempnam($file_temp_dir,"xctmp");
	if (empty($tmpfile)) return false;

	$fp = @fopen($tmpfile,"w");
	if (!$fp) {
		@unlink($tmpfile);
		return false;
	}

	fwrite($fp,$data);
	fclose($fp);

	return $tmpfile;
}

#
# Get tmpfile content
#
function func_temp_read($tmpfile, $delete = false) {
	if (empty($tmpfile))
		return false;

	$fp = @fopen($tmpfile,"rb");
	if(!$fp)
		return false;

	while (strlen($str = fread($fp, 4096)) > 0 )
		$data .= $str;
	fclose($fp);

	if ($delete) {
		@unlink($tmpfile);
	}

	return $data;
}

#
# realpath() wrapper
#
function func_realpath($path) {
	global $xcart_dir;

	if (X_DEF_OS_WINDOWS && preg_match('!^((?:\\\\\\\\[^\\\\]+)|(?:\w:))(.*)!S', $path, $matched)) {
		# windows paths: \\server\path and DRIVE:\path
		$path = $matched[1].func_normalize_path($matched[2]);
	}
	else {
		# other paths
		if ($path[0] != '/' && $path[0] != '\\')
			$path = $xcart_dir.DIRECTORY_SEPARATOR.$path;

		$path = func_normalize_path($path);

		$cache = array ();
		do {
			$cache[$path] = true; # prevent the loop
			$path = func_resolve_fs_symlinks($path);
			if ($path === false) {
				# cannot resolve, broken path
				return false;
			}
		} while (empty($cache[$path]));
	}

	return $path;
}

#
# Helper function for func_realpath()
# Works only under Unix like operating systems
# Note: will not work when open_basedir is in effect
#
function func_resolve_fs_symlinks($path) {
	if (X_DEF_OS_WINDOWS || strlen($path) < 2 || strlen(ini_get('open_basedir')) > 0)
		return $path;

	$parts = explode('/', substr($path,1));
	$resolved = "";

	$normalize = false;
	while (!empty($parts)) {
		$elem = array_shift($parts);
		if (strlen($elem) == 0)
			continue;

		$resolved .= '/' . $elem;
		if (!file_exists($resolved))
			continue;

		if (is_link($resolved)) {
			$normalize = true;
			$link = readlink($resolved);
			if ($link === false || strlen($link) == 0 || !strcmp($link, $elem)) {
				# cannot resolve, broken path
				return false;
			}

			$link = preg_replace('!/+$!S','',$link);

			if (strlen($link) == 0) {
				$resolved = '/';
			}
			elseif ($link[0] == '/') {
				$resolved = $link;
			}
			else {
				$resolved .= '/../' . $link;
			}
		}
	}

	$path = $resolved;

	if ($normalize)
		$path = func_normalize_path($path);

	return strlen($path) == 0 ? false : $path;
}

#
# This function decide to allow/deny to use path for files
# Returns: full path for the file if path is allowed,
#          'false', if path is not allowed to use.
#
function func_allowed_path($allowed_path, $path) {
	global $xcart_dir;

	if (empty($path)) return false;

	if (X_DEF_OS_WINDOWS) {
		$allowed_path = strtolower($allowed_path);
		$path = strtolower($path);
		$_xcart_dir = strtolower($xcart_dir);
	} else {
		$_xcart_dir = $xcart_dir;
	}

	$allowed_path = func_realpath($allowed_path);
	if (empty($allowed_path) || strncmp($allowed_path, $_xcart_dir, strlen($_xcart_dir)) != 0) return false;

	# absolute path
	if ((X_DEF_OS_WINDOWS && preg_match("/^(\\\\)|(\w:)/S",$path)) || !X_DEF_OS_WINDOWS && $path[0] == '/') {
		$path = func_realpath($path);
	}
	else {
		$path = func_realpath($allowed_path.DIRECTORY_SEPARATOR.$path);
	}

	if (!strcmp($allowed_path, $path))
		return $allowed_path;

	if ($allowed_path[strlen($allowed_path)-1] != DIRECTORY_SEPARATOR)
		$allowed_path .= DIRECTORY_SEPARATOR;

	if (!strncmp($path, $allowed_path, strlen($allowed_path)))
		return $path;

	return false;
}

#
# Check filename for present in X-Cart directory
#
function func_allow_file($file, $is_root = false) {
	global $xcart_dir, $login, $single_mode, $current_area, $active_modules, $files_dir_name;

	if (empty($file) || !func_is_allowed_file($file))
		return false;

	if (!is_url($file)) {
		$dir = $xcart_dir;
		if (!$is_root) {
			if ($current_area=="A" || (($active_modules["Simple_Mode"] || $single_mode)&& $current_area=="P")) {
				$dir = $files_dir_name;
			}
			elseif ($current_area=="P" || $current_area == 'A') {
				$dir = $files_dir_name.DIRECTORY_SEPARATOR.$login;
			}
			else {
				$dir = $files_dir_name;
			}
		}

		$file = func_allowed_path($dir, $file);
	}

	return $file;
}

#
# fopen() wrapper
#
function func_fopen($file, $perm = 'r', $is_root = false) {
	$file = func_allow_file($file, $is_root);
	if ($file === false)
		return false;

	return @fopen($file, $perm);
}

#
# fopen + fread wrapper
#
function func_file_get($file, $is_root = false) {
	$fp = func_fopen($file, 'rb', $is_root);

	if ($fp === false) return false;

	while (strlen($str = fread($fp, 8192)) > 0 )
		$data .= $str;

	fclose($fp);
	return $data;
}

#
# readfile() wrapper
#
function func_readfile($file, $is_root = false) {
	$file = func_allow_file($file, $is_root);
	if ($file === false) return false;

	return readfile($file);
}

#
# move_uploaded_file() wrapper
#
function func_move_uploaded_file($file) {
	global $_FILES, $file_temp_dir;

    if (empty($file) || !isset($_FILES[$file])) {
		return false;
    }

	$path = func_allow_file(tempnam($file_temp_dir,preg_replace('/^.*[\/\\\]/S', '', $_FILES[$file]['name'])), true);
	if ($path === false)
		return false;

    if (move_uploaded_file($_FILES[$file]['tmp_name'], $path)) {
		return $path;
    }

	@chmod($path, 0644);

	return false;
}

#
# file() wrapper
#
function func_file($file, $is_root = false) {
	$file = func_allow_file($file, $is_root);
	if ($file === false) return array();
	
	$result = @file($file);

	return (is_array($result) ? $result : array());
}

#
# Normalize path: remove "../", "./" and duplicated slashes
#
function func_normalize_path($path, $separator=DIRECTORY_SEPARATOR) {
	$qs = preg_quote($separator,'!');
	$path = preg_replace("/[\\\\\/]+/S",$separator,$path);
	$path = preg_replace("!".$qs."\.".$qs."!S", $separator, $path);

	$regexp = "!".$qs."[^".$qs."]+".$qs."\.\.".$qs."!S";
	for ($old="", $prev="1"; $old != $path; $path = preg_replace($regexp, $separator, $path)) {
		$old = $path;
	}

	return $path;
}

#
# Create path to file/directory relating to $home_dir
#
function func_relative_path($dir, $home_dir = false) {
	global $xcart_dir;

	if (empty($dir))
		return false;

	if ($home_dir === false)
		$home_dir = $xcart_dir;

	$home_dir = preg_replace("/".preg_quote(DIRECTORY_SEPARATOR, "/")."$/", '', $home_dir);

	$dir = func_realpath($dir);
	$is_dir = is_dir($dir);

	# Get paths as arrays
	$d = explode(DIRECTORY_SEPARATOR, $is_dir ? $dir : dirname($dir));
	$h = explode(DIRECTORY_SEPARATOR, $home_dir);
	$dir_disc = strtoupper(array_shift($d));
	$home_disc = strtoupper(array_shift($h));

	if (X_DEF_OS_WINDOWS) {

		# Check disk letters
		if (($dir_disc !== $home_disc)) {
			return false;

		# Check net devies names
		} elseif ((empty($dir_disc) && empty($home_disc) && empty($d[0]) && empty($h[0]))) {
			array_shift($d);
			array_shift($h);
			$dir_disc = array_shift($d);
			$home_disc = array_shift($h);
			if ($dir_disc != $home_disc)
				return false;

		}
	}

	$max = count($h);
	if (count($d) < $max)
		$max = count($d);

	# Define equal root for both paths
	$root = 0;
	for ($x = 0; $x < $max; $x++) {
		if ($d[$x] !== $h[$x])
			break;
		$root++;
	}

	# Build prefix (return from home dir to destination dir) for result path
	$prefix_str = str_repeat("..".DIRECTORY_SEPARATOR, count($h)-$root);
	if (empty($prefix_str)) {
		$prefix_str = ".".DIRECTORY_SEPARATOR;
	}

	# Remove root from destination dir
	if ($root > 0) {
		array_splice($d, 0, $root);
	}

	if (!empty($d)) {
		$prefix_str .= implode(DIRECTORY_SEPARATOR, $d).DIRECTORY_SEPARATOR;
	}

	if (!$is_dir) {
		$prefix_str .= basename($dir);
	}

	return $prefix_str;
}

function is_url($url) {
	if (empty($url))
		return false;

	return preg_match("/^(http|https|ftp):\/\//iS", $url);
}

function func_get_image_type($image_type) {
	static $imgtypes = array (
		"/gif/i" => "GIF",
		"/jpg|jpeg/i" => "JPEG",
		"/png/i" => "PNG",
		"/bmp/i" => "BMP");

	foreach ($imgtypes as $k=>$v) {
		if (preg_match($k, $image_type))
			return $v;
	}

	return "JPEG";
}

#
# Determine size of local or http:// file
#
function func_filesize($file) {
	clearstatcache(); # without can return zero for just uploaded, non-zero size and exists files (affected: PHP 4.4.0 CGI).
	if (!is_url($file))
		return @filesize($file);

	$host = parse_url($file);
	if ($host['scheme'] != 'http')
		return false;

	if (empty($host['port']))
		$host['port'] = 80;

	$fp = fsockopen($host['host'], $host['port'], $errno, $errstr, 30);
	if (!$fp)
		return false;

	fputs ($fp, "HEAD $host[path]?$host[query] HTTP/1.0\r\n");
	fputs ($fp, "Host: $host[host]:$host[port]\r\n");
	fputs ($fp, "User-Agent: Mozilla/4.5 [en]\r\n");
	fputs ($fp,"\r\n");

	$err = chop(fgets($fp, 4096));
	if (strpos($err, " 200 ") === false)
		return false;

	$header_passed = false;
	$len = false;
	while (!feof($fp)) {
		$line = fgets($fp, 4096);
		if ($line == "\n" || $line == "\r\n") {
			break;
		}

		$header_line = explode(": ", $line, 2);
		if (strtoupper($header_line[0]) == 'CONTENT-LENGTH') {
			$len = (int)trim($header_line[1]);
			break;
		}
	}

	fclose($fp);

	if ($len === false) {
		if($fp = func_fopen($file, 'rb')) {
			while (strlen($str = fread($fp, 8192)) > 0) {
				$len += strlen($str);
			}

			fclose($fp);
		}
	}

	return $len;
}

function func_is_full_path($path) {
	return (is_url($path) || (X_DEF_OS_WINDOWS && preg_match("/^(?:\w:\\\)|^(?:\\\\\\\\\\w+\\\)/S", $path)) || (!X_DEF_OS_WINDOWS && preg_match("/^\//S", $path)));
}

#
# mkdir() wrapper (recursive version)
#
function func_mkdir($dir, $mode = 0777) {
	$dir = func_realpath($dir);

	$dirstack = array();

	while (!@is_dir($dir) && $dir != '/') {
		if ($dir != ".") {
			array_unshift($dirstack, $dir);
		}

		$dir = dirname($dir);
	}

	while ($newdir = array_shift($dirstack)) {
		if (substr($newdir, -2) == "..") continue;

		umask(0000);
		if (!@mkdir($newdir, $mode)) {
			return false;
		}
	}

	return true;
}

#
# DIRECTORY_SEPARATOR independent comparison of paths
# $use_len = 1 # for path1
# $use_len = 2 # for path2
# $use_len = NULL # for exact match
#
function func_pathcmp($path1, $path2, $use_len=NULL) {
	static $func_defs = array (
		0 => array ('strcmp', 'strncmp'),
		1 => array ('strcasecmp', 'strncasecmp')
		);

	$index = (int)(X_DEF_OS_WINDOWS);
	$func = $func_defs[$index];

	$path1 = func_normalize_path($path1);
	$path2 = func_normalize_path($path2);

	if (is_null($use_len))
		return !$func[0]($path1, $path2);

	$len = ($use_len == 1) ? strlen($path1) : strlen($path2);

	return !$func[1]($path1, $path2, $len);
}

#
# This function is a wrapper for md5_file() function
# Note: $mode is used in admin/snapshots.php script
#
function func_md5_file($file, $mode=1) {
	if ($mode == "2") {
		global $files_md5_list;

		return $files_md5_list[$file];
	}

	if (phpversion() > "4.2.0" && !is_url($file)) {
		return @md5_file($file);
	}
	elseif ($fd = @fopen($file, "r")) {
		while (strlen($str = fread($fd, 8192)) > 0) {
			$content .= $str;
		}

		fclose($fd);

		return md5($content);
	}

	return false;
}

#
# Copy file to new location
#
function func_transfer_file($from, $to, $is_root) {
	if (isset($from) && isset($to)) {
	
		$fto = func_fopen($to, "wb", true);
		if ($fto === false) {
			# ERROR: cannot continue
			return false;
		}
		
		$from_data = func_file_get($from, $is_root);
		if (empty($from_data)) {
			unlink($to);
			return false;
		}

		fwrite($fto, $from_data);
		fclose($fto);
		@chmod($fto, 0644);

		unlink($from);

		return true;
	}
	return false;
}
