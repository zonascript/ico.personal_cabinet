<?php

#
# Construct path to directory of images of type $type
#
function func_image_dir($type) {
	global $xcart_dir;

	$dir = $xcart_dir."/images/".$type;
	if (!is_dir($dir) && file_exists($dir))
		unlink($dir);

	if (!file_exists($dir))
		func_mkdir($dir);

	return $dir;
}

#
# Get image file extension using mime type of image
#
function func_get_image_ext($mime_type) {
	static $corrected = array (
		"application/x-shockwave-flash" => "swf"
	);

	if (!empty($corrected[$mime_type]))
		return $corrected[$mime_type];

	if (!zerolen($mime_type)) {
		list($type, $subtype) = explode('/', $mime_type, 2);
		if (!strcmp($type, "image") && !zerolen($subtype))
			return $subtype;
	}

	return "img"; # unknown generic file extension
}

#
# Check uniqueness of image filename
#
function func_image_filename_is_unique($file, $type, $imageid=false) {
	global $config, $sql_tbl, $xcart_dir;

	if (empty($config['available_images'][$type]) || empty($config['setup_images'][$type])) {
		# ERROR: unknown or not aavailable image type
		return false;
	}

	$_table = $sql_tbl['images_'.$type];
	$_where = "filename='".addslashes($file)."'";
	if (!empty($imageid)) {
		# ignore ourself
		$_where .= " AND imageid<>'".addslashes($imageid)."'";
	}

	if (func_query_first_cell("SELECT COUNT(*) FROM ".$_table." WHERE ".$_where) > 0)
		return false;

	return !@file_exists(func_image_dir($type)."/".$file);
}

#
# Generate unique filename for image in directory defined for $type
# and corresponding database table
#
function func_image_gen_unique_filename($file_name, $type, $mime_type="image/jpg", $id=false, $imageid=false) {
	static $max_added_idx = 99999;
	static $last_max_idx = array();

	if (zerolen($file_name)) {
		# File name is empty
		$file_name = strtolower($type);
		if (!zerolen((string)$id))
			$file_name .= "-".$id."-".$imageid;

		$file_ext = func_get_image_ext($mime_type);

	} elseif (preg_match("/^(.+)\.([^\.]+)$/S", $file_name, $match)) {
		# Detect file extension
		$file_name = $match[1];
		$file_ext = $match[2];
	}

	$is_unique = func_image_filename_is_unique($file_name.".".$file_ext, $type, $imageid);

	if ($is_unique)
		return $file_name.".".$file_ext;

	# Generate unique name
	$idx = isset($last_max_idx[$type][$file_name]) ? $last_max_idx[$type][$file_name] : func_get_next_unique_id($file_name, $type);
	$name_tmp = $file_name;
	$dest_dir = func_image_dir($type);
	do {
		$file_name = sprintf("%s-%02d", $name_tmp, $idx++);

		$is_unique = func_image_filename_is_unique($file_name.".".$file_ext, $type, $imageid);
	} while (!$is_unique && $idx < $max_added_idx);

	if (!$is_unique) {
		# ERROR: cannot generate unique name
#
##
###
		$file_name = "img_".time();
		return $file_name.".".$file_ext;
###
##
#

		return false;
	}

	if ($idx > 2) {

		# Save last suffix
		if (!isset($last_max_idx[$type]))
			$last_max_idx[$type] = array();
		$last_max_idx[$type][$name_tmp] = $idx-1;
	}

	return $file_name.".".$file_ext;
}

#
# Get last unique id for image file name
#
function func_get_next_unique_id($file, $type) {
	global $config, $sql_tbl, $xcart_dir;

	$max = 1;
	if (empty($config['available_images'][$type]) || empty($config['setup_images'][$type])) {
		# ERROR: unknown or not aavailable image type
		return $max;
	}

	$res = db_query("SELECT filename FROM ".$sql_tbl['images_'.$type]." WHERE SUBSTRING(filename, 1, ".(strlen($file)+1).") = '".addslashes($file)."-'");
	if ($res) {

		while ($f = db_fetch_array($res)) {
			$f = substr(array_pop($f), strlen($file)+1);
			if (preg_match("/^(\d+)/S", $f, $match) && $max < intval($match[1]))
				$max = intval($match[1]);

		}
		db_free_result($res);
		if ($max > 1)
			$max++;

		return $max;
	}

    return $max;
}


