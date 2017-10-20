<?php

# core function to acquire price according it's MAP Price, Manufacturers Feed enabled and Stock status
# array $product must contain fields:
#       product_availability ('in stock'|'out of stock')   get value with func_product_availability function
#       new_map_price   DECIMAL
#       supplier_feeds_enabled  ('Y'|'N')
#       cost_to_us      DECIMAL
#       price   DECIMAL    price corrected with min_amount quantity
/**
 * @deprecated
 */
function func_product_price($fproduct) {
	global $sql_tbl, $xcart_dir, $active_modules, $config, $https_location, $http_location;

	/* complete code to define final product price */
	$price_min_amount = $fproduct["price"];
	if ($price_min_amount < $fproduct["new_map_price"])
		{
			$price_min_amount = $fproduct["new_map_price"];
		}
	if ($fproduct["supplier_feeds_enabled"] == "Y" && $fproduct["product_availability"] == "out of stock")
		{
			$price_min_amount = func_decreased_price($fproduct["cost_to_us"], $price_min_amount, $fproduct["new_map_price"]);
		}
	return $price_min_amount;
}

#
# Delete product from products table + all associated information
# $productid - product's id
#
function func_delete_product($productid, $update_categories=true, $delete_all=false) {
	global $sql_tbl, $xcart_dir, $smarty, $active_modules;

	x_load('backoffice','category', 'image');


#
##
###
        die("Forbidden. Access denied. You cannot delete products!");
        return false;
###
##
#


	if ($delete_all === true) {
		db_query("DELETE FROM $sql_tbl[pricing]");
		db_query("DELETE FROM $sql_tbl[product_links]");
		db_query("DELETE FROM $sql_tbl[featured_products]");
		db_query("DELETE FROM $sql_tbl[products]");
		db_query("DELETE FROM $sql_tbl[delivery]");
		db_query("DELETE FROM $sql_tbl[extra_field_values]");
		db_query("DELETE FROM $sql_tbl[products_categories]");
		db_query("DELETE FROM $sql_tbl[product_taxes]");
		db_query("DELETE FROM $sql_tbl[product_votes]");
		db_query("DELETE FROM $sql_tbl[product_reviews]");
		db_query("DELETE FROM $sql_tbl[products_lng]");
		db_query("DELETE FROM $sql_tbl[subscriptions]");
		db_query("DELETE FROM $sql_tbl[subscription_customers]");
		db_query("DELETE FROM $sql_tbl[download_keys]");
		db_query("DELETE FROM $sql_tbl[discount_coupons]");
		db_query("DELETE FROM $sql_tbl[stats_customers_products]");
		db_query("DELETE FROM $sql_tbl[wishlist]");
		db_query("DELETE FROM $sql_tbl[product_bookmarks]");
		db_query("DELETE FROM $sql_tbl[product_memberships]");
		db_query("DELETE FROM $sql_tbl[product_files]");
		db_query("DELETE FROM $sql_tbl[products_sf]");
        if (!empty($active_modules['Multiple_Storefronts']) && !empty($active_modules['Brands'])) {
		    db_query("DELETE FROM $sql_tbl[brands_sf]");
        }

		rmdir($product_files_dir);
		mkdir($product_files_dir);
		func_delete_images("T");
		func_delete_images("P");
		func_delete_images("D");

		# Feature comparison module
	if (func_query_first_cell("SELECT module_name FROM $sql_tbl[modules] WHERE module_name='Feature_Comparison'")) {
			if (!isset($sql_tbl['product_features']) || !isset($sql_tbl['product_foptions'])) {
				include_once $xcart_dir."/modules/Feature_Comparison/config.php";
			}

			db_query("DELETE FROM $sql_tbl[product_features]");
			db_query("DELETE FROM $sql_tbl[product_foptions]");
		}

		# Product options module
		if (func_query_first_cell("SELECT module_name FROM $sql_tbl[modules] WHERE module_name='Product_Options'")) {
			if (!isset($sql_tbl['classes']) || !isset($sql_tbl['class_options'])) {
				include_once $xcart_dir."/modules/Product_Options/config.php";
			}

			db_query("DELETE FROM $sql_tbl[classes]");
			db_query("DELETE FROM $sql_tbl[class_options]");
			db_query("DELETE FROM $sql_tbl[product_options_lng]");
			db_query("DELETE FROM $sql_tbl[product_options_ex]");
			db_query("DELETE FROM $sql_tbl[product_options_js]");
			db_query("DELETE FROM $sql_tbl[variant_items]");
			db_query("DELETE FROM $sql_tbl[variants]");
			func_delete_images("W");
		}

		# Product configurator module
		if (func_query_first_cell("SELECT module_name FROM $sql_tbl[modules] WHERE module_name='Product_Configurator'")) {
			if (!isset($sql_tbl['pconf_products_classes'])) {
				include_once $xcart_dir."/modules/Product_Configurator/config.php";
			}

			db_query("DELETE FROM $sql_tbl[pconf_products_classes]");
			db_query("DELETE FROM $sql_tbl[pconf_class_specifications]");
			db_query("DELETE FROM $sql_tbl[pconf_class_requirements]");
			db_query("DELETE FROM $sql_tbl[pconf_wizards]");
			db_query("DELETE FROM $sql_tbl[pconf_slots]");
			db_query("DELETE FROM $sql_tbl[pconf_slot_rules]");
			db_query("DELETE FROM $sql_tbl[pconf_slot_markups]");
		}

		# Magnifier module
		if (func_query_first_cell("SELECT module_name FROM $sql_tbl[modules] WHERE module_name='Magnifier'")) {
			if (!isset($sql_tbl['images_Z'])) {
				include_once $xcart_dir."/modules/Magnifier/config.php";
			}

			db_query("DELETE FROM $sql_tbl[images_Z]");
			$dir_z = func_image_dir("Z");
			if (is_dir($dir_z) && file_exists($dir_z))
				func_rm_dir($dir_z);
		}

		if ($update_categories) {
			$res = db_query("SELECT categoryid FROM $sql_tbl[categories]");
			func_recalc_product_count($res);
		}

		func_data_cache_get("fc_count", array("Y"), true);
		func_data_cache_get("fc_count", array("N"), true);

		db_query("DELETE FROM $sql_tbl[quick_flags]");
		db_query("DELETE FROM $sql_tbl[quick_prices]");

		return true;
	}

    if (!empty($active_modules['Multiple_Storefronts']) && !empty($active_modules['Brands'])) {
        $old_brand = func_query_first_cell('SELECT brandid FROM ' . $sql_tbl['products'] 
            . ' WHERE productid = "' . $productid . '"');
    }

	$product_categories = func_query_column("SELECT $sql_tbl[categories].categoryid_path FROM $sql_tbl[categories], $sql_tbl[products_categories] WHERE $sql_tbl[categories].categoryid = $sql_tbl[products_categories].categoryid AND $sql_tbl[products_categories].productid='$productid'");

	db_query("DELETE FROM $sql_tbl[pricing] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[product_links] WHERE productid1='$productid' OR productid2='$productid'");
	db_query("DELETE FROM $sql_tbl[featured_products] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[products] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[delivery] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[extra_field_values] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[products_categories] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[product_memberships] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[products_sf] WHERE productid='$productid'");
	$files = func_query_column('SELECT filename FROM '.$sql_tbl['product_files'].' WHERE productid='.$productid);
	db_query("DELETE FROM $sql_tbl[product_files] WHERE productid='$productid'");
	if (is_dir($product_files_dir . '/' . $productid)) {
		rmdir($product_files_dir . '/' . $productid);
	}
	func_delete_image($productid, "T");
	func_delete_image($productid, "P");
	func_delete_image($productid, "D");

	# Feature comparison module
	if (func_query_first_cell("SELECT module_name FROM $sql_tbl[modules] WHERE module_name='Feature_Comparison'")) {
		if (!isset($sql_tbl['product_features']) || !isset($sql_tbl['product_foptions'])) {
			include_once $xcart_dir."/modules/Feature_Comparison/config.php";
		}

		db_query("DELETE FROM $sql_tbl[product_features] WHERE productid='$productid'");
		db_query("DELETE FROM $sql_tbl[product_foptions] WHERE productid='$productid'");
	}

	# Product options module
	if (func_query_first_cell("SELECT module_name FROM $sql_tbl[modules] WHERE module_name='Product_Options'")) {
		if (!isset($sql_tbl['classes']) || !isset($sql_tbl['class_options'])) {
			include_once $xcart_dir."/modules/Product_Options/config.php";
		}

		$classes = func_query_column("SELECT classid FROM $sql_tbl[classes] WHERE productid='$productid'");
		db_query("DELETE FROM $sql_tbl[classes] where productid='$productid'");
		if (!empty($classes)) {
			$options = func_query_column("SELECT optionid FROM $sql_tbl[class_options] where classid IN ('".implode("','", $classes)."')");
			db_query("DELETE FROM $sql_tbl[class_lng] where classid IN ('".implode("','", $classes)."')");
			if (!empty($options)) {
				db_query("DELETE FROM $sql_tbl[class_options] where classid IN ('".implode("','", $classes)."')");
				db_query("DELETE FROM $sql_tbl[product_options_lng] WHERE optionid IN ('".implode("','", $options)."')");
				db_query("DELETE FROM $sql_tbl[product_options_ex] WHERE optionid IN ('".implode("','", $options)."')");
				db_query("DELETE FROM $sql_tbl[variant_items] WHERE optionid IN ('".implode("','", $options)."')");
			}
		}

		db_query("DELETE FROM $sql_tbl[product_options_js] WHERE productid='$productid'");
		$vids = db_query("SELECT variantid FROM $sql_tbl[variants] WHERE productid='$productid'");
		if ($vids) {
			while ($row = db_fetch_array($vids)) {
				func_delete_image($row['variantid'], "W");
			}
			db_free_result($vids);
		}
		db_query("DELETE FROM $sql_tbl[variants] WHERE productid='$productid'");
	}

	# Magnifier module
	if (func_query_first_cell("SELECT module_name FROM $sql_tbl[modules] WHERE module_name='Magnifier'")) {
		if (!isset($sql_tbl['images_Z'])) {
			include_once $xcart_dir."/modules/Magnifier/config.php";
		}

		db_query("DELETE FROM $sql_tbl[images_Z] WHERE id = '$productid'");
		$dir_z = func_image_dir("Z").DIRECTORY_SEPARATOR.$productid;
		if (is_dir($dir_z) && file_exists($dir_z))
			func_rm_dir($dir_z);
	}

	db_query("DELETE FROM $sql_tbl[product_taxes] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[product_votes] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[product_reviews] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[products_lng] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[subscriptions] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[subscription_customers] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[download_keys] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[discount_coupons] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[stats_customers_products] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[wishlist] WHERE productid='$productid'");
	db_query("DELETE FROM $sql_tbl[product_bookmarks] WHERE productid='$productid'");

	# Product configurator module
	if (func_query_first_cell("SELECT module_name FROM $sql_tbl[modules] WHERE module_name='Product_Configurator'")) {
		#
		# If Product Configurator installed delete the related information
		#
		include_once $xcart_dir."/modules/Product_Configurator/config.php";

		$classes = func_query_column("SELECT classid FROM $sql_tbl[pconf_products_classes] WHERE productid='$productid'");
		if (!empty($classes)) {
			
			#
			# Delete all classification info related with this product
			#
			db_query("DELETE FROM $sql_tbl[pconf_class_specifications] WHERE classid IN ('".implode("','", $classes)."')");
			db_query("DELETE FROM $sql_tbl[pconf_class_requirements] WHERE classid IN ('".implode("','", $classes)."')");
		}

		db_query("DELETE FROM $sql_tbl[pconf_products_classes] WHERE productid='$productid'");

		#
		# Delete configurable product
		#
		$steps = func_query_column("SELECT stepid FROM $sql_tbl[pconf_wizards] WHERE productid='$productid'");
		if (!empty($steps)) {

			#
			# Delete the data related with wizards' steps
			#
			$slots = func_query("SELECT slotid FROM $sql_tbl[pconf_slots] WHERE stepid IN ('".implode("','", $steps)."')");
			if (!empty($slots)) {

				#
				# Delete data related with slots
				#
				db_query("DELETE FROM $sql_tbl[pconf_slots] WHERE stepid IN ('".implode("','", $steps)."')");
				db_query("DELETE FROM $sql_tbl[pconf_slot_rules] WHERE slotid IN ('".implode("','", $slots)."')");
				db_query("DELETE FROM $sql_tbl[pconf_slot_markups] WHERE slotid IN ('".implode("','", $slots)."')");
			}
		}

		db_query("DELETE FROM $sql_tbl[pconf_wizards] WHERE productid='$productid'");
	}

	#
	# Update product count for categories
	#
	if ($update_categories && !empty($product_categories)) {
		$cats = array();
		foreach ($product_categories as $c) {
			$cats = array_merge($cats, explode("/", $c));
		}
		$cats = array_unique($cats);
		func_recalc_product_count($cats);
	}

	func_data_cache_get("fc_count", array("Y"), true);
	func_data_cache_get("fc_count", array("N"), true);

	db_query("DELETE FROM $sql_tbl[quick_flags] WHERE productid = '$productid'");
	db_query("DELETE FROM $sql_tbl[quick_prices] WHERE productid = '$productid'");

        db_query("DELETE FROM $sql_tbl[clean_urls] WHERE resource_type = 'P'");
        db_query("DELETE FROM $sql_tbl[clean_urls_history] WHERE resource_type = 'P'");


    if (!empty($active_modules['Multiple_Storefronts']) && !empty($active_modules['Brands']) && !empty($old_brand)) {
        func_rebuild_brand_sf($old_brand);
    }

	return true;
}

