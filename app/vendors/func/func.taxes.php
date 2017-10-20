<?php

x_load('cart');

#
# This function gathers the product taxes information
#
function func_get_product_taxes(&$product, $login, $calculate_discounted_price=false, $taxes="") {
	global $config;

	$amount = (isset($product["amount"]) ? $product["amount"] : 1);

	if ($calculate_discounted_price && isset($product["discounted_price"]))
		$price = $product["discounted_price"] / $amount;
	else
		$price = $product["price"];

	if (empty($taxes))
		$taxes = func_get_product_tax_rates($product, $login);

	$price_deducted_tax_flag = "";
	foreach ($taxes as $k=>$tax_rate) {
		if ($tax_rate["price_includes_tax"] != "Y" || $product["price_deducted_tax"] == "Y")
			continue;
		
		if (!preg_match("!\b(DST|ST)\b!", $tax_rate["formula"]))
			continue;

		if ($tax_rate["rate_type"] == "%") {
			$_tax_value = $price - $price*100/($tax_rate["rate_value"] + 100);
			$price = $price - $_tax_value;
		}
		else {
			$price = $price - $tax_rate["rate_value"];
		}

		$product["price"] = $price;
		$price_deducted_tax_flag = "Y";
	}

	if (!defined('XAOM'))
		$product["price_deducted_tax"] = $price_deducted_tax_flag;

	$taxed_price = $price;

	$formula_data["ST"] = $price;

	foreach ($taxes as $k=>$tax_rate) {
		#
		# Calculate the tax value
		#
		if (!empty($tax_rate["skip"]) || (empty($login) && $config["General"]["apply_default_country"] != "Y"))
			continue;

		$assessment = func_calculate_assessment($tax_rate["formula"], $formula_data);

		if ($tax_rate["rate_type"] == "%") {
			$tax_rate["tax_value_precise"] = $assessment * $tax_rate["rate_value"] / 100;
			$tax_rate["tax_value"] = $tax_rate["tax_value_precise"];
		}
		else {
			$tax_rate["tax_value"] = $tax_rate["tax_value_precise"] = $tax_rate["rate_value"];
		}

		$tax_rate["taxed_price"] = $price + $tax_rate["tax_value"];

		if ($tax_rate["display_including_tax"] == "Y")
			$taxed_price += $tax_rate["tax_value"];

		$taxes[$k] = $tax_rate;

		$formula_data[$k] = $tax_rate["tax_value"];
	}

	if (is_array($taxes)) {
		foreach ($taxes as $k=>$v) {
			$taxes[$k]["tax_value"] = $v["tax_value_precise"] * $amount;
		}
	}

	$product["taxed_price"] = price_format($taxed_price);

	return $taxes;

}