#
# Move images of $type to the new location (generic function)
#
function func_move_images($type, $config_data) {
	global $sql_tbl, $config, $images_step, $str_out, $xcart_dir;

	if (zerolen($type, $config_data['location'])) {
		return false;
	}

	$image_table = $sql_tbl['images_'.$type];
	$count = func_query_first_cell("SELECT COUNT(*) FROM ".$image_table);
	if (!$count)
		return true; # success

	#
	# Transfer images by $images_step per pass
	#
	$move_functions = array (
		"FS" => "func_move_images_to_fs",
		"DB" => "func_move_images_to_db"
	);

	$move_func = $move_functions[$config_data['location']];

	$error = false;
	# $rec_no used for displaying dots
	for ($rec_no=0, $pos=0; $pos < $count && !$error; $pos+=$images_step) {
		$sd = db_query("SELECT * FROM ".$image_table." LIMIT $pos,$images_step");

		$error = $error || ($sd === false);
		if (!$sd || !function_exists($move_func))
			continue;

		$error = $error || !$move_func($sd, $type, $rec_no, $config_data);

		db_free_result($sd);
	}

	return !$error;
}

#
# Move images of $type to the filesystem
# Please use func_move_images() instead.
#
function func_move_images_to_fs($db_image_set, $type, &$rec_no, $config_data) {
	global $sql_tbl, $str_out, $xcart_dir;

	$dest_dir = func_image_dir($type);

	# Storing of image_path field for images stored in filesystem
	# is necessary for compatibility with data caching
	$update_query = "UPDATE ".$sql_tbl['images_'.$type]." SET image_path=?, filename=?, image='', md5=?, date=?, image_size=?, image_x=?, image_y=?, image_type=? WHERE imageid=?";

	$error = false;
	while ($v = db_fetch_array($db_image_set)) {
		if (zerolen($v["image"]) && (!is_url($v['image_path']) || $config_data['save_url'] != 'Y')) {
			# 1. URL images are NOT moving (if 'save_url' option is disabled)
			# 2. for empty "image" assume what image in filesystem already
			continue;
		}

		if (!empty($v['image_path']))
			$v['filename'] = basename($v['image_path']);

		$str_out .= "image #".$v['imageid']." (owner: ".$v['id'].")";

		$moved = false;
		$reason = '';
		if (is_url($v['image_path']) && $config_data['save_url'] == 'Y')
			$v['file_path'] = $v['image_path'];

		$file = func_store_image_fs($v, $type);
		if ($file === false) {
			$reason = 'cannot create file for the image';

		} else {
			$new_data = func_get_image_size($file);
			$image_path = func_relative_path($file);
			$str_out .= " (file: ".$image_path.") - ";

			$file_name = basename($file);
			$md5 = func_md5_file($file);

			if (empty($v['date']))
				$v['date'] = time();

			$update_params = array(
				$image_path,
				$file_name,
				$md5,
				$v['date']
			);
			$update_params = func_array_merge($update_params, $new_data);
			$update_params[] = $v['imageid'];

			$moved = db_exec($update_query, $update_params);

			$error = $error || !$moved;

			if (!$moved) {
				$reason = "cannot update database";
				unlink(func_realpath($file));
			}
		}

		$str_out .= ($moved ? "OK" : "Failed ($reason)")."\n";

		func_echo_dot($rec_no, 1, 100);
	}

	return !$error;
}