#
# Search for products in products database
#
function func_search_products($query, $membershipid, $orderby="", $limit="") {
	global $current_area, $user_account, $active_modules, $xcart_dir, $current_location, $single_mode;
	global $store_language, $sql_tbl;
	global $config;
	global $cart, $login;
	global $active_modules;
	static $orderby_rules = NULL;

	x_load('files','taxes');

	if (is_null($orderby_rules)) {
		$orderby_rules = array (
			"title" => "product",
			"quantity" => "$sql_tbl[products].avail",
			"orderby" => "$sql_tbl[products_categories].orderby",
			"quantity" => "$sql_tbl[products].avail",
			"price" => "price",
			"productcode" => "$sql_tbl[products].productcode");
	}

	#
	# Generate ORDER BY rule
	#
	if (empty($orderby)) {
		$orderby = ($config["Appearance"]["products_order"] ? $config["Appearance"]["products_order"] : "orderby");
		if (!empty($orderby_rules))
			$orderby = $orderby_rules[$orderby];
	}

	#
	# Initialize service arrays
	#
	$fields = array();
	$from_tbls = array();
	$inner_joins = array();
	$left_joins = array();
	$where = array();
	$groupbys = array();
	$orderbys = array();

	#
	# Generate membershipid condition
	#
	$membershipid_condition = "";
	$membershipid_string = ($membershipid == 0 || empty($active_modules['Wholesale_Trading'])) ? "= 0" : "IN ('$membershipid', 0)";
	if ($current_area == "C") {
		$where[] = "($sql_tbl[category_memberships].membershipid = '$membershipid' OR $sql_tbl[category_memberships].membershipid IS NULL)";
		$where[] = "$sql_tbl[products].forsale='Y'";
		$where[] = "($sql_tbl[product_memberships].membershipid = '$membershipid' OR $sql_tbl[product_memberships].membershipid IS NULL)";
	}

	#
	# Generate products availability condition
	#
	if ($config["General"]["unlimited_products"]=="N" && (($current_area == "C" || $current_area == "B") && $config["General"]["disable_outofstock_products"] == "Y"))
		$where[] = "$sql_tbl[products].avail > 0";

	$from_tbls[] = "pricing";
	$inner_joins = array(
		"products_categories" => array(
			"on" => "$sql_tbl[products_categories].productid = $sql_tbl[products].productid",
		),
		"categories" => array(
			"on" => "$sql_tbl[categories].categoryid = $sql_tbl[products_categories].categoryid AND $sql_tbl[categories].avail = 'Y'",
		)
	);
	$left_joins = array();

	$fields[] = "$sql_tbl[products].productid";
	if ($current_area == "C") {
		$left_joins["products_lng"] = array(
			"on" => "$sql_tbl[products].productid = $sql_tbl[products_lng].productid AND code = '$store_language'"
		);
		$fields[] = "IF($sql_tbl[products_lng].productid != '', $sql_tbl[products_lng].product, $sql_tbl[products].product) as product";

	} else {
		$fields[] = "$sql_tbl[products].product";
	}

	$fields[] = "$sql_tbl[products].productcode";
	$fields[] = "$sql_tbl[products].avail";

	if ($current_area != 'C') {
		$fields[] = "MIN($sql_tbl[pricing].price) as price";

	} else {
		$fields[] = "$sql_tbl[pricing].price";

		$left_joins['quick_prices'] = array(
			"on" => "$sql_tbl[quick_prices].productid = $sql_tbl[products].productid AND $sql_tbl[quick_prices].membershipid $membershipid_string"
		);
		$where[] = "$sql_tbl[quick_prices].priceid = $sql_tbl[pricing].priceid and $sql_tbl[pricing].quantity = 1";
	}

	if ($current_area == "C" && !$single_mode) {
		$inner_joins["ACHECK"] = array(
			"tblname" => "customers",
			"on" => "$sql_tbl[products].provider=ACHECK.login",
		);
	}

	$left_joins['category_memberships'] = array(
		"on" => "$sql_tbl[category_memberships].categoryid = $sql_tbl[categories].categoryid",
		"parent" => "categories"
	);
	$left_joins['product_memberships'] = array(
		"on" => "$sql_tbl[product_memberships].productid = $sql_tbl[products].productid"
	);

	$where[] = "$sql_tbl[products].productid = $sql_tbl[products_categories].productid";
	$where[] = "$sql_tbl[products_categories].categoryid = $sql_tbl[categories].categoryid";
	$where[] = "$sql_tbl[products].productid = $sql_tbl[pricing].productid";
	$where[] = "$sql_tbl[pricing].quantity = '1'";
	if (empty($membershipid)) {
		$where[] = "$sql_tbl[pricing].membershipid = 0";
	} else {
		$where[] = "$sql_tbl[pricing].membershipid IN ('$membershipid', 0)";
	}

	if ($current_area == 'C' && empty($active_modules['Product_Configurator'])) {
		$where[] = "$sql_tbl[products].product_type <> 'C'";
		$where[] = "$sql_tbl[products].forsale <> 'B'";
	}

	if ($current_area == 'C' && !empty($active_modules['Product_Options'])) {
		$where[] = "($sql_tbl[pricing].variantid = 0 OR ($sql_tbl[variants].variantid = $sql_tbl[pricing].variantid".(($config["General"]["disable_outofstock_products"] == "Y" && $config["General"]["unlimited_products"] != "Y")?" AND $sql_tbl[variants].avail > 0":"")."))";
	}
	else {
		$where[] = "$sql_tbl[pricing].variantid = '0'";
	}

	$groupbys[] = "$sql_tbl[products].productid";
	$orderbys[] = $orderby;

	#
	# Check if product have prodyct class (Feature comparison)
	#
	if (!empty($active_modules['Feature_Comparison']) && $current_area == "C") {
		global $comparison_list_ids;

		$left_joins['product_features'] = array(
			"on" => "$sql_tbl[product_features].productid = $sql_tbl[products].productid"
		);
		$fields[] = "$sql_tbl[product_features].fclassid";
		if (($config['Feature_Comparison']['fcomparison_show_product_list'] == 'Y') && $config['Feature_Comparison']['fcomparison_max_product_list'] > @count((array)$comparison_list_ids)) {
			$fields[] = "IF($sql_tbl[product_features].fclassid IS NULL || $sql_tbl[product_features].productid IN ('".@implode("','",@array_keys((array)$comparison_list_ids))."'),'','Y') as is_clist";
		}
	}

	#
	# Check if product have product options (Product options)
	#
	if (!empty($active_modules['Product_Options'])) {
		$left_joins['classes'] = array(
			"on" => "$sql_tbl[classes].productid = $sql_tbl[products].productid"
		);
		if ($current_area == 'C') {
			$left_joins['variants'] = array(
				"on" => "$sql_tbl[variants].productid = $sql_tbl[products].productid AND $sql_tbl[quick_prices].variantid = $sql_tbl[variants].variantid",
			);
			$fields[] = "$sql_tbl[quick_prices].variantid";
			global $variant_properties;
			foreach ($variant_properties as $property) {
				$fields[] = "IFNULL($sql_tbl[variants].$property, $sql_tbl[products].$property) as ".$property;
			}

		} else {
			$left_joins['variants'] = array(
				"on" => "$sql_tbl[variants].productid = $sql_tbl[products].productid",
			);
		}

		$fields[] = "IF($sql_tbl[classes].classid IS NULL,'','Y') as is_product_options";
		$fields[] = "IF($sql_tbl[variants].variantid IS NULL,'','Y') as is_variant";
	}

	if ($config['setup_images']['T']['location'] == "FS") {
		$left_joins['images_T'] = array(
			"on" => "$sql_tbl[images_T].id = $sql_tbl[products].productid"
		);
		$fields[] = "IF($sql_tbl[images_T].id IS NULL, '', 'Y') as is_thumbnail";
		$fields[] = "$sql_tbl[images_T].image_path";
	}

	if ($current_area == "C") {
		$left_joins['product_taxes'] = array(
			"on" => "$sql_tbl[product_taxes].productid = $sql_tbl[products].productid"
		);
		$fields[] = "$sql_tbl[product_taxes].taxid";
	}

	#
	# Generate search query
	#
	foreach ($inner_joins as $j) {
		if (!empty($j['fields']) && is_array($j['fields']))
			$fields = func_array_merge($fields, $j['fields']);
	}
	foreach ($left_joins as $j) {
		if (!empty($j['fields']) && is_array($j['fields']))
			$fields = func_array_merge($fields, $j['fields']);
	}

	$search_query = "SELECT ".implode(", ", $fields)." FROM ";
	if (!empty($from_tbls)) {
		foreach ($from_tbls as $k => $v) {
			$from_tbls[$k] = $sql_tbl[$v];
		}
		$search_query .= implode(", ", $from_tbls).", ";
	}
	$search_query .= $sql_tbl['products'];

	foreach ($left_joins as $ljname => $lj) {
		if (!empty($lj['parent']))
			continue;
		$search_query .= " LEFT JOIN ";
		if (!empty($lj['tblname'])) {
			$search_query .= $sql_tbl[$lj['tblname']]." as ".$ljname;
		} else {
			$search_query .= $sql_tbl[$ljname];
		}
		$search_query .= " ON ".$lj['on'];
	}

	foreach ($inner_joins as $ijname => $ij) {
		$search_query .= " INNER JOIN ";
		if (!empty($ij['tblname'])) {
			$search_query .= $sql_tbl[$ij['tblname']]." as ".$ijname;
		} else {
			$search_query .= $sql_tbl[$ijname];
		}
		$search_query .= " ON ".$ij['on'];
		foreach ($left_joins as $ljname => $lj) {
			if ($lj['parent'] != $ijname)
				continue;
			$search_query .= " LEFT JOIN ";
			if (!empty($lj['tblname'])) {
				$search_query .= $sql_tbl[$lj['tblname']]." as ".$ljname;
			} else {
				$search_query .= $sql_tbl[$ljname];
			}
			$search_query .= " ON ".$lj['on'];
		}
	}

	$search_query .= " WHERE ".implode(" AND ", $where).$query;
	if (!empty($groupbys))
		$search_query .= " GROUP BY ".implode(", ", $groupbys);
	if (!empty($orderbys))
		$search_query .= " ORDER BY ".implode(", ", $orderbys);
	if (!empty($limit))
		$search_query .= " LIMIT ".$limit;

	db_query("SET OPTION SQL_BIG_SELECTS=1");

	$result = func_query($search_query);

	$ids = array();
	if (!empty($result)) {
		foreach($result as $v) {
			$ids[] = $v['productid'];
		}
	}

	if ($result && ($current_area=="C" || $current_area=="B") ) {
		#
		# Post-process the result products array
		#

		if (!empty($active_modules['Extra_Fields'])) {
			$tmp = func_query("SELECT *, IF($sql_tbl[extra_fields_lng].field != '', $sql_tbl[extra_fields_lng].field, $sql_tbl[extra_fields].field) as field FROM $sql_tbl[extra_field_values], $sql_tbl[extra_fields] LEFT JOIN $sql_tbl[extra_fields_lng] ON $sql_tbl[extra_fields].fieldid = $sql_tbl[extra_fields_lng].fieldid AND $sql_tbl[extra_fields_lng].code = '$shop_language' WHERE $sql_tbl[extra_fields].fieldid = $sql_tbl[extra_field_values].fieldid AND $sql_tbl[extra_field_values].productid IN ('".implode("','", $ids)."') AND $sql_tbl[extra_fields].active = 'Y' ORDER BY $sql_tbl[extra_fields].orderby");
			$products_ef = array();
			if (!empty($tmp) && is_array($tmp)) {
				foreach($tmp as $v) {
					$products_ef[$v['productid']][] = $v;
				}
			}
		}

		if (!empty($active_modules['Product_Options']) && !empty($ids)) {
			$options_markups = func_get_default_options_markup_list($ids);
		}

		foreach ($result as $key => $value) {

			$value['taxed_price'] = $result[$key]['taxed_price'] = $value['price'];
			if (!empty($active_modules['Product_Options']) && !empty($options_markups[$value['productid']])) {
				# Add product options markup
				$result[$key]['price'] += $options_markups[$value['productid']];
				$result[$key]['taxed_price'] = $products[$key]['price'];
				$value = $result[$key];
			}

			if (!empty($cart) && !empty($cart["products"]) && $current_area=="C") {
				#
				# Update quantity for products that already placed into the cart
				#
				$in_cart = 0;
				foreach ($cart["products"] as $cart_item) {
					if ($cart_item["productid"] == $value["productid"] && $cart_item["variantid"] == $variant_def[$value["productid"]]['variantid'])
						$in_cart += $cart_item["amount"];
				}
				$result[$key]["avail"] -= $in_cart;
			}

			if (!empty($active_modules['Extra_Fields'])) {
				if (isset($products_ef[$v['productid']])) {
					$result[$key]['extra_fields'] = $products_ef[$v['productid']];
				}
			}

			#
			# Get thumbnail's URL (uses only if images stored in FS)
			#
			$value['is_thumbnail'] = ($value['is_thumbnail'] == 'Y');
			if ($value['is_thumbnail'] && !empty($value['image_path']))
				$result[$key]["tmbn_url"] = func_get_image_url($value['productid'], 'T', $value['image_path']);

			unset($result[$key]['image_path']);

			if ($current_area == "C" && $value['taxid'] > 0) {
				$result[$key]["taxes"] = func_get_product_taxes($result[$key], $login);
			}
		}
	}

	return $result;
}