#
# This function generate the product tax rates array
#
function func_get_product_tax_rates($product, $login) {
	global $sql_tbl, $user_account, $config, $single_mode;
	static $saved_tax_rates = array();

	# Define input data
	$is_array = true;
	if (isset($product['productid'])) {
		$is_array = false;
		$_product = array($product['productid'] => $product);

	} else {
		$_product = array();
		foreach ($product as $k => $p) {
			$_product[$p['productid']] = $p;
		}
	}

	unset($product);

	$membershipid = $user_account["membershipid"];

	# Select taxes data
	$_taxes = func_query_hash("SELECT $sql_tbl[taxes].*, $sql_tbl[product_taxes].productid FROM $sql_tbl[taxes], $sql_tbl[product_taxes] WHERE $sql_tbl[taxes].taxid=$sql_tbl[product_taxes].taxid AND $sql_tbl[product_taxes].productid IN ('".implode("','", array_keys($_product))."') AND $sql_tbl[taxes].active='Y' ORDER BY $sql_tbl[taxes].priority", "productid");

	if (empty($_taxes) || !is_array($_taxes))
		return array();

	# Define available customer zones
	$zone_account = defined('XAOM') ? $user_account : $login;
	$tax_rates = $address_zones = $_tax_names = array();
	foreach ($_taxes as $pid => $_tax) {
		foreach ($_tax as $k => $v) {
			$_tax_names["tax_".$v['taxid']] = true;
		}
	}

	# Get tax names
	$_tax_names = func_get_languages_alt(array_keys($_tax_names));

	if ($config["Taxes"]["enable_user_tax_exemption"] == "Y") {
		#
		# Get the 'tax_exempt' feature of customer
		#
		static $_customer_tax_exempt;

		if (empty($_customer_tax_exempt)) {
			$_customer_tax_exempt = func_query_first_cell("SELECT tax_exempt FROM $sql_tbl[customers] WHERE login='$zone_account'");
		}

		if ($_customer_tax_exempt == "Y") {
			$tax_rate["skip"] = true;
		}
	}
	else {
		$_customer_tax_exempt = "";
	}

	$provider_condition = "";
	if (!$single_mode) {
		$providers = array();
		foreach($_product as $p) {
			$providers[addslashes($p['provider'])] = true;
		}
		$provider_condition = "AND $sql_tbl[tax_rates].provider IN ('".implode("','", array_keys($providers))."')";
		unset($providers);
	}

	foreach ($_product as $productid => $product) {
		if ($product['free_tax'] == 'Y' || !is_array($_taxes[$productid]) || empty($_taxes[$productid]))
			continue;

		$taxes = $_taxes[$productid];

		# Generate tax rates array
		foreach ($taxes as $k => $v) {
			if (!isset($address_zones[$product['provider']][$v["address_type"]])) {
				$address_zones[$product['provider']][$v["address_type"]] = array_keys(func_get_customer_zones_avail($zone_account, $product['provider'], $v["address_type"]));
			}
			$zones = $address_zones[$product['provider']][$v["address_type"]];

			$tax_rate = array();
			if (!empty($zones) && is_array($zones)) {
				foreach ($zones as $zoneid) {
					if (!$single_mode && isset($saved_tax_rates[$product["provider"]][$v["taxid"]][$zoneid][$membershipid])) {

						# Get saved data (by provider name, zoneid and membershipid)
						$tax_rate = $saved_tax_rates[$product["provider"]][$v["taxid"]][$zoneid][$membershipid];

					} elseif ($single_mode && isset($saved_tax_rates[$v["taxid"]][$zoneid][$membershipid])) {

						# Get saved data (by zoneid and membershipid)
						$tax_rate = $saved_tax_rates[$v["taxid"]][$zoneid][$membershipid];

					} else {

						$tax_rate = func_query_first("SELECT $sql_tbl[tax_rates].taxid, $sql_tbl[tax_rates].formula, $sql_tbl[tax_rates].rate_value, $sql_tbl[tax_rates].rate_type FROM $sql_tbl[tax_rates] LEFT JOIN $sql_tbl[tax_rate_memberships] ON $sql_tbl[tax_rate_memberships].rateid = $sql_tbl[tax_rates].rateid WHERE $sql_tbl[tax_rates].taxid = '$v[taxid]' $provider_condition AND $sql_tbl[tax_rates].zoneid = '$zoneid' AND ($sql_tbl[tax_rate_memberships].membershipid = '$membershipid' OR $sql_tbl[tax_rate_memberships].membershipid IS NULL) ORDER BY $sql_tbl[tax_rate_memberships].membershipid DESC LIMIT 1");

						if (!$single_mode) {
							# Save data (by provider name, zoneid and membershipid)
							$saved_tax_rates[$product["provider"]][$v["taxid"]][$zoneid][$membershipid] = $tax_rate;

						} else {
							# Save data (by zoneid and membershipid)
							$saved_tax_rates[$v["taxid"]][$zoneid][$membershipid] = $tax_rate;
						}
					}

					if (!empty($tax_rate))
						break;
				}
			}

			if (empty($tax_rate) || $_customer_tax_exempt == "Y") {
				if ($v["price_includes_tax"] != "Y")
					continue;
				$tax_rate = func_query_first("SELECT $sql_tbl[tax_rates].taxid, $sql_tbl[tax_rates].formula, $sql_tbl[tax_rates].rate_value, $sql_tbl[tax_rates].rate_type FROM $sql_tbl[tax_rates] LEFT JOIN $sql_tbl[tax_rate_memberships] ON $sql_tbl[tax_rate_memberships].rateid = $sql_tbl[tax_rates].rateid WHERE $sql_tbl[tax_rates].taxid='$v[taxid]' $provider_condition AND ($sql_tbl[tax_rate_memberships].membershipid = '$membershipid' OR $sql_tbl[tax_rate_memberships].membershipid IS NULL) ORDER BY $sql_tbl[tax_rates].rate_value DESC LIMIT 1");
				$tax_rate["skip"] = true;
			}

			if (empty($tax_rate["formula"]))
				$tax_rate["formula"] = $v["formula"];

			$tax_rate["rate_value"] *= 1;
			$tax_rate["tax_display_name"] = isset($_tax_names["tax_".$v["taxid"]]) ? $_tax_names["tax_".$v["taxid"]] : $v["tax_name"];

			if ($is_array) {
				$tax_rates[$productid][$v["tax_name"]] = func_array_merge($v, $tax_rate);
			} else {
				$tax_rates[$v["tax_name"]] = func_array_merge($v, $tax_rate);
			}
		}
	}

	return $tax_rates;
}