#
# Move images of $type to the database.
# Please use func_move_images() instead.
#
function func_move_images_to_db($db_image_set, $type, &$rec_no, $config_data) {
	global $config, $sql_tbl, $str_out;

	$update_query = "UPDATE ".$sql_tbl['images_'.$type]." SET image_path='', image=?, md5=?, date=?, image_size=?, image_x=?, image_y=?, image_type=? WHERE imageid=?";

	$src_dir = func_image_dir($type).DIRECTORY_SEPARATOR;

	$error = false;

	while (!$error && ($v = db_fetch_array($db_image_set))) {
		if (!zerolen($v['image']) || (is_url($v['image_path']) && $config_data['save_url'] != 'Y')) {
			# image in database already ?
			continue;
		}

		if (!empty($v['image_path']) && is_url($v['image_path'])) {
			$file = $fn = $v['image_path'];

		} elseif (!empty($v['image_path'])) {
			$file = $v['image_path'];
			$fn = func_relative_path($file);

		} else {
			$file = $src_dir.$v['filename'];
			$fn = func_relative_path($file);
		}

		$str_out .= $fn." (ID: ".$v['id'].") - ";

		$moved = false;
		$reason = '';

		$image = func_file_get($file, true);
		if ($image === false) {
			$reason = 'cannot open';
		}
		elseif (zerolen($image)) {
			$reason = 'empty image';
		}
		else {
			if (empty($v['date']))
				$v['date'] = time();

			$new_data = func_get_image_size($image, true);
			$update_params = array(
				$image,
				md5($image),
				$v['date']
			);
			$update_params = func_array_merge($update_params, $new_data);
			$update_params[] = $v['imageid'];

			$moved = db_exec($update_query, $update_params);

			$error = $error || !$moved;
			if (!$moved) {
				$reason = "cannot update database";
			}
		}

		if ($moved && !is_url($file)) {
			# finish transfer of image
			@unlink(func_realpath($file));
		}

		$str_out .= ($moved ? "OK" : "Failed ($reason)")."\n";

		func_echo_dot($rec_no, 1, 100);
	}

	return !$error;
}

#
# Check image permissions
#
function func_check_image_storage_perms($file_upload_data, $type = 'T', $get_message = true) {
	global $config, $xcart_dir;

	if (!func_check_image_posted($file_upload_data, $type))
		return true;

	return func_check_image_perms($type, $get_message);
}

#
# Check image type permissions
#
function func_check_image_perms($type, $get_message = true) {
	global $config, $xcart_dir;

	if (!isset($config['setup_images'][$type]) || $config['setup_images'][$type]['location'] == 'DB')
		return true;

	$path = func_image_dir($type);
	$arr = explode("/", substr($path, strlen($xcart_dir)+1));
	$suffix = $xcart_dir;

	foreach ($arr as $p) {
		$suffix .= DIRECTORY_SEPARATOR.$p;

		$return = array();
		if (!is_writable($suffix))
			$return[] = 'w';

		if (!is_readable($suffix))
			$return[] = 'r';

		if (count($return) > 0) {
			$return['path'] = $suffix;
			if ($get_message) {
				if (in_array("r", $return) && in_array("w", $return)) {
					$return['label'] = "msg_err_image_cannot_saved_both_perms";

				} elseif (in_array("r", $return)) {
					$return['label'] = "msg_err_image_cannot_saved_read_perms";

				} else {
					$return['label'] = "msg_err_image_cannot_saved_write_perms";
				}
				$return['content'] = func_get_langvar_by_name($return['label'], array("path" =>  $return['path']));
			}

			return $return;
		}
	}

	return true;
}

#
# Checking that posted image is exist
#
function func_check_image_posted($file_upload_data, $type = 'T') {
	global $config;

	$return = false;
	$config_data = $config['setup_images'][$type];

	if (!empty($type) && !empty($file_upload_data[$type]) && isset($file_upload_data[$type]))
		$image_posted = $file_upload_data[$type];

//	if (!func_allow_file($image_posted["file_path"], true))
	if (isset($image_posted["file_path"]) && !func_allow_file($image_posted["file_path"], true))
		return false;

	if ($image_posted["source"] == "U") {
		if ($fd = func_fopen($image_posted["file_path"], "rb", true)) {
			fclose($fd);
			$return = true;
		}
	} else {
		$return = file_exists($image_posted["file_path"]);
	}

	if ($return) {
		$return = ($image_posted["file_size"] <= $config_data["size_limit"] || $config_data["size_limit"]=="0");
	}

	return $return;
}

