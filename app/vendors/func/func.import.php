<?php

#
# Detect product by productid / productcode / product name
# Output: array
#		productid
#		variantid (if Product Options module is enabled)
#
function func_import_detect_product($values, $use_provider = true) {
	global $action, $active_modules, $sql_tbl, $import_data_provider, $single_mode, $import_file;

	$provider_condition = "";
	if (!$use_provider && !$single_mode && !empty($import_data_provider))
		$provider_condition = " AND $sql_tbl[products].provider='".$import_data_provider."'";

	$values_exists = 0;
	$values_not_added = 0;

	$_productid = $_variantid = NULL;
	if (!empty($values['productid'])) {
		$values_exists++;
		$_productid = func_import_get_cache("PI", $values["productid"]);
		if (is_null($_productid) || ($action == "do" && empty($_productid))) {
			$values_not_added++;
			$_productid = func_query_first_cell("SELECT productid FROM $sql_tbl[products] WHERE productid = '$values[productid]'".$provider_condition);
			if (!empty($_productid))
				func_import_save_cache("PI", $values["productid"], $_productid);
		}
		unset($values['productid']);
	}
	if (!empty($values['productcode']) && (is_null($_productid) || ($action == "do" && empty($_productid)))) {
		$values_exists++;
		$_productid = func_import_get_cache("PR", $values["productcode"]);
		if (is_null($_productid) || ($action == "do" && empty($_productid))) {
			$values_not_added++;
			$_productid = func_query_first_cell("SELECT productid FROM $sql_tbl[products] WHERE productcode = '".addslashes($values['productcode'])."'".$provider_condition);
			if (!empty($_productid))
				func_import_save_cache("PR", $values["productcode"], $_productid);
		}
		if ((is_null($_productid) || ($action == "do" && empty($_productid))) && !empty($active_modules['Product_Options'])) {
			$tmp = func_query_first("SELECT $sql_tbl[variants].variantid, $sql_tbl[products].productid FROM $sql_tbl[products], $sql_tbl[variants] WHERE $sql_tbl[products].productid = $sql_tbl[variants].productid AND $sql_tbl[variants].productcode = '".addslashes($values['productcode'])."'".$provider_condition);
			if (!empty($tmp)) {
				$_productid = $tmp['productid'];
				$_variantid = $tmp['variantid'];
			}
		}
		unset($values['productcode']);
	}
	if (!empty($values['product']) && (is_null($_productid) || ($action == "do" && empty($_productid)))) {
		$values_exists++;
		$_productid = func_import_get_cache("PN", $values["product"]);
		if (is_null($_productid) || ($action == "do" && empty($_productid))) {
			$values_not_added++;
			$_productid = func_query_first_cell("SELECT productid FROM $sql_tbl[products] WHERE product = '".addslashes($values['product'])."'".$provider_condition);
			if (!empty($_productid))
				func_import_save_cache("PN", $values["product"], $_productid);
		}
		unset($values['product']);
	}

	# Check: product MUST be added if section PRODUCTS will be droped
	if ($values_exists == $values_not_added && $import_file["drop"]["products"] == 'Y')
		func_import_module_error("msg_err_import_log_message_14");

	return array($_productid, $_variantid);
}

#
# Get standart product signature (productid / productcode / product)
#
function func_export_get_product($productid) {
	global $sql_tbl;

	return func_query_first("SELECT productid, productcode, product FROM $sql_tbl[products] WHERE productid = '$productid'");
}

#
# Get cell from import cache (product signature based)
#
function func_import_get_pb_cache($values, $type, $add_key = "", $force_save = false) {
	if (empty($type) || (empty($values['productid']) && empty($values['productcode']) && empty($values['product'])))
		return NULL;

	$key = "";
	if (!empty($add_key))
		$key = "\n".$add_key;

	$id = NULL;
	if (!empty($values['productid'])) {
		$id = func_import_get_cache($type."i", $values['productid'].$key);
		if (is_null($id) && $force_save)
			func_import_save_cache($type."i", $values['productid'].$key);
	}
	if (!empty($values['productcode']) && (is_null($id) || $force_save)) {
		$id = func_import_get_cache($type."s", $values['productcode'].$key);
		if (is_null($id) && $force_save)
			func_import_save_cache($type."s", $values['productcode'].$key);
	}
	if (!empty($values['product']) && (is_null($id) || $force_save)) {
		$id = func_import_get_cache($type."n", $values['product'].$key);
		if (is_null($id) && $force_save)
			func_import_save_cache($type."n", $values['product'].$key);
	}
	return $id;
}