#
# This function get the taxed price
#
function func_tax_price($price, $productid=0, $disable_abs=false, $discounted_price=NULL, $customer_info="", $taxes="", $price_deducted_tax=false) {
	global $sql_tbl, $config, $active_modules, $shop_language;

	if (empty($customer_info)) {
		global $login;
		$customer_info["login"] = $login;
	}

	$return_taxes = array();

	$no_discounted_price = false;
	if (is_null($discounted_price)) {
		$discounted_price = $price;
		$no_discounted_price = true;
	}

	if ($productid > 0) {
		#
		# Get product taxes
		#
		$product = func_query_first("SELECT productid, provider, free_shipping, shipping_freight, distribution, '$price' as price FROM $sql_tbl[products] WHERE productid='$productid'");

		$taxes = func_get_product_tax_rates($product, $customer_info["login"]);
	}
	
	$total_tax_cost = 0;

	if (is_array($taxes)) {
		#
		# Calculate price and tax_value
		#
		foreach ($taxes as $k=>$tax_rate) {
			if ($tax_rate["price_includes_tax"] != "Y" || $price_deducted_tax)
				continue;

			if (!preg_match("!\b(DST|ST)\b!S", $tax_rate["formula"]))
				continue;

			if ($tax_rate["rate_type"] == "%") {
				$_tax_value = $price - $price*100/($tax_rate["rate_value"] + 100);
				$price -= $_tax_value;
				if ($discounted_price > 0)
					$_tax_value = $discounted_price - $discounted_price*100/($tax_rate["rate_value"] + 100);

				$discounted_price -= $_tax_value;

			}
			else {
				$price -= $tax_rate["rate_value"];
				$discounted_price -= $tax_rate["rate_value"];
			}
		}

		$taxed_price = $discounted_price;

		$formula_data["ST"] = $price;
		if (!$no_discounted_price)
			$formula_data["DST"] = $discounted_price;

		foreach ($taxes as $k=>$v) {
			if (!empty($v["skip"]))
				continue;

			if ($v["display_including_tax"] != "Y")
				continue;
			if ($v["rate_type"] == "%") {
				$assessment = func_calculate_assessment($v["formula"], $formula_data);
				$tax_value = $assessment * $v["rate_value"] / 100;
			}
			elseif (!$disable_abs) {
				$tax_value = $v["rate_value"];
			}

			$formula_data[$v["tax_name"]] = $tax_value;

			$total_tax_cost += $tax_value;

			$taxed_price += $tax_value;

			$return_taxes["taxes"][$v["taxid"]] = $tax_value;
		}
	}

	$return_taxes["taxed_price"] = $taxed_price;
	$return_taxes["net_price"] = $taxed_price - $total_tax_cost;

	return $return_taxes;
}

#
# This function cacluate the assessment according to the formula string
#
function func_calculate_assessment($formula, $formula_data) {
	$return = 0;
	if (is_array($formula_data)) {
		# Correct the default values...
		if (is_null($formula_data["DST"]))
			$formula_data["DST"] = $formula_data["ST"];

		if (empty($formula_data["SH"]))
			$formula_data["SH"] = 0;

		# Preparing math expression...
		$_formula = $formula;
		foreach ($formula_data as $unit=>$value) {
			if (!is_numeric($value))
				$value = 0;

			$_formula = preg_replace("/\b".preg_quote($unit,"/")."\b/S", $value, $_formula);
		}

		$to_eval = "\$return = $_formula;";
		# Perform math expression...
		eval($to_eval);
	}

	return $return;
}