#
# Prepare posted image for saving
#
function func_prepare_image($file_upload_data, $type = 'T', $id = 0) {
	global $config, $xcart_dir, $sql_tbl;

	if (empty($file_upload_data[$type]['file_path']) || empty($config['setup_images'][$type]) || !in_array($file_upload_data[$type]['source'], array("U","S","L"))) {
		# ERROR: incorrect value
		return false;
	}

	$image_data = $file_upload_data[$type];

	$config_data = $config['setup_images'][$type];

	$file_path = $image_data["file_path"];
	if (!is_url($file_path))
		$file_path = func_realpath($file_path);

	$image = func_file_get($file_path, true);
	if ($image === false)
		return false;

	$prepared = array(
		"image_size" => strlen($image),
		"md5" => md5($image),
		"filename" => $image_data['filename'],
		"image_type" => $image_data['image_type'],
		"image_x" => $image_data['image_x'],
		"image_y" => $image_data['image_y'],
	);

	if ($config_data["location"] == "FS") {
		$prepared['image_path'] = "";

		if (!is_url($file_path) || $config_data['save_url'] == 'Y') {

			$dest_file = func_image_dir($type);
			if (!zerolen($prepared['filename'])) {
				$dest_file .= "/".$prepared['filename'];
			}

			$prepared['image_path'] = func_store_image_fs($image_data, $type);

			if (zerolen($prepared['image_path']))
				return false;

			$prepared['filename'] = basename($prepared['image_path']);

			$path = func_relative_path($prepared['image_path'], $xcart_dir);
			if ($path !== false) {
				$prepared['image_path'] = $path;
			}

		} else {
			$prepared['image_path'] = $file_path;

		}
	}
	else {

		if (is_url($file_path) && $config_data['save_url'] != 'Y') {
			$prepared['image_path'] = $file_path;
		} else {
			$prepared['image'] = $image;
		}
		unset($image);
		if ($image_data["source"] == "L") {
			@unlink(func_realpath($file_path));
		}
	}

	return $prepared;
}

#
# Save uploaded/changed image
#
function func_save_image(&$file_upload_data, $type, $id, $added_data = array(), $_imageid = NULL) {
	global $sql_tbl, $config, $skip_image;

	$image_data = func_prepare_image($file_upload_data, $type, $id);
	if (empty($image_data) || (empty($id) && $type != "S" && $type != "F"))
		return false;

	if ($skip_image[$type] == 'Y') {
		if (!empty($file_upload_data[$type]['is_copied'])) {
			# Should delete image file
			@unlink($file_upload_data[$type][$file_path]);
		}
		unset($file_upload_data[$type]);
		return false;
	}

	$image_data['id'] = $id;
	$image_data['date'] = time();
	if (!empty($added_data)) {
		$image_data = func_array_merge($image_data, $added_data);
	}

	$image_data = func_addslashes($image_data);
	unset($file_upload_data[$type]);

	$_table = $sql_tbl['images_'.$type];

	if ($config['available_images'][$type] == 'U') {
		if (!empty($_imageid)) {
			$_old_id = func_query_first_cell("SELECT id FROM ".$_table." WHERE imageid = '$_imageid'");
			if (empty($_old_id) || $_old_id == $id)
				$image_data['imageid'] = $_imageid;
		}

		if (empty($image_data['imageid']))
			$image_data['imageid'] = func_query_first_cell("SELECT imageid FROM ".$_table." WHERE id = '$id'");

		if (!empty($image_data['imageid'])) {
			x_load("backoffice");
			func_delete_image($id, $type);
		}
	}

//	return func_array2insert('images_'.$type, $image_data);
	$array2insertid = func_array2insert('images_'.$type, $image_data);



	if (!empty($array2insertid)){
		global $xcart_dir;
		$image_folder_path = $xcart_dir."/images/".$type."/";
		$image_tbl = 'images_'.$type;
		$bad_chars = array("[","]", " ", "'", "\"", "`", ",");
		$replace_to = "_";

		$images = db_query("SELECT imageid, image_path FROM $sql_tbl[$image_tbl] WHERE imageid='$array2insertid'");

		while ($image = db_fetch_array($images)) {

	                $image_path = $image["image_path"];
//        	        $new_image_path = str_replace($bad_chars, $replace_to, $image_path);

#
##
###
			global $default_charset;
			$new_image_path = trim($image_path);
			$new_image_path = preg_replace('/\&(?!#[0-9]+;)(?!#x[0-9a-f]+;)/', '-and-', preg_replace('/\&amp;/', '-and-', $new_image_path));
			$new_image_path = str_replace('/', '-SLASH-', $new_image_path);
			$new_image_path = preg_replace('/-$/', '', preg_replace('/[-]+/', '-', preg_replace('/[^a-zA-Z0-9._-]/', '-', func_translit($new_image_path, $default_charset, '-'))));
			$new_image_path = str_replace('-SLASH-', '/', $new_image_path);
###
##
#

	                if ($image_path != $new_image_path){

        	                $current_filename_arr = explode("/", $image_path);
                	        $current_filename = array_pop($current_filename_arr);
                        	$current_filename_path = $image_folder_path.$current_filename;

	                        $is_the_same_img = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[$image_tbl] WHERE image_path='".addslashes($new_image_path)."'");
        	                if (!empty($is_the_same_img) || $is_the_same_img > "0"){
                	                $replace_type_str = "images/".$type."/";
                        	        $insert_type_str = "images/".$type."/".time()._;
                                	$new_image_path = str_replace($replace_type_str, $insert_type_str, $new_image_path);
	                        }

        	                $new_current_filename_arr = explode("/", $new_image_path);
                	        $new_current_filename = array_pop($new_current_filename_arr);
                        	$new_current_filename_path = $image_folder_path.$new_current_filename;

	                        if (@copy($current_filename_path, $new_current_filename_path)) {
	                                @unlink($current_filename_path);
        	                        db_query("UPDATE $sql_tbl[$image_tbl] SET image_path='$new_image_path' WHERE imageid='$image[imageid]'");
                	        }
	                }
		}

	}

	return $array2insertid;
}