#
# Save data to import cache (product signature based)
#
function func_import_save_pb_cache($values, $type, $add_key = NULL, $value, $force_save = false) {
	if (empty($type) || (empty($values['productid']) && empty($values['productcode']) && empty($values['product'])))
	return NULL;

	$key = "";
	if (!empty($add_key))
		$key = "\n".$add_key;

    if (!empty($values['productid'])) {
        func_import_save_cache($type."i", $values['productid'].$key, $value, $force_save);
    }
    if (!empty($values['productcode'])) {
        func_import_save_cache($type."s", $values['productcode'].$key, $value, $force_save);
    }
    if (!empty($values['product'])) {
        func_import_save_cache($type."n", $values['product'].$key, $value, $force_save);
    }
    return true;
}

function func_import_rebuild_product($productid) {
	func_build_quick_flags($productid);
	func_build_quick_prices($productid);
}

function func_import_detect_category($values) {
	global $action, $active_modules, $sql_tbl, $import_file;

	$values_exists = 0;
	$values_not_added = 0;
	$_categoryid = NULL;
	if (!empty($values['categoryid'])) {
		$values_exists++;
		$tmp = func_import_get_cache("CI", $values["categoryid"]);
		if (is_null($tmp) || ($action == "do" && empty($tmp))) {
			$values_not_added++;
			$c = func_query_first("SELECT categoryid, categoryid_path, category FROM $sql_tbl[categories] WHERE categoryid = '$values[categoryid]'");
			if (!empty($c)) {
				$_categoryid = $c['categoryid'];
				$ids = explode("/", $c['categoryid_path']);
				if (count($ids) == 1) {
					$cname = $c['category'];
				} else {
					$where = array();
					$orderby = "CASE categoryid ";
					for ($x = 0; $x < count($ids); $x++) {
						$where[] = "(categoryid = '".$ids[$x]."' AND parentid = '".(($x == 0) ? 0 : $ids[$x-1])."')";
						$orderby .= "WHEN ".$ids[$x]." THEN ".$x." ";
					}
					$orderby .= "END";
					$ids = func_query_column("SELECT category FROM $sql_tbl[categories] WHERE ".implode(" OR ", $where)." ORDER BY ".$orderby);
					$cname = implode($import_file['category_sep'], $ids);
				}
				if (!empty($cname) && !empty($_categoryid))
					func_import_save_cache("CI", $values["categoryid"], $cname);
			}
		} else {
			$_categoryid = $values["categoryid"];
		}
		unset($values['categoryid']);
	}
	if (!empty($values['category']) && (is_null($_categoryid) || ($action == "do" && empty($_categoryid)))) {
		$values_exists++;
		$_categoryid = func_import_get_cache("C", $values["category"]);
		if (is_null($_categoryid) || ($action == "do" && empty($_categoryid))) {
			$ids = explode($import_file['category_sep'], $values['category']);
			$_parentid = 0;
			for ($x = 0; $x < count($ids); $x++) {
				$_categoryid = func_query_first_cell("SELECT categoryid FROM $sql_tbl[categories] WHERE category = '".addslashes($ids[$x])."' AND parentid = '$_parentid'");
				if (empty($_categoryid))
					break;
				$_parentid = $_categoryid;
			}
			if (!empty($_categoryid))
				func_import_save_cache("C", $values["category"], $_categoryid);
		}
		unset($values['category']);
	}

    # Check: category MUST be added if section CATEGORIES will be droped
    if ($values_exists == $values_not_added && $import_file["drop"]["categories"] == 'Y')
        func_import_module_error("msg_err_import_log_message_18");

	return $_categoryid;
}

#
# Get standart category signature (categoryid / category)
#
function func_export_get_category($categoryid) {
	global $sql_tbl, $export_data;

	$cat = func_query_first("SELECT categoryid, categoryid_path FROM $sql_tbl[categories] WHERE categoryid = '$categoryid'");
	if (empty($cat))
		return false;

	$tmp = func_categoryid_path2category_path($cat['categoryid_path']);
	$cat['category'] = (empty($tmp) ? "" : implode($export_data['options']['category_sep'], $tmp));
	func_unset($cat, "categoryid_path");

	return $cat;
}