#
# Put all product info into $product array
#
function func_select_product($id, $membershipid, $redirect_if_error=true, $clear_price=false, $always_select=false, $skip_cat_checking=false, $use_current_storefront = '') {
	global $login, $login_type, $current_area, $single_mode, $cart, $current_location;
	global $store_language, $sql_tbl, $config, $active_modules, $xcart_catalogs;
	global $ajax_error, $ajax_redirect, $ajax_mode, $current_storefront;
	global $add_from_order_edit;

#
##
###
	if ($use_current_storefront != ""){
		$current_storefront = $use_current_storefront;
	}
###
##
#
	x_load('files','taxes');

	$in_cart = 0;

	$id = intval($id);

	$membershipid = intval($membershipid);
	$p_membershipid_condition = $membershipid_condition = "";
	if ($current_area == "C" || empty($current_area)) {  /*speed optimization*/
		$membershipid_condition = ""; // " AND ($sql_tbl[category_memberships].membershipid = '$membershipid' OR $sql_tbl[category_memberships].membershipid IS NULL) ";
		$p_membershipid_condition = ""; // " AND ($sql_tbl[product_memberships].membershipid = '$membershipid' OR $sql_tbl[product_memberships].membershipid IS NULL) ";
//		$price_condition = " /*AND $sql_tbl[quick_prices].membershipid ".((empty($membershipid) || empty($active_modules['Wholesale_Trading'])) ? "= 0" : "IN ('$membershipid', 0)")."*/ AND $sql_tbl[quick_prices].priceid = $sql_tbl[pricing].priceid and $sql_tbl[pricing].quantity = 1";
		$price_condition = " AND $sql_tbl[quick_prices].priceid = $sql_tbl[pricing].priceid";

	} else {
		$price_condition = " /*AND $sql_tbl[pricing].membershipid = 0*/ AND $sql_tbl[products].productid = $sql_tbl[pricing].productid AND $sql_tbl[pricing].quantity = 1 AND $sql_tbl[pricing].variantid = 0";
	}

	if ($current_area == "C" && !empty($cart) && !empty($cart["products"])) {
		foreach ($cart["products"] as $cart_item) {
			if ($cart_item["productid"] == $id) {
				$in_cart += $cart_item["amount"];
			}
		}
	}

	$login_condition = "";
	if (!$single_mode) {
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
		if ($login != "" && $login_type == "P") {
			$selected_manufacturers = func_query_first_cell("SELECT manufacturerids FROM $sql_tbl[customers] WHERE login='$login' AND usertype='$login_type'");
			if (!empty($selected_manufacturers)) {
				$selected_manufacturers = unserialize($selected_manufacturers);
			}

			if (is_array($selected_manufacturers)) {
				$login_condition = "AND $sql_tbl[products].manufacturerid IN ('".implode("','", $selected_manufacturers)."')";
			} else {
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
		$login_condition = (($login != "" && $login_type == "P") ? "AND $sql_tbl[products].provider='$login'" : "");
	}
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
		} 
	}
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 

	$add_fields = "";
	$join = "";

	if (!empty($active_modules['Product_Options']) && ($current_area == "P" || $current_area == "A")) {
		$join .= " LEFT JOIN $sql_tbl[variants] ON $sql_tbl[products].productid = $sql_tbl[variants].productid";
		$add_fields .= ", IF($sql_tbl[variants].productid IS NULL, '', 'Y') as is_variants";
	}

	if (
		!empty($active_modules['Multiple_Storefronts']) &&
		!$add_from_order_edit &&
		!( ($current_area == 'A' || $current_area == 'P') && $config['Search_products']['search_by_sku_from_all_sf'] == 'Y')
	   ) 
	{
		$join .= " LEFT JOIN $sql_tbl[products_sf] ON $sql_tbl[products_sf].productid=$sql_tbl[products].productid";
		$sf_condition = " AND $sql_tbl[products_sf].sfid=$current_storefront";
	} else {
		$sf_condition = '';
	}

	if (!empty($active_modules['Feature_Comparison'])) {
		$join .= " LEFT JOIN $sql_tbl[product_features] ON $sql_tbl[product_features].productid = $sql_tbl[products].productid";
		$add_fields .= ", $sql_tbl[product_features].fclassid";
	}

	if (!empty($active_modules["Manufacturers"])) {
		$join .= " LEFT JOIN $sql_tbl[manufacturers] ON $sql_tbl[manufacturers].manufacturerid = $sql_tbl[products].manufacturerid";
		$add_fields .= ", $sql_tbl[manufacturers].manufacturer, $sql_tbl[manufacturers].cost_to_us_coef_x, $sql_tbl[manufacturers].price_coef_x, $sql_tbl[manufacturers].price_coef_y, $sql_tbl[manufacturers].price_coef_z, $sql_tbl[manufacturers].map_price_coef_x, $sql_tbl[manufacturers].new_map_price_coef_x, $sql_tbl[manufacturers].allow_pre_orders ";
	}

	$join .= " LEFT JOIN xcart_supplier_feeds SF ON SF.manufacturerid = xcart_products.manufacturerid and SF.feed_type = 'I' AND SF.enabled='Y' AND (SF.multiple_feed_destinations!='Y' OR (SF.multiple_feed_destinations='Y' AND xcart_products.controlled_by_feed=SF.feed_file_name))";
	$add_fields .= ", SF.enabled as supplier_feeds_enabled ";

	if ($current_area == "C" || empty($current_area)) {  /*speed optimization*/
		$add_fields .= ", /*IF($sql_tbl[products_lng].productid != '', $sql_tbl[products_lng].product,*/( $sql_tbl[products].product) as product, /*IF($sql_tbl[products_lng].productid != '', $sql_tbl[products_lng].descr,*/( $sql_tbl[products].descr) as descr, /*IF($sql_tbl[products_lng].productid != '', $sql_tbl[products_lng].fulldescr,*/( $sql_tbl[products].fulldescr) as fulldescr, $sql_tbl[quick_flags].*, $sql_tbl[quick_prices].variantid, $sql_tbl[quick_prices].priceid";
		$join .= " /*LEFT JOIN $sql_tbl[products_lng] ON $sql_tbl[products_lng].code='$store_language' AND $sql_tbl[products_lng].productid = $sql_tbl[products].productid*/ LEFT JOIN $sql_tbl[quick_prices] ON $sql_tbl[products].productid = $sql_tbl[quick_prices].productid /*AND $sql_tbl[quick_prices].membershipid*/ ";
/*		if (empty($membershipid) || empty($active_modules['Wholesale_Trading'])) {
			$join .= " = '0'";
		} else {
			$join .= " IN ('$membershipid', 0)";
		}*/
		$join .= " LEFT JOIN $sql_tbl[quick_flags] ON $sql_tbl[products].productid = $sql_tbl[quick_flags].productid";
	}
	else {
                $add_fields .= ", $sql_tbl[manufacturers].d_website_search_for_sku_url ";
	}

//	$join .= " LEFT JOIN $sql_tbl[product_memberships] ON $sql_tbl[product_memberships].productid = $sql_tbl[products].productid";


#
##
###
	    $join .= " LEFT JOIN $sql_tbl[clean_urls] ON $sql_tbl[clean_urls].resource_type = 'P' AND $sql_tbl[clean_urls].resource_id = $sql_tbl[products].productid";

	    $add_fields .= ", $sql_tbl[clean_urls].clean_url, $sql_tbl[clean_urls].mtime";
###
##
#

/* speed optimizations no such conditions
	if ($current_area == 'C' && empty($active_modules['Product_Configurator'])) {
	
		$login_condition .= " AND $sql_tbl[products].product_type <> 'C' AND $sql_tbl[products].forsale <> 'B' ";
		
	}
*/

	
	$product = func_query_first("SELECT $sql_tbl[products].*, $sql_tbl[products].avail-$in_cart AS avail, $sql_tbl[pricing].price as price $add_fields FROM $sql_tbl[pricing], $sql_tbl[products] $join WHERE $sql_tbl[products].productid='$id' ".$login_condition.$p_membershipid_condition.$price_condition.$sf_condition." GROUP BY $sql_tbl[products].productid");

/*speed optimization*/
//	print("l:".$membershipid_condition);
//	$categoryid = func_query_first_cell("SELECT $sql_tbl[products_categories].categoryid FROM $sql_tbl[products_categories]  /*, $sql_tbl[categories]*/ /*LEFT JOIN $sql_tbl[category_memberships] ON $sql_tbl[category_memberships].categoryid = $sql_tbl[categories].categoryid*/ WHERE /*$sql_tbl[products_categories].categoryid=$sql_tbl[categories].categoryid $membershipid_condition AND*/ $sql_tbl[products_categories].productid = '$id' and $sql_tbl[products_categories].main = 'Y' LIMIT 1");
	$category_info = func_query_first("SELECT $sql_tbl[products_categories].categoryid, $sql_tbl[categories].category FROM $sql_tbl[products_categories]  LEFT JOIN $sql_tbl[categories] ON $sql_tbl[products_categories].categoryid=$sql_tbl[categories].categoryid WHERE $sql_tbl[products_categories].productid = '$id' and $sql_tbl[products_categories].main = 'Y' LIMIT 1");

	$categoryid = $category_info["categoryid"];


/*
	# Check product's provider activity // Custom development (Activity of providers should not affect products)
	if (!$single_mode && $current_area == "C" && !empty($product)) {
		if (!func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[customers] WHERE login = '$product[provider]'")) {
			$product = array();
	}
	}
*/
	#
	# Error handling
	#
	if (!$product || (!$categoryid && $skip_cat_checking == false)) {
		if ($redirect_if_error) {
			if ($ajax_mode == 'Y') {
				$ajax_error = 'Y';
				$ajax_redirect = 'error_message.php?access_denied&id=33';
				return false;
			} else {
			func_header_location("error_message.php?access_denied&id=33");
			}
		} else {
			return false;
	}
	}

	$product["productid"] = $id;
	$product["categoryid"] = $categoryid;
	$product["category"] = $category_info["category"];
	if ($current_area != 'C' && !empty($current_area)) { /*speed optimization*/
	$tmp = func_query_column("SELECT membershipid FROM $sql_tbl[product_memberships] WHERE productid = '$product[productid]'");
	if (!empty($tmp) && is_array($tmp)) {
		$product['membershipids'] = array();
		foreach ($tmp as $v) {
			$product['membershipids'][$v] = 'Y';
		}
		}
	}
	
	if (!empty($product['variantid']) && !empty($active_modules['Product_Options'])) {
		$tmp = func_query_first("SELECT * FROM $sql_tbl[variants] WHERE variantid = '$product[variantid]'");
		if (!empty($tmp)) {
			func_unset($tmp, "def");
			$product = func_array_merge($product, $tmp);
		} else {
			func_unset($product, "variantid");
		}
	}

	# Detect product thumbnail and image
	$tmp = func_query_first("SELECT image_path as image_path_T, image_x as image_x_T, image_y as image_y_T FROM $sql_tbl[images_T] WHERE id = '$product[productid]'");
	if (!empty($tmp)) {
		$product = func_array_merge($product, $tmp);
		$product['is_thumbnail'] = true;
	}

	$tmp = false;
	if (!empty($product['variantid']) && !empty($active_modules['Product_Options']) && ($current_area == "C" || $current_area == "B" || empty($current_area)))
		$tmp = func_query_first("SELECT image_path as image_path_P, image_x as image_x_P, image_y as image_y_P FROM $sql_tbl[images_W] WHERE id = '$product[variantid]'");
	if (empty($tmp))
		$tmp = func_query_first("SELECT image_path as image_path_P, image_x as image_x_P, image_y as image_y_P FROM $sql_tbl[images_P] WHERE id = '$product[productid]'");
	if (!empty($tmp)) {
		$product = func_array_merge($product, $tmp);
		$product['is_image'] = true;
	}

	unset($tmp);

    if ($current_area == 'A' || $current_area == 'P') {
        if (!empty($active_modules['Multiple_Storefronts'])) {
            $product['customer_url'] = func_get_product_link_sf($product['productid'], $current_storefront, 'customer');
        } else {
            $product['customer_url'] = ($HTTPS) ? 'https://' : 'http://';
            $product['customer_url'] .= $xcart_catalogs['customer'] . '/product.php?productid=' . $product['productid'];
        }
    }

	if ($current_area == "C" || $current_area == "B" || empty($current_area)) {
		#
		# Check if product is not available for sale
		#
		if (empty($active_modules["Egoods"]))
			$product["distribution"] = "";

		global $pconf;

		if ($product["forsale"] == "B" && empty($pconf)) {
			if (is_array(@$cart["products"])) {
				foreach ($cart["products"] as $k=>$v) {
					if ($v["productid"] == $product["productid"]) {
						$pconf = $product["productid"];
						break;
					}
				}
			}
			if (empty($pconf)) {
				x_session_register("configurations");
				global $configurations;

				if (!empty($configurations)) {
					foreach ($configurations as $c) {
						if (empty($c['steps']) || !is_array($c['steps']))
							continue;

						foreach ($c['steps'] as $s) {
							if (empty($s['slots']) || !is_array($s['slots']))
								continue;

							foreach($s['slots'] as $sl) {
								if ($sl['productid'] == $product["productid"]) {
									$pconf = $product["productid"];
									break;
								}
							}
						}
					}
				}
			}
		}

		$product['taxed_price'] = $product['price'];

		if (!$always_select && ($product["forsale"] == "N" || ($product["forsale"] == "B" && empty($pconf)))) {
			if ($redirect_if_error) {
				if ($ajax_mode == 'Y') {
					$ajax_error = 'Y';
					$ajax_redirect = 'error_message.php?product_disabled';
					return false;
				} else {
				func_header_location("error_message.php?product_disabled");
				}
			} else {
				return false;
		}
		}

		if (($current_area == "C" || empty($current_area)) && !$clear_price) {
			#
			# Calculate taxes and price including taxes
			#
			global $login;

			$product["taxes"] = func_get_product_taxes($product, $login);
		}
	}

	if (!empty($active_modules['Google_Checkout'])) {
		global $xcart_dir;
		include $xcart_dir."/modules/Google_Checkout/product_modify.php";
	}

	# Add product features
	if (!empty($active_modules['Feature_Comparison']) && $product['fclassid'] > 0) {
		$product['features'] = func_get_product_features($product['productid']);
		$product['is_clist'] = func_check_comparison($product['productid'], $product['fclassid']);
	}

	$product["producttitle"] = $product['product'];

	if ($current_area == "C" || $current_area == "B" || empty($current_area)) {
		$product["descr"] = func_eol2br($product["descr"]);
		$product["fulldescr"] = func_eol2br($product["fulldescr"]);
	}

    if ($product['google_search_term']) {
        $product['google_search_link'] = urlencode($product['google_search_term']);
    }

	#
	# Get thumbnail's URL (uses only if images stored in FS)
	#
	if ($product['is_image'])
		$product["tmbn_url_P"] = func_get_image_url($product["productid"], "P", $product['image_path_P']);

	if ($product['is_thumbnail'])
		$product["tmbn_url_T"] = func_get_image_url($product["productid"], "T", $product['image_path_T']);

	if (!$product['is_image'] && !$product['is_thumbnail']) {
		$product["tmbn_url"] = func_get_default_image("P");

	} elseif ($product['is_image']) {
		$product["tmbn_url"] = $product["tmbn_url_P"];
		$product["image_x"] = $product["image_x_P"];
		$product["image_y"] = $product["image_y_P"];

	} else {
		# Use thumbnail instead of product image for product details page
		# when product image is not available.
		# Necessary only for the image dimensions because of
		# usage in <img> tag
		$product["tmbn_url"] = $product["tmbn_url_T"];
		$product["image_x"] = $product["image_x_T"];
		$product["image_y"] = $product["image_y_T"];
	}


#
##
###
/*speed optimization*/
//	    $product['clean_urls_history'] = func_query_hash("SELECT id, clean_url FROM $sql_tbl[clean_urls_history] WHERE resource_type = 'P' AND resource_id = '".$product['productid']."' ORDER BY mtime DESC", "id", false, true);
###
##
#

    if (($current_area == 'C' || empty($current_area)) && !empty($product['upc'])) {
        $upc_len = strlen($product['upc']);
        
        $product['upc_ean_isbn'] = array();
        $product['upc_ean_isbn']['value'] = $product['upc'];
        
        switch ($upc_len) {
	
            case "8": $product['upc_ean_isbn']['type'] = func_get_langvar_by_name('lbl_ean');
		break;

            case "14": $product['upc_ean_isbn']['type'] = func_get_langvar_by_name('lbl_ean');
                break;

            case ISBN_LENGTH: $product['upc_ean_isbn']['type'] = func_get_langvar_by_name('lbl_isbn');
                break;

            case UPC_LENGTH: $product['upc_ean_isbn']['type'] = func_get_langvar_by_name('lbl_upc');
                break;

            case EAN_ISBN_LENGTH: 
                if (substr(trim($product['upc']), 0, 3) == '978') {
                    $product['upc_ean_isbn']['type'] = func_get_langvar_by_name('lbl_isbn');
                } else {
                    $product['upc_ean_isbn']['type'] = func_get_langvar_by_name('lbl_ean');
                }
                break;

            default: unset($product['upc_ean_isbn']);
        }
    }

#
##
###
        if (($current_area == 'C' || empty($current_area)) && $product["new_map_price"]>0){

                if ($product["new_map_price"] > $product["price"]){
                        $product["price"] = $product["new_map_price"];
                        $product['taxed_price'] = $product['price'];
                }

                $product["discount_avail"] = "N";
                $product["discount_slope"] = "";
                $product["discount_table"] = "";
        }
###
##
#

#
##
###
	if (!empty($product["eta_date_mm_dd_yyyy"])){
		if ($product["eta_date_mm_dd_yyyy"] > time()){
			$product["eta_date_in_future"] = "Y";

			if (($current_area == 'C' || empty($current_area)) && $product["allow_pre_orders"] != "Y"){
				$product["avail"] = "0";
			}
		}
	}

		$classProduct = new Xcart\Product(['productid'=>$product['productid']]);
		$mpn = $classProduct->getMPN();


        /*$pos = strpos($product['productcode'], '-');
        $mpn = '';
        if ($pos && is_numeric($pos) && $pos + 1 != strlen($product['productcode'])) {
  	      $mpn = substr($product['productcode'], $pos + 1);
        }*/

	    $product['mpn'] = $mpn;

	if (!empty($product["d_website_search_for_sku_url"]) && !empty($mpn) && ($current_area != 'C' && !empty($current_area))){
		$product["d_website_search_for_sku_url"] = $classProduct->getProductURLOnDistributorWebSite();
	}

	$product["prevent_search_indexing"] = func_prevent_search_indexing($product);
	if (strpos($product['prevent_search_indexing'], 'Y') !== false){
		$product["robots_noindex"] = "Y";
	}


#
## Calculate correct price for customer area
###
	$product["product_availability"] = func_product_availability(false,$product);

	if ($current_area == 'C' || empty($current_area)){
		$product["price"] = $product["taxed_price"] = func_product_price($product);

		if ($product["supplier_feeds_enabled"] == "Y" && empty($product["is_variants"]) && $product["product_availability"] == "out of stock"){
		        $product["new_notify_in_stock_price"] = $product["price"];
		}
	}
###
##
#

	$product["brand"] = func_query_first_cell("SELECT brand FROM $sql_tbl[brands] WHERE brandid='$product[brandid]'");

	if ($classProduct->splash_id) {
		$product['oSplash'] = \Xcart\Images\Splash::objects()->filter(['id' => $classProduct->splash_id , 'active' => 'Y'])->get();
	}
	return $product;
}