#
# Store image in FS
# Return: path to the file or FALSE
#
function func_store_image_fs($image_data, $type) {
	$dest_dir = func_image_dir($type);

	if (isset($image_data['file_path'])) {
		# this is uploaded image
		# add some missing fields

		$image_data['id'] = false;
		$image_data['imageid'] = false;
		$image_data['image'] = func_file_get($image_data['file_path'],true);
	}

	# unique file location
	$file_name = func_image_gen_unique_filename(
		$image_data['filename'], $type, $image_data['image_type'],
		$image_data['id'], $image_data['imageid']);

	if ($file_name === false) {
		# ERROR: cannot continue
		return false;
	}

	$file = $dest_dir."/".$file_name;

	$fd = func_fopen($file, "wb", true);
	if ($fd === false) {
		# ERROR: cannot continue
		return false;
	}

	fwrite($fd, $image_data["image"]);
	fclose($fd);
	@chmod($file, 0666);

	if (!empty($image_data['is_copied'])) {
		# should present only in structure of uploaded image
		unlink(func_realpath($image_data['file_path']));
	}

	return $file;
}

function func_echo_dot(&$rec_no, $threshold_dot, $threshold_newline) {
	$rec_no ++;
	if ($threshold_dot==1 || ($rec_no % $threshold_dot) == 0) {
		echo ".";
		flush();
	}

	if ($threshold_newline==1 || ($rec_no % $threshold_newline) == 0) {
		echo "<br />\n";
		flush();
	}
}

#
# Get image properties
#
function func_image_properties($type, $id) {
	global $config, $sql_tbl;

	if (empty($config['available_images'][$type]) || empty($config['setup_images'][$type]))
		return false;

	return func_query_first("SELECT image_x, image_y, image_type, image_size, filename FROM ".$sql_tbl['images_'.$type]." WHERE id = '$id'");
}

#
# Function gets image from URL and sets $top_message variable if error has occuried. 
# It is used in func_generate_image function 
#
function func_get_url_image ($path) {
	$url_image = func_url_get($path);
	if ($url_image) {
		return func_temp_store($url_image);
	}

	global $top_message;
	$top_message = array(
		"content" => func_get_langvar_by_name("lbl_auto_resize_could_not_get_file_for_resizing", null, false, true),
		"type" => "E"
	);
	return false;
}

