<?php

#
# This function filter the filename
#
function f_file_filter ($file) {
	global $smarty;
	$return = false;

	if (!strstr(dirname($file), $smarty->compile_dir) && !preg_match("!md5_[0-9]+log\.php!S", basename($file)))
		$return = true;

	return $return;
}

#
# This function generates the MD5 file name by timestamp
#
function f_get_md5file_name($time) {
	global $var_dirs;

	return $var_dirs["log"]."/md5_".$time."log.php";
}

#
# This function generates the list of files in the $dirname directory
#
function f_get_filelist ($dirname) {
	global $files_list, $md5file, $smarty;

	$fd = @opendir($dirname);
	while ($file = @readdir($fd)) {
		if (in_array($file, array(".", "..")) || preg_match("!md5_[0-9]+log\.php!S", $file) || strstr($dirname, $smarty->compile_dir))
			continue;
		if (is_dir($dirname.DIRECTORY_SEPARATOR.$file))
			f_get_filelist ($dirname.DIRECTORY_SEPARATOR.$file);
		else
			$files_list[] = $dirname.DIRECTORY_SEPARATOR.$file;
	}
	@closedir($fd);

	return $files_list;
}

#
# This function updates/inserts option 'snapshots' in xcart_config table
#
function f_update_snapshots ($value) {
	global $sql_tbl;

	db_query("REPLACE INTO $sql_tbl[config] (name, value) VALUES ('snapshots', '".addslashes(serialize($value))."')");
}

#
# This functin processes file (get MD5)
#
function f_process_file ($file) {
	global $file_log;

	if ($md5 = func_md5_file($file))
		$file_log[$file] = $md5;
	else
		$file_log[$file] = ($md5 = "R");

	return $md5;
}

#
# This function generates the snapshot and writes it to the file
#
function func_generate_snapshot($file_to_write, $install=false) {
	global $xcart_dir, $file_log;

	$src_name = $xcart_dir;

	$return["error"] = 0;

	$file_log = array();

	$files_list = f_get_filelist($src_name);

	if (is_array($files_list)) {

		$unknown_files = 0;
		$counter = 0;

		if ($fc = fopen($file_to_write, "w+")) {
			fwrite($fc, "<?php exit; ?>\n");

			foreach ($files_list as $file) {
				if (is_dir($file))
					continue;

				if (f_file_filter($file)) {
					# Process file...
					$res = f_process_file($file);

					# Write to the fingerprint file...
					$fname = preg_replace("/^".preg_quote($src_name,"/")."/", "", $file);
					fwrite($fc, base64_encode($fname).":".$file_log[$file]."\n");
					if ($install) # Empty $file_log when installing to avoid memory exceed
						$file_log = array();

					if ($res == "R")
						$unknown_files++;

					if (!(++$counter % 50)) {
						echo ".";
						func_flush();
					}
				}
			}

			fclose($fc);

			echo "<br />";

			$return["total_files"] = count($file_log);
			$return["unprocessed_files"] = $unknown_files;
		}
		else {
			$return["error"] = 1;
			$return["errordescr"] = "snpst_write_file";
		}
	}
	else {
		$return["error"] = 1;
		$return["errordescr"] = "snpst_no_files";
	}

	return $return;
}