#
# Get delivery options by product ID
#
function func_select_product_delivery($id) {
	global $sql_tbl;

	return func_query("select $sql_tbl[shipping].*, count($sql_tbl[delivery].productid) as avail from $sql_tbl[shipping] left join $sql_tbl[delivery] on $sql_tbl[delivery].shippingid=$sql_tbl[shipping].shippingid and $sql_tbl[delivery].productid='$id' where $sql_tbl[shipping].active='Y' group by shippingid");
}

#
# Add data to service array (Group editing of products functionality)
#
function func_ge_add($data, $geid = false) {
	global $sql_tbl, $XCARTSESSID;

	if (strlen($geid) < 32)
		$geid = md5(uniqid(rand(0, time())));

	if (!is_array($data))
		$data = array($data);

	$query_data = array(
		"sessid" => $XCARTSESSID,
		"geid" => $geid
		);

	foreach ($data as $pid) {
		if (empty($pid))
			continue;
		$query_data['productid'] = $pid;
		func_array2insert("ge_products", $query_data);
	}

	return $geid;
}

#
# Get length of service array (Group editing of products functionality)
#
function func_ge_count($geid) {
	global $sql_tbl;

	return func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[ge_products] WHERE geid = '$geid'");
}

#
# Get next line of service array (Group editing of products functionality)
#
function func_ge_each($geid, $limit = 1, $productid = 0) {
	global $__ge_res, $sql_tbl;

	if (!is_bool($__ge_res) && (!is_resource($__ge_res) || strpos(@get_resource_type($__ge_res), "mysql ") !== 0)) {
		$__ge_res = false;
	}

	if ($__ge_res === true) {
		$__ge_res = false;
		return false;
	}
	elseif ($__ge_res === false) {
		$__ge_res = db_query("SELECT productid FROM $sql_tbl[ge_products] WHERE geid = '$geid'");
		if (!$__ge_res) {
			$__ge_res = false;
			return false;
		}
	}

	$res = true;
	$ret = array();
	$limit = intval($limit);
	if ($limit <= 0)
		$limit = 1;

	$orig_limit = $limit;
	while (($limit > 0) && ($res = db_fetch_row($__ge_res))) {
		if ($productid == $res[0])
			continue;
		$ret[] = $res[0];
		$limit--;
	}

	if (!$res) {
		func_ge_reset($geid);
		$__ge_res = !empty($ret);
	}

	if (empty($ret))
		return false;

	return ($orig_limit == 1) ? $ret[0] : $ret;
}