#
# Generate 'from_type' image to 'to_type' image with new dimensions
# (used when script generates thumbnail from product image)
# if new dimensions are lesser than older ones and allow_not_resize is set then there will be no resizing
#
function func_generate_image($id, $from_type = 'P', $to_type = 'T', $allow_not_resize = true, $temporary = false, $imgid = false) {
	global $config, $sql_tbl, $top_message, $auto_thumb_error;

	$from = 'images_'.$from_type;
	$to = 'images_'.$to_type;

	$get_temporary = false;

	if ($temporary) {
		x_session_register("file_upload_data");
		global $file_upload_data;

		if (!empty($file_upload_data[$from_type]) && !isset($file_upload_data[$from_type]['is_redirect'])) {
			$image_filename = $file_upload_data[$from_type]["file_path"];
			$image_type = $file_upload_data[$from_type]["image_type"];
			$image = $file_upload_data[$from_type];
			if (is_url($image_filename)) {
				$image_filename = func_get_url_image($image_filename);
				if (!$image_filename) {
					return false;
				}
			}
			$get_temporary = true;
		}
	}

	if (!$get_temporary) {
		if (!empty($imgid) && is_numeric($imgid)) {
			$img_condition = 'AND imageid = ' . $imgid;
		} else {
			$img_condition = '';
		}

		$image = func_query_first("SELECT image, image_type, image_x, image_y, image_path FROM ".$sql_tbl[$from]." WHERE id='$id' $img_condition LIMIT 1");
		if (is_url($image["image_path"])) {
			$image_filename = func_get_url_image($image["image_path"]);
			if (!$image_filename) {
				return false;
			}

		} else {
			$image_filename = ($config['setup_images'][$from_type]['location'] == "DB") ?
				func_temp_store($image['image']) :
				func_image_dir($from_type).'/'.basename($image['image_path']);
		}
	}

	$image_type = func_get_image_ext(mime_content_type($image_filename));

	if (empty($image_filename)) {
		$top_message = array(
			"content" => func_get_langvar_by_name("lbl_auto_resize_could_not_get_file_for_resizing", null, false, true),
			"type" => "E"
		);
		return false;
	}

#
## 
###
	if ($to_type == "D"){
		$new_x = $config['Appearance']['max_width_det_img'];
		$new_y = $config['Appearance']['max_height_det_img'];
	} elseif ($to_type == "P"){
        	$new_x = $config['Appearance']['max_width_prod_img'];
	        $new_y = $config['Appearance']['max_height_prod_img'];
	} else {
		$new_x = $config['Appearance']['thumbnail_width'];
		$new_y = $config['Appearance']['thumbnail_width'];
	}
###
##
#
	if ($allow_not_resize && $new_x >= $image['image_x'] && $new_y >= $image['image_y']) {
		$new_x = $image['image_x'];
		$new_y = $image['image_y'];
	}

	unset($image);

	$auto_thumb_error = "";
	$new_image = func_resize_image($image_filename, $new_x, $new_y, $image_type, true, THUMB_QUALITY, THUMB_BGCOLOR, true);
	if ($new_image === false) {
		$lbl_message = ($auto_thumb_error == "") ? "lbl_auto_resize_could_not_resize_image" : $auto_thumb_error;
		$top_message = array(
			"content" => func_get_langvar_by_name($lbl_message, null, false, true),
			"type" => "E"
		);
		return false;
	}

	if ($temporary) {
		#
		# Store Image into session
		#

		if (!empty($file_upload_data[$to_type]['is_copied'])) {
			@unlink($file_upload_data[$to_type]['file_path']);
		}

		$file_upload_data[$to_type] = array_merge($new_image, array(
			'is_copied' => true,
			'filename' => basename($new_image['file_path']).'.png',
			'source' => 'L',
			'id' => $id,
			'type' => $to_type,
			'date' => time())
		);

		x_session_save();

	} else {
		#
		# Prepare data to store image
		#

		if ($config['setup_images'][$from_type]['location'] == "DB") {
			@unlink($image_filename);
		}

		if ($config['setup_images'][$to_type]['location'] == "DB") {
			#
			# Store image to DB
			#

			$new_image['image'] = func_temp_read($new_image['file_path'], true);
			$new_image['date'] = time();
			$new_image['filename'] = '';

			$new_image = func_addslashes($new_image);

		} else {
			#
			# Store image to FS
			#

			$new_image = func_addslashes($new_image);
			$new_image['id'] = $id;
			$new_image['image'] = '';
			$new_image['date'] = time();
			$image = $new_image;
			$image['is_copied'] = true;

#
##
###
//func_print_r($new_image, $image, $to_type);
//die();
			db_query("UPDATE $sql_tbl[products] SET tmp_generated_file='".addslashes($new_image["file_path"])."' WHERE productid='$id'");
###
##
#

			$new_image['image_path'] = func_relative_path(func_store_image_fs($image, $to_type));
			if (!$new_image['image_path']) {
				$top_message = array(
					"content" => func_get_langvar_by_name("lbl_auto_resize_could_not_store_image_FS", null, false, true),
					"type" => "E"
				);
				return false;
			}
			$new_image['filename'] = basename($new_image['image_path']);
			unset($image);

			$old_file = func_query_first_cell("SELECT image_path FROM ".$sql_tbl[$to]." WHERE id='$id' LIMIT 1");
			if (!empty($old_file)) {
				$file_is_used = func_query_first_cell("SELECT COUNT(*) FROM ".$sql_tbl[$to]." WHERE image_path = '$old_file' AND id <> '$id'");
				if (empty($file_is_used)) {
					@unlink($old_file);
				}
			}
		}

		unset($new_image['file_path']);

		if ($to_type == "D" && !empty($imgid)){
			$imageid = $imgid;
		} else {
			$imageid = func_query_first_cell("SELECT imageid FROM ".$sql_tbl[$to]." WHERE id='$id' LIMIT 1");
		}

		if ($imageid) {
			func_array2update($to, $new_image, "imageid='$imageid'");
		} else {
			$new_image['id'] = $id;
			func_array2insert($to, $new_image);
		}

		if ($to_type == 'T') {
			$record_exist = func_query_first_cell('SELECT COUNT(*) FROM ' . $sql_tbl['quick_flags'] . ' WHERE productid=' . $id);
			if ($record_exist) {
				func_array2update('quick_flags', array('image_path_T' => $new_image['image_path']), 'productid=' . $id);
			}
		}
	}

	#
	# $auto_thumb_error contains non-critical errors now (show it as a warning)
	#
	$top_message = array(
		"content" => func_get_langvar_by_name(($auto_thumb_error == "") ? "lbl_auto_resize_generate_success" : $auto_thumb_error, null, false, true),
		"type" => ($auto_thumb_error == "") ? "I" : "W"
	 );

	return true;
}

