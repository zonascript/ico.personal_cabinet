<?php

#
# Save export range
#
function func_export_range_save($section, $data) {
	global $sql_tbl, $export_ranges;

	if (empty($data))
		return false;

	$section = strtoupper($section);
	if (is_string($data)) {
		$export_ranges[$section] = $data;
		db_query("DELETE FROM $sql_tbl[export_ranges] WHERE sec = '".addslashes($section)."'");
	}
	elseif (is_array($data)) {
		func_unset($export_ranges, $section);
		foreach ($data as $v) {
			func_array2insert("export_ranges", array("sec" => addslashes($section), "id" => $v), true);
		}
	}
	else {
		return false;
	}

	return true;
}

# Get export range
function func_export_range_get($section) {
	global $sql_tbl, $export_ranges;

	$type = func_export_range_type($section);
	if ($type == 'S') {
		return $export_ranges[$section];

	} elseif ($type == 'C') {
		return "SELECT id FROM $sql_tbl[export_ranges] WHERE sec = '".addslashes($section)."'";
	}

	return false;
}

#
# Get export range type
#
function func_export_range_type($section) {
	global $sql_tbl, $export_ranges;

	$section = strtoupper($section);
	if (is_array($export_ranges) && isset($export_ranges[$section])) {
		return "S";
	}
	else {
		if (func_query_column("SELECT id FROM $sql_tbl[export_ranges] WHERE sec = '".addslashes($section)."'"))
			return "C";
	}
	return false;
}

#
# Get parent section with not empty export range
#
function func_export_range_detect($section, $last_range = "") {
	global $sql_tbl, $import_specification;

	$section = strtoupper($section);
	if (func_export_range_get_num($section) !== false)
		$last_range = $section;

	if (!empty($import_specification[$section]['parent']))
		return func_export_range_detect($import_specification[$section]['parent'], $last_range);

	return $last_range;
}

#
# Get count of export range
#
function func_export_range_get_num($section) {
	$tmp = func_export_range_get($section);
	if ($tmp === false)
		return false;

	if (is_string($tmp) && !zerolen($tmp)) {
		$res = db_query($tmp);
		if ($res) {
			$tmp = db_num_rows($res);
			db_free_result($res);

			return $tmp;
		}

		return 0;
	}

	return false;
}

# Erase export range
function func_export_range_erase($section) {
	global $sql_tbl, $export_ranges;

	$section = strtoupper($section);
	func_unset($export_ranges, $section);
	db_query("DELETE FROM $sql_tbl[export_ranges] WHERE sec = '".addslashes($section)."'");

	return true;
}