#
# Check element of service array (Group editing of products functionality)
#
function func_ge_check($geid, $id) {
	global $sql_tbl;

	return (func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[ge_products] WHERE geid = '$geid' AND productid = '$id'") > 0);
}

#
# Reset pointer of service array (Group editing of products functionality)
#
function func_ge_reset($geid) {
	global $__ge_res;

	if ($__ge_res !== false)
		@db_free_result($__ge_res);

	$__ge_res = false;
}

#
# Get stop words list
#
function func_get_stopwords($code = false) {
	global $xcart_dir, $shop_language;

	if ($code === false)
		$code = $shop_language;

	if (!file_exists($xcart_dir."/include/stopwords_".$code.".php"))
		return false;

	$stopwords = array();
	include $xcart_dir."/include/stopwords_".$code.".php";

	return $stopwords;
}

function func_add_catalog_to_cart($manufacturerid) {
	global $cart, $sql_tbl, $membershipid, $catalog_checkboxes, $added_catalogs;

	if (!empty($manufacturerid) && is_numeric($manufacturerid)) {
		
		# Get catalog info
		$catalog = func_query_first('SELECT p.productid, p.min_amount, m.catalog_price FROM '.$sql_tbl['products'].' as p RIGHT JOIN '.$sql_tbl['manufacturers'].' as m ON p.productcode=m.catalog_sku WHERE p.manufacturerid='.$manufacturerid);
		if (!empty($catalog)) {

			# Check if the customer has already deleted the catalog from the cart
			if (!isset($added_catalogs[$manufacturerid])) {

				# Add the catalog to cart with the default price
				$add_product = array();
				$add_product['productid'] = $catalog['productid'];
				$add_product['amount'] = $catalog['min_amount'];
				$add_product['product_options'] = false;
				$add_product['price'] = NULL;
				$add_product['catalog_price'] = $catalog['catalog_price'] ? @price_format(@$catalog['catalog_price']) : null;

				$result = func_add_to_cart($cart, $add_product);

				$added_catalogs[$manufacturerid] = 'Y';

				unset($catalog_checkboxes[$manufacturerid]);
			}
		}
	}
}

function func_add_catalog_checkbox_to_cart($productid) {
	global $sql_tbl, $catalog_checkboxes, $cart, $added_catalogs;

	$catalog_info = func_query_first('SELECT p.productcode, p.manufacturerid, m.catalog_text, m.catalog_sku FROM ' . $sql_tbl['products'] . ' as p LEFT JOIN ' . $sql_tbl['manufacturers'] . ' as m ON p.manufacturerid=m.manufacturerid WHERE p.productid='.$productid);

	$no_catalog_in_cart = true;
	
	// Don't show catalog checkbox if the catalog is added to cart

	if ($catalog_info && is_array($cart['products'])) {

		foreach ($cart['products'] as $cart_product) {
			if ($cart_product['productcode'] == $catalog_info['catalog_sku']) {
				$no_catalog_in_cart = false;
				break;
			}
		}
	}
	
	if ($catalog_info && is_numeric($catalog_info['manufacturerid']) && !empty($catalog_info['catalog_sku']) && !empty($catalog_info['catalog_text']) && $catalog_info['productcode'] != $catalog_info['catalog_sku'] && $no_catalog_in_cart) {

		if (!isset($catalog_checkboxes[$catalog_info['manufacturerid']]) && !isset($added_catalogs[$catalog_info['manufacturerid']])) {
			$catalog_checkboxes[$catalog_info['manufacturerid']] = $catalog_info['catalog_text'];
		}
	}

	if ($catalog_info['productcode'] == $catalog_info['catalog_sku'] && isset($catalog_checkboxes[$catalog_info['manufacturerid']])) {
		unset($catalog_checkboxes[$catalog_info['manufacturerid']]);
	}
}

function func_save_product_thumb_image($productid, $type) {
	global $file_upload_data, $geid, $fields, $sql_tbl, $skip_image, $top_message, $product_modified_data;

	x_load('image');

	if (!in_array($type, array('T', 'P'))) {
		return false;
	}
	
    $return = false;
	
	$isnt_perms = func_check_image_storage_perms($file_upload_data, $type);

	$fillerror = $isnt_perms !== true;
	
	if (!$fillerror) {
		if (!empty($productid)) {
			# If image was posted
			if (func_check_image_posted($file_upload_data, $type)) {
                $old_imageid = func_query_first_cell('SELECT imageid FROM ' . $sql_tbl['images_' . $type]
                    . ' WHERE id = "' . $productid . '"');
                if (empty($old_imageid)) {
                    $old_imageid = null;
                }
				$return = func_save_image($file_upload_data, $type, $productid, array(), $old_imageid);
			}
	
			if (($type == 'T' && $fields['thumbnail'] == 'Y' 
				|| $type == 'P' && $fields['product_image'] == 'Y') 
				&& $geid) {
				
				$img = func_addslashes(func_query_first('SELECT * FROM ' . $sql_tbl['images_' . $type] 
					. ' WHERE id = "' . $productid . '"'));
				
				unset($img['imageid']);
				
				while ($pid = func_ge_each($geid, 1, $productid)) {
					$img['id'] = $pid;
					func_array2insert($sql_tbl['images_' . $type], $img, true);
				}
			}
	
		}
			
		func_build_quick_flags($productid);

		if ($geid && !empty($fields)) {
			while ($pid = func_ge_each($geid, 100, $productid)) {
				func_build_quick_flags($pid);
			}
		}
	} else {
		if ($isnt_perms !== true) {
			$top_message['content'] = $isnt_perms['content'];
		}
			
		if ($file_upload_data[$type] && $file_upload_data[$type]['is_redirect'] && $skip_image[$type] != 'Y') {
			$file_upload_data[$type]['is_redirect'] = false;
			$product_modified_data['is_image_' . $type] = true;
		}
	
        $return = false;
	}

	if (!empty($geid_field)) {
		x_session_unregister('geid_field');
	}

    return $return;
}
function func_get_product_descr($fulldescr) {
	$descr = preg_replace('/(\s*<br\s*[\/]?>\s*(\*\s*)?)/i', '&;', $fulldescr);
	$descr = preg_replace('/([^;:\?!\.\s]{1})&;/i', '$1. ', $descr);
	$descr = preg_replace('/&;/i', ' ', $descr);
	$order = array("\r\n", "\n", "\r");
	$descr = trim(strip_tags(str_replace($order, '', $descr)));
	if (!empty($descr) && !in_array(substr($descr, -1), array(';', ':', '!', '.', '?'))) {
		$descr .= '.';
	}
	return $descr;
}

function func_rebuild_product_sf($productid) {
	global $sql_tbl, $current_storefront;

	$product_sfs = func_query_column('SELECT DISTINCT c.storefrontid FROM ' . $sql_tbl['products_categories'] . ' as pc'
		. ' LEFT JOIN ' . $sql_tbl['categories'] . ' as c ON c.categoryid = pc.categoryid'
		. ' WHERE pc.productid = "' . $productid . '"');
	if (!is_array($product_sfs)) {
		$product_sfs = array($current_storefront);
	}

	db_query('DELETE FROM ' . $sql_tbl['products_sf'] . ' WHERE productid="' . $productid . '"');

	foreach ($product_sfs as $sf) {
		if (is_numeric($sf)) {
			$sf_query = array(
				'productid' => $productid,
				'sfid'      => $sf
			);
		}
		func_array2insert('products_sf', $sf_query);
	}
}

function func_get_product_link_sf($productid, $sfid = 0, $type = 'all') {
    global $sql_tbl, $active_modules, $http_location;

    $usertypes = array('customer', 'admin', 'provider', 'partner');
    $userdirs = array(
        'customer'  => DIR_CUSTOMER, 
        'admin'     => DIR_ADMIN, 
        'provider'  => DIR_PROVIDER, 
        'partner'   => DIR_PARTNER,
    );

    if (!in_array($type, $usertypes) && $type != 'all') {
        return false;
    }

    $sfid = intval($sfid);
    $productid = intval($productid);

    $product_exists = func_query_first_cell('SELECT COUNT(productid) FROM ' . $sql_tbl['products'] . ' WHERE productid = ' . $productid);
    if (empty($product_exists)) {
        return false;
    }

    if ($sfid != 0) {
        $sf_domain = func_query_first_cell('SELECT domain FROM ' . $sql_tbl['storefronts'] . ' WHERE storefrontid = ' . $sfid);
        if (empty($sf_domain)) {
            return false;
        }
    }

    if (empty($active_modules['Multiple_Storefronts'])) {
        $link = $http_location;
        $link_adm = $link;
    } else {
        if ($sfid == 0) {
            $link = '//' . MAIN_SF_DOMAIN;
        } else {
            $link = '//' . $sf_domain;
        }
        $link_adm = '//' . MAIN_SF_DOMAIN;
    }

    $result = array();

    foreach ($usertypes as $t) {
        
        if ($t == 'customer') {
            $result[$t] = $link . $userdirs[$t] . '/product.php?productid=' . $productid;
        } else {
            $result[$t] = $link_adm . $userdirs[$t] . '/product_modify.php?productid=' . $productid . '&sf=' . $sfid;
        }
    }

    if ($type == 'all') {
        return $result;
    }

    return $result[$type];
}

/*
function my_array_sort($array, $on, $order=SORT_ASC)
{
    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
            break;
            case SORT_DESC:
                arsort($sortable_array);
            break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}
*/

#
# https://basecamp.com/2070980/projects/1577907/messages/52260744
#
function func_generate_discounts($productids, $tick = 0) {
        global $sql_tbl;

        if ($tick > 0){
                func_display_service_header("lbl_rebuild_quick_prices");
		$i = 0;
	}

        $prs = func_query_hash("SELECT p.productid, p.discount_slope, p.discount_table, pr.price, cidev_get_base_xcart_price(p.cost_to_us) as base_price, p.min_amount FROM $sql_tbl[products] as p LEFT JOIN $sql_tbl[pricing] as pr ON p.productid = pr.productid AND pr.membershipid = '0' AND pr.quantity = 1 AND pr.variantid = '0'WHERE p.productid IN ('".implode("','",$productids)."')", "productid");

       
        foreach ($prs as $productid => $p) {

			if (empty($p[0]["price"])) {
				db_query("REPLACE $sql_tbl[pricing] SET price = ".$p[0]["base_price"].", productid = $productid, quantity = 1, variantid = 0, membershipid = 0");
				$p[0]["price"] = $p[0]["base_price"];
			}

	    if ($tick > 0){
		$i = 0;
	    }

            if (strpos($p[0]["discount_table"], ":")){

                $check1_count = substr_count($p[0]["discount_table"], ':');
                $check2_count = substr_count($p[0]["discount_table"], ',')+1;


                if ($check1_count == $check2_count){

				$quantity_arr = array();
                foreach (explode(",",$p[0]["discount_table"]) as $v) {
                        $v_arr = explode(":",$v);
                        $quantity_arr[] = trim($v_arr[0]);
				}

				db_query("DELETE FROM $sql_tbl[pricing] WHERE productid='$productid' AND membershipid = '0' AND quantity > 1 AND variantid = '0' AND quantity NOT IN ('".implode("','",$quantity_arr)."')");
				unset($quantity_arr);


                foreach (explode(",",$p[0]["discount_table"]) as $v) {
                        $v_arr = explode(":",$v);

                        $quantity = trim($v_arr[0]);
						$quantity = intval($quantity);
                        $kef = trim($v_arr[1]);
                                
                        $price = $p[0]["price"] * (1 - $kef);
                        $price = price_format($price);

						db_query("INSERT INTO $sql_tbl[pricing] (productid, quantity, price) VALUES ('$productid', '$quantity', '$price') ON DUPLICATE KEY UPDATE price='$price'");

/*
                                $query_data = array(
                                                    "productid" => $productid,
                                                    "quantity" => intval($quantity),
                                                    "price" => $price,
                                                    "membershipid" => '0'
                                );
                                func_array2insert("pricing", $query_data, true);
*/
                        }
                }
            }
            else {

				$quantity_arr = array();
                foreach (explode(",",$p[0]["discount_table"]) as $v) {
                    $quantity_arr[] = trim($v);
				}

                db_query("DELETE FROM $sql_tbl[pricing] WHERE productid='$productid' AND membershipid = '0' AND quantity > 1 AND variantid = '0' AND quantity NOT IN ('".implode("','",$quantity_arr)."')");
                unset($quantity_arr);


                foreach (explode(",",$p[0]["discount_table"]) as $v) {
                        if (intval($v)) {

								$v = intval($v);
								$price = (1 - $p[0]["discount_slope"] * log($v,2) / 100) * $p[0]["price"];
								$price = price_format($price);

								db_query("INSERT INTO $sql_tbl[pricing] (productid, quantity, price) VALUES ('$productid', '$v', '$price') ON DUPLICATE KEY UPDATE price='$price'");

/*
                                $query_data = array(
                                                    "productid" => $productid,
                                                    "quantity" => intval($v),
                                                    "price" => (1 - $p[0]["discount_slope"] * log($v,2) / 100) * $p[0]["price"],
                                                    "membershipid" => '0'
                                );
                                func_array2insert("pricing", $query_data, true);
*/
                        }
                }
            }


	    $price_id = func_query_first_cell("SELECT PR.priceid FROM xcart_pricing PR WHERE PR.productid = '$productid' and PR.quantity <= '".$p[0]["min_amount"]."' and PR.variantid = 0 and PR.membershipid = 0 ORDER BY PR.quantity DESC LIMIT 1");

	    db_query("DELETE FROM xcart_quick_prices where productid = '$productid' and membershipid = 0 and variantid = 0 and priceid != '$price_id'");

//	    db_query("REPLACE INTO xcart_quick_prices (productid,priceid,variantid, membershipid) VALUES ('$productid','$price_id',0,0) ON DUPLICATE KEY UPDATE productid='$productid', priceid='$price_id', variantid='0'");
	    db_query("REPLACE INTO xcart_quick_prices (productid,priceid,variantid, membershipid) VALUES ('$productid','$price_id',0,0)");


	    $variants = func_query("SELECT variantid FROM $sql_tbl[variants] WHERE avail>0 AND productid='$productid'");
	    if (!empty($variants)){
				$variantid = func_get_default_variantid($productid);
				$price_id = func_query_first_cell("SELECT priceid FROM $sql_tbl[pricing] WHERE variantid='$variantid' AND quantity='1'");

				db_query("DELETE FROM $sql_tbl[quick_prices] WHERE productid='$productid' AND priceid!='$price_id' AND variantid>0");
				db_query("REPLACE INTO xcart_quick_prices (productid,priceid,variantid, membershipid) VALUES ('$productid','$price_id','$variantid',0)");
	    }

            if ($tick > 0 && $i % $tick == 0) {
				echo ". ";
                if (($i/$tick) % 100 == 0)
                	echo "\n";
                func_flush();
			}
	}

	if ($tick > 0){
		return $i;
	}
}

function func_prevent_search_indexing($product){
	global $sql_tbl;

	$brandid = $product["brandid"];

	$p_brand = func_query_first_cell("SELECT prevent_search_indexing_of_all_brand_products FROM $sql_tbl[brands] WHERE brandid='$brandid'");
	if (empty($p_brand)){
		$p_brand = "N";
	}


	$p_cat = "N";

	if (!empty($product["categoryid"])){
		$categoryid = $product["categoryid"];
	} else {
		$categoryid = func_query_first_cell("SELECT categoryid FROM $sql_tbl[products_categories] WHERE productid='$product[productid]' AND main='Y'");
	}

	$categoryid_path = func_query_first_cell("SELECT categoryid_path FROM $sql_tbl[categories] WHERE categoryid='$categoryid'");
	$categoryid_path_arr = explode("/", $categoryid_path);
	$categories = array();
	foreach ($categoryid_path_arr as $cat){
		if (!empty($cat)){
			$categories[] = $cat;
		}
	}

	$main_cat = func_query_first("
Select GROUP_CONCAT(C.prevent_index_products) as prevent_index_products, GROUP_CONCAT(C.avail) as avail
From xcart_categories C
Where C.categoryid IN ('".implode("','", $categories)."')
	");

	if (strpos($main_cat['avail'], 'N') !== true && strpos($main_cat['prevent_index_products'],'Y') !== false ){
		$p_cat = 'Y';
	}

	$additional_cats = func_query("SELECT categoryid FROM $sql_tbl[products_categories] WHERE productid='$product[productid]' AND main='N'");
	if (!empty($additional_cats)){
		foreach ($additional_cats as $categoryid){
			$categoryid_path = func_query_first_cell("SELECT categoryid_path FROM $sql_tbl[categories] WHERE categoryid='$categoryid'");
		        $categoryid_path_arr = explode("/", $categoryid_path);
		        $categories = array();
		        foreach ($categoryid_path_arr as $cat){
                		if (!empty($cat)){
		                        $categories[] = $cat;
                		}
		        }

		        $main_cat = func_query_first("
Select GROUP_CONCAT(C.prevent_index_products) as prevent_index_products, GROUP_CONCAT(C.avail) as avail
From xcart_categories C
Where C.categoryid IN ('".implode("','", $categories)."')
		        ");

		        if (strpos($main_cat['avail'], 'N') !== true && strpos($main_cat['prevent_index_products'],'Y') !== false ){
		                $p_cat = 'Y';
		        }
		}
	}

	$prevent_search_indexing_this_product_page = $product["prevent_search_indexing_this_product_page"];
	if (empty($prevent_search_indexing_this_product_page)){
		$prevent_search_indexing_this_product_page = "N";
	}

	$prevent_search_indexing = $p_brand."/".$p_cat."/".$prevent_search_indexing_this_product_page;

	return $prevent_search_indexing;
}