#
# Resize image using GDLib to JPEG image file
#
# Function takes image file, resize it with new dimensions to JPEG format, 
# returns file name which is stored in temporary directory and new dimensions of new image.
# If 'proportional' is set then new dimensions will be recalculated to the proportional ones.
# 'Image Type' must be set to the correct one. (image filename could be without proper image extension) 
#

function func_resize_image($image_filename, $new_x, $new_y, $image_type = 'jpeg', $proportional = true, $quality = 97, $color = 0xFFFFFF, $pthumb = false) {
	global $auto_thumb_error, $xcart_dir;

	ini_set('memory_limit', '128M');

	if (!is_file($image_filename)) {
		$auto_thumb_error = 'lbl_auto_resize_no_file';
		return false;
	}

	if ($image_type == 'jpg') {
		$image_type = 'jpeg';
	}

	$func = 'func_imagecreatefrom' . $image_type;

	if (!function_exists($func)) {
		$auto_thumb_error = 'lbl_auto_resize_no_gd_function';
		return false;
	}

	list($image_x, $image_y) = @getimagesize($image_filename);

	$expansion = true;

	if ($pthumb) {
		$thumb_x = $new_x;
		$thumb_y = $new_y;
		if (($image_x < $thumb_x) && ($image_y < $thumb_y)) {
			$new_x = $image_x;
			$new_y = $image_y;
			$expansion = false;
		}
	}
	if ($proportional && $expansion) {
		list ($new_x, $new_y) = func_get_proper_dimensions($image_x, $image_y, $new_x, $new_y);
	}

	$new_x = intval(round($new_x));
	$new_y = intval(round($new_y));

	$image = $func($image_filename);

	if ($image === false) {
		$auto_thumb_error = 'lbl_auto_resize_could_not_create_image';
		return false;
	}

	if ($pthumb) {
		$new_image = func_imagecreatetruecolor($thumb_x, $thumb_y);
	} else {
		$new_image = func_imagecreatetruecolor($new_x, $new_y);
	}
	if ($new_image === false) {
		$auto_thumb_error = 'lbl_auto_resize_could_not_create_image';
		return false;
	}

	if ($pthumb) {
		if (func_imagefill($new_image, 0, 0, $color) == false) {
			$auto_thumb_error = 'lbl_auto_resize_could_not_create_image';
			return false;
		}
	}

	if ($pthumb) {
		$res = func_imagecopyresampled($new_image, $image, (round($thumb_x) - $new_x) / 2, (round($thumb_y) - $new_y) / 2, 0, 0, $new_x, $new_y, $image_x, $image_y);
	} else {
		$res = func_imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_x, $new_y, $image_x, $image_y);
	}

	if ($res === false) {
		$auto_thumb_error = 'lbl_auto_resize_could_not_resize_image';
		return false;
	}

	$new_file = func_temp_store('');
	if ($new_file === false) {
		$auto_thumb_error = 'lbl_auto_resize_could_not_create_file';
		return false;
	}

	$res = func_imagejpeg($new_image, $new_file, $quality);

	if ($res === false) {
		@unlink($new_file);
		$auto_thumb_error = 'lbl_auto_resize_could_not_convert_image';
		return false;
	}

	func_imagedestroy($new_image);
	func_imagedestroy($image);

	if ($pthumb) {
		$result = array(
			'file_path' => $new_file,
			'image_x' => $thumb_x,
			'image_y' => $thumb_y,
			'image_size' => func_filesize($new_file),
			'image_type' => 'image/jpg'
		);
	} else {
		$result = array(
			'file_path' => $new_file,
			'image_x' => $new_x,
			'image_y' => $new_y,
			'image_size' => func_filesize($new_file),
			'image_type' => 'image/jpg'
		);
	}

	return $result;
}

#
# Get new dimensions with old proportions
#
function func_get_proper_dimensions ($old_x, $old_y, $new_x, $new_y) {

	if ($old_x <= 0 || $old_y <= 0 || ($new_x <= 0 && $new_y <= 0)) {
		return array($old_x, $old_y);
	}

	if ($new_x <= 0) {
		$new_x = round($new_y / $old_y * $old_x, 0);

	} elseif ($new_y <= 0) {
		$new_y = round($new_x / $old_x * $old_y, 0);

	} else {

		$_kx = $new_x / $old_x;
		$_ky = $new_y / $old_y;

		if ($_kx < $_ky) {
			$new_y = round($_kx * $old_y, 0);

		} elseif ($_kx > $_ky) {
			$new_x = round($_ky * $old_x, 0);
		}
	}

	return array($new_x, $new_y);
}

function func_set_correct_det_img($image_info, $update = false){

	global $sql_tbl, $config;

	if (!empty($image_info["image_path"])){
		$file_name_path = $image_info["image_path"];
	}
	elseif (!empty($image_info["file_path"])){
		$file_name_path = $image_info["file_path"];
	}

	$width = $image_info["image_x"];
	$height = $image_info["image_y"];

	if ($width >= $config['Appearance']['max_width_det_img']  || $height >= $config['Appearance']['max_height_det_img']){
        	$im = new Imagick();
		try {
			$im->pingImage($file_name_path);
		} catch (ImagickException $e) {
			throw new Exception(_('Invalid or corrupted image file, please try uploading another image.'));
		}

                try {
               /* send thumbnail parameters to Imagick so that libjpeg can resize images
                * as they are loaded instead of consuming additional resources to pass back
                * to PHP. */

	                $R = MIN($config['Appearance']['max_width_det_img']/$width,$config['Appearance']['max_height_det_img']/$height,1);
                        $new_width = round(abs($R*$width));
                        $new_height = round(abs($R*$height));

                        $im->setSize($new_width, $width);
                        $im->readImage($file_name_path);
                        $im->thumbnailImage(abs($R*$width), 0, false);

                        $im->setImageFileName($file_name_path);
                        $im->writeImage();

                        $image_info["image_x"] = $new_width;
                        $image_info["image_y"] = $new_height;
                        $image_info["image_size"] = filesize($file_name_path);

			if ($update && !empty($image_info["imageid"])){
				db_query("UPDATE $sql_tbl[images_D] SET image_x='$new_width', image_y='$new_height', image_size='$image_info[image_size]' WHERE imageid='$image_info[imageid]'");
			}

		}
                catch (ImagickException $e) {
			header('HTTP/1.1 500 Internal Server Error');
                        throw new Exception(_('An error occured reszing the image.'));
                }

               /* cleanup Imagick */
               $im->destroy();
	}

	return $image_info;
}

