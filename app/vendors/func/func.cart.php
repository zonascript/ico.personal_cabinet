<?php

x_load('files','user','taxes');

# START: random:20460 [2010 Mar 18 13:43] 
//function func_is_customer_free_ship_zone($zoneid, $userinfo, $provider, $for_manufacturerid=0, $type="") 
function func_is_customer_free_ship_zone($zoneid, $userinfo, $provider) {

	if ($zoneid < 0) {
		$return["is_customer_free_ship_zone"] = false;
//		return false;
		return $return;
	}

//	$ship_zones = func_get_customer_zones_avail($userinfo, $provider, "S", $zoneid, $for_manufacturerid, $type); // del zoneid
	$ship_zones = func_get_customer_zones_avail($userinfo, $provider, "S","0","");

	if (!is_array($ship_zones)) {
		# default zone
		$ship_zones = array('0' => 0);
	}

	$return_value = array_key_exists($zoneid, $ship_zones);

	$return["is_customer_free_ship_zone"] = $return_value;
	$return["ship_zones"] = $ship_zones;

	return $return;

}

# END: random:20460 [2010 Mar 18 13:43] 
#
# Get the customer's zone
#
function func_get_customer_zone_ship ($username, $provider, $type, $for_manufacturerid=0, $iShippingMethod = null) {
	global $sql_tbl;
	global $single_mode;
	$zone = null;

	if (empty($for_manufacturerid)) {
        $for_manufacturerid = 0;
	}
#
##
###
	if (!empty($username) && is_array($username) && $username["usertype"] == "C" && $username["s_country"] == "US" && !empty($username["s_zipcode"]) && empty($username["s_state"])){

		$username_state_city_info = func_query_first("SELECT region as s_state, city as s_city FROM $sql_tbl[geo_litecity_location] WHERE country='US' AND postalCode='".$username["s_zipcode"]."'");

		if (!empty($username_state_city_info["s_state"])){
			$username["s_state"] = $username_state_city_info["s_state"];
			$username["s_statename"] = func_get_state($username_state_city_info["s_state"], "US");
		}

		if (empty($username["s_city"]) && !empty($username_state_city_info["s_city"])){
			$username["s_city"] = $username_state_city_info["s_city"];
		}
	}
###
##
#

	$zones = func_get_customer_zones_avail($username, $provider, "S", $for_manufacturerid, $type);

	$provider_condition = ($single_mode) ? "" : " AND provider='".addslashes($provider)."'";

	if (isset($iShippingMethod) && $iShippingMethod >= 0 && $for_manufacturerid) {
		$manufMethodZones = array();
		$manufMethodZones = func_query_column("SELECT zoneid
												FROM $sql_tbl[shipping_rates]
												WHERE  type='$type' $provider_condition  AND manufacturerid = $for_manufacturerid AND shippingid = $iShippingMethod
												GROUP BY zoneid");
		foreach ($zones as $iZoneId => $iCount) {
			if (!in_array($iZoneId, array_values($manufMethodZones))) unset($zones[$iZoneId]);
		}
	}




	if (is_array($zones)) {
		reset($zones);
		$zone = key($zones); #extract first zone
	}

	return $zone;
}

#
# Get the customer's zones
#
function func_get_customer_zones_avail ($username, $provider, $address_type="S", $for_manufacturerid=0, $type = "") {
	global $sql_tbl, $config, $single_mode;

//$zoneid  = "-1" - for Free shipping for destination  https://basecamp.com/2070980/projects/1577907/messages/53254308

	static $z_flags = array (
		"C" => 0x01,
		"S" => 0x02,
		"G" => 0x04,
		"T" => 0x08,
		"Z" => 0x10,
		"A" => 0x20);
	static $zone_element_types = array (
		"S" => "state",
		"G" => "county",
		"T" => "city",
		"Z" => "zipcode",
		"A" => "address");
	static $results_cache = array();

	if (empty($for_manufacturerid)) {
        $for_manufacturerid = 0;
	}

	if ($config["General"]["use_counties"] != "Y") {
		unset($z_flags["G"]);
		unset($zone_element_types["G"]);
	}

	# Define which address type should be compared
	if ($address_type == "B")
		$address_prefix = "b_";
	else
		$address_prefix = "s_";

	$zones = array();

	if (is_array($username)) {
		$customer_info = $username;
	}
	elseif (!empty($username)) {
		$customer_info = func_userinfo($username, "C");
	}
	elseif ($config["General"]["apply_default_country"] == "Y") {
		# Set the default user address
		$customer_info[$address_prefix."country"] = $config["General"]["default_country"];
		$customer_info[$address_prefix."state"] = $config["General"]["default_state"];
		$customer_info[$address_prefix."county"] = $config["General"]["default_county"];
		$customer_info[$address_prefix."zipcode"] = $config["General"]["default_zipcode"];
		$customer_info[$address_prefix."city"] = $config["General"]["default_city"];
	}

# START: random:17710_17631 [2009 Mar 26 09:25] 
	if (defined('IS_SHIPPING_QUOTE')) {
		global $shipquote_userinfo;
		if (empty($customer_info))
			$customer_info = array();
		$customer_info = array_merge($customer_info, $shipquote_userinfo);
	}

# END: random:17710_17631 [2009 Mar 26 09:25] 
	$customer_login = "";


	if (!empty($customer_info)) {
		$customer_login = $customer_info["login"];

		#
		# Check local zones cache
		#
		$data_key = md5($customer_login . $provider . $customer_info[$address_prefix."country"] . $customer_info[$address_prefix."state"] . $customer_info[$address_prefix."county"] . $customer_info[$address_prefix."zipcode"] . $customer_info[$address_prefix."city"]);

		if (isset($results_cache[$data_key])){
//			return $results_cache[$data_key]; // Do not uncomment out it
		}

		#
		# Generate the zones list
		#
		$provider_condition = ($single_mode ? "" : "AND provider='$provider'");

#
##
###
/*
if (isset($_GET["mode"]) && $_GET["mode"] == "checkout" && isset($_GET["paymentid"]) && $_GET["paymentid"] > 0){
        global $cart;

        if (isset($cart["shippingids"][$for_manufacturerid]) && $cart["shippingids"][$for_manufacturerid] > 0 && isset($cart["all_shippings"][$for_manufacturerid]) && is_array($cart["all_shippings"][$for_manufacturerid])){
                foreach ($cart["all_shippings"][$for_manufacturerid] as $ks => $vs){
                        if ($vs["shippingid"] == $cart["shippingids"][$for_manufacturerid]){
                                if (!empty($vs["code"])){
                                        $type = "R";
                                }
                                else {
                                        $type = "D";
                                }
                                break;
                        }
                }
        }
}
###
##
#
*/

		if (1==2/*$customer_info[$address_prefix."country"] != "US" || empty($type) || empty($for_manufacturerid)*/)

		{

			// empty for_manufacturerid for free_shipping = Y AND for getting all avalable zones for taxes

			# Possible zones for customer's country...
			$possible_zones = func_query("SELECT $sql_tbl[zone_element].zoneid FROM $sql_tbl[zone_element], $sql_tbl[zones] WHERE $sql_tbl[zone_element].zoneid=$sql_tbl[zones].zoneid AND $sql_tbl[zone_element].field='".$customer_info[$address_prefix."country"]."'  AND $sql_tbl[zone_element].field_type='C' $provider_condition GROUP BY $sql_tbl[zone_element].zoneid");

			if (is_array($possible_zones)) {

				$zones_completion = array();
				$_possible_zones = array();
				foreach ($possible_zones as $pzone) {
					$_possible_zones[$pzone["zoneid"]] = func_query_column("SELECT field_type FROM $sql_tbl[zone_element] WHERE zoneid='$pzone[zoneid]' AND field<>'%'");
				}

				foreach ($_possible_zones as $_pzoneid=>$_elements) {
					if (is_array($_elements)) {
						foreach ($_elements as $k=>$v) {
							$zones_completion[$_pzoneid] += $z_flags[$v];
						}
					}
				}

				$cs_state = $customer_info[$address_prefix."state"];
				$cs_country = $customer_info[$address_prefix."country"];
				$cs_pair = $cs_country."_".$cs_state;

				$empty_condition = " AND $sql_tbl[zone_element].field<>'%'";

				foreach ($possible_zones as $pzone) {
					$zones[$pzone["zoneid"]] = $z_flags["C"];

					# If only country is defined for this zone, skip further actions
					if ($zones_completion[$pzone["zoneid"]] == $z_flags["C"])
						continue;

					foreach ($z_flags as $field_type=>$field_type_flag) {

						if ($field_type == "C")
							continue;

						if ($zones_completion[$pzone["zoneid"]] & $field_type_flag) {
							# Checking the field for  equal...

							if ($field_type == "S") {
								# Checking the state...
								$found_zones = func_query_first_cell("SELECT zoneid FROM $sql_tbl[zone_element], $sql_tbl[states] WHERE $sql_tbl[zone_element].field='".addslashes($cs_pair)."' AND $sql_tbl[zone_element].field_type='S' AND $sql_tbl[states].code='".addslashes($cs_state)."' AND $sql_tbl[states].country_code='".addslashes($cs_country)."' AND $sql_tbl[zone_element].zoneid='$pzone[zoneid]'");
							} elseif ($field_type == "G") {
								# Checking the county...
								$found_zones = func_query_first_cell("SELECT zoneid FROM $sql_tbl[zone_element] WHERE field_type='G' AND field='".$customer_info[$address_prefix."county"]."' AND zoneid='$pzone[zoneid]'");
							}
							else {
								# Checking the rest fields (city, zipcode, address)
								$found_zones = func_query_first_cell("SELECT $sql_tbl[zone_element].zoneid FROM $sql_tbl[zone_element], $sql_tbl[zones] WHERE $sql_tbl[zone_element].zoneid=$sql_tbl[zones].zoneid AND $sql_tbl[zone_element].field_type='$field_type' AND '".addslashes($customer_info[$address_prefix.$zone_element_types[$field_type]])."' LIKE $sql_tbl[zone_element].field  AND $sql_tbl[zone_element].zoneid='$pzone[zoneid]' $empty_condition $provider_condition");
							}

							if (!empty($found_zones)) {
								# Field is found: increase the priority
								$zones[$pzone["zoneid"]] += $field_type_flag;
							}
							else {
								# Remove zone from available zones list
								unset($zones[$pzone["zoneid"]]);
								continue;
							}
						}
					} # /foreach ($z_flags)
				} # /foreach ($possible_zones)
			}
			$zones[0] = 0;
			arsort($zones, SORT_NUMERIC);
		}
		else {
			$cs_state = $customer_info[$address_prefix."state"];
			$cs_country = $customer_info[$address_prefix."country"];
			$sCA_ST = $cs_country."_".$cs_state;

$possible_zones = func_query($possible_zones_query = <<<SQL
SELECT ZE.zoneid, COUNT(DISTINCT ZES.field) cnt
FROM xcart_zone_element AS ZE
INNER JOIN xcart_zone_element AS ZES USING (zoneid, field_type)
INNER JOIN xcart_shipping_rates SR ON SR.manufacturerid = {$for_manufacturerid} AND ZE.zoneid = SR.zoneid AND SR.type='{$type}'
WHERE ZE.field_type = 'S' AND ZE.field ='{$sCA_ST}'
GROUP BY ZE.zoneid 
UNION
SELECT zoneid, 999999999
FROM xcart_shipping_rates
WHERE manufacturerid = {$for_manufacturerid} AND zoneid = 0 AND type='R'
GROUP BY zoneid
ORDER BY cnt
SQL
);

//Order By COUNT(SR.rateid)
###########
			if (!empty($possible_zones)){
				foreach ($possible_zones as $pos_zone) {
					$zones[$pos_zone['zoneid']] =  $pos_zone['cnt'];
				}
			}

		}
	}



	if (!empty($customer_login)) {
		$results_cache[$data_key] = $zones;

	}

	return $zones;
}

function func_get_products_providers ($products) {
	if (empty($products) || !is_array($products))
		return array();

	$providers = array ();
	foreach ($products as $product)
		$providers[$product["provider"]] = 1;

	return array_keys($providers);
}

#
# Will return array of products with preserved indexes
#
function func_get_products_by_provider ($products, $provider) {
	global $single_mode;

	if (!is_array($products) || empty($products))
		return array();

	if ($single_mode) return $products;

	$result = array ();
	foreach ($products as $k=>$product) {
		if ($product["provider"] == $provider)
			$result[$k] = $product;
	}

	return $result;
}

#
# This function do real shipping calcs
#
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
# START: random:17710_17631 [2009 Mar 26 09:25] 
function func_real_shipping($delivery, $for_manufacturerid = 0, $approximation_intershipper_rates = "") {
# END: random:17710_17631 [2009 Mar 26 09:25] 
	global $intershipper_rates, $sql_tbl, $intershipper_rates_all;

#
##
###
	if (
		!empty($approximation_intershipper_rates) && is_array($approximation_intershipper_rates) && 
		!empty($intershipper_rates) && is_array($intershipper_rates)
	){

		foreach($intershipper_rates as $k => $v){
			foreach ($approximation_intershipper_rates as $kk => $vv){
				if ($v["methodid"] == $vv["methodid"]){
					$intershipper_rates[$k]["rate"] = $vv["rate"];

					if (!empty($intershipper_rates_all[$for_manufacturerid]) && is_array($intershipper_rates_all[$for_manufacturerid])){
						foreach ($intershipper_rates_all[$for_manufacturerid] as $kkk => $vvv){
							if ($vvv["methodid"] == $vv["methodid"]){
								$intershipper_rates_all[$for_manufacturerid][$kkk]["rate"] = $vv["rate"];
							}
						}
					} else {
						$intershipper_rates_all[$for_manufacturerid] = $intershipper_rates;
					}
				}
			}
		}


//		$intershipper_rates = $approximation_intershipper_rates;
//		$intershipper_rates_all[$for_manufacturerid] = $intershipper_rates;

//func_print_r($intershipper_rates_all, $intershipper_rates);
//func_print_r($approximation_intershipper_rates);

	}
###
##
#

//func_print_r($intershipper_rates_all, $intershipper_rates);
//func_print_r($approximation_intershipper_rates);

# START: random:17710_17631 [2009 Mar 26 09:25] 
	if (!empty($intershipper_rates_all[$for_manufacturerid]))
# END: random:17710_17631 [2009 Mar 26 09:25] 
		$intershipper_rates = $intershipper_rates_all[$for_manufacturerid];
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
	$shipping_codes = func_query_first("select code, subcode from $sql_tbl[shipping] where shippingid='$delivery'");

	if (!empty($intershipper_rates) && is_array($intershipper_rates)) {
		foreach($intershipper_rates as $rate) {
			if ($rate['methodid'] == $shipping_codes['subcode']) {
				$surcharge = (!empty($rate['oversize_surcharge'])) ? $rate['oversize_surcharge'] : 0;

//func_print_r($rate['rate']);

				return array('rate' => $rate['rate'], 'surcharge' => $surcharge);
		        }
		}
	}

	return array('rate' => '0.00','surcharge' => '0.00');
}

#
# This function calculates costs of contents of shopping cart
#
function func_calculate($cart, $products, $login, $login_type, $paymentid=NULL) {
	global $config, $single_mode, $sql_tbl;
# START: random:17710_17631 [2009 Mar 26 09:25] 
	global $xcart_dir, $active_modules;
# END: random:17710_17631 [2009 Mar 26 09:25] 
	$return = array ();
	$return ["orders"] = array ();

	if ($active_modules["Special_Offers"]) {
		include $xcart_dir."/modules/Special_Offers/calculate_init.php";
	}

	if ($single_mode) {
		$result = func_calculate_single ($cart, $products, $login, $login_type);
		$return = $result;
		$return ["orders"][0] = $result;
		$return ["orders"][0]["provider"] = (!empty($products) ? $products[0]["provider"] : "");
		if ($active_modules["Special_Offers"]) {
			include $xcart_dir."/modules/Special_Offers/calculate_return.php";
		}
	}
	else {
		$products_providers = func_get_products_providers ($products);

		$key = 0;

		foreach ($products_providers as $provider_for) {
			$_products = func_get_products_by_provider ($products, $provider_for);

			$result = func_calculate_single ($cart, $_products, $login, $login_type, $provider_for);

#
##
###
			$return ['additional_fee'] = $result['additional_fee'];
###
##
#

# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
			$return ['shipping_groups'] = $result['shipping_groups'];
			$return ['shipping_costs'] = $result['shipping_costs'];
			$return ['display_shipping_costs'] = $result['display_shipping_costs'];
# START: random:20341 [2010 Jul 29 14:46] 
			$return ['shipping_taxes'] = $result['shipping_taxes'];
# END: random:20341 [2010 Jul 29 14:46] 
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
			$return ["total_cost"] += $result ["total_cost"];
			$return ["shipping_cost"] += $result ["shipping_cost"];
			$return ["display_shipping_cost"] += $result ["display_shipping_cost"];
			$return ["tax_cost"] += $result ["tax_cost"];
			$return ["discount"] += $result ["discount"];
			if ($result["coupon"]) {
				$return ["coupon"] = $result ["coupon"];
			}
			$return ["coupon_discount"] += $result ["coupon_discount"];
			$return ["subtotal"] += $result ["subtotal"];
			$return ["display_subtotal"] += $result ["display_subtotal"];
			$return ["discounted_subtotal"] += $result ["discounted_subtotal"];
			$return ["display_discounted_subtotal"] += $result ["display_discounted_subtotal"];
			$return ["products"] = func_array_merge($return ["products"], $result ["products"]);

			if (empty($return["taxes"])) {
				$return["taxes"] = $result["taxes"];
			}
			elseif (is_array($result["taxes"])) {
				foreach ($result["taxes"] as $k=>$v) {
					if (in_array($k, array_keys($return["taxes"]))) {
						$return["taxes"][$k]["tax_cost"] += $v["tax_cost"];
						$return["taxes"][$k]["tax_cost_no_shipping"] += $v["tax_cost_no_shipping"];
					}
					else
						$return["taxes"][$k] = $v;
				}
			}

			$return ["orders"][$key] = $result;
			$return ["orders"][$key]["provider"] = $provider_for;

			if ($active_modules["Special_Offers"]) {
				include $xcart_dir."/modules/Special_Offers/calculate_return.php";
			}

			$key ++;
		}

		if (!empty($cart["giftcerts"])) {
			$_products = array ();
			$result = func_calculate_single ($cart, $_products, $login, $login_type);
			$return ["total_cost"] += $result ["total_cost"];
			$return ["shipping_cost"] += $result ["shipping_cost"];
			$return ["display_shipping_cost"] += $result ["display_shipping_cost"];
			$return ["tax_cost"] += $result ["tax_cost"];
			$return ["discount"] += $result ["discount"];
			$return ["subtotal"] += $result ["subtotal"];
			$return ["display_subtotal"] += $result ["display_subtotal"];
			$return ["discounted_subtotal"] += $result ["discounted_subtotal"];
			$return ["display_discounted_subtotal"] += $result ["display_discounted_subtotal"];
			$return ["coupon_discount"] += $result ["coupon_discount"];

			$return ["orders"][$key] = $result;
			$return ["orders"][$key]["provider"] = ""; #$provider_for;
			$key++;
		}
	}

	$_payment_surcharge = 0;
	if ($paymentid !== NULL) {
		#
		# Apply the payment method surcharge or discount
		#
		$_payment_surcharge = func_payment_method_surcharge($return["total_cost"], $paymentid);

		if ($_payment_surcharge != 0) {
			$_payment_surcharge = price_format($_payment_surcharge);
			$return["total_cost"] += $_payment_surcharge;
			$return["payment_surcharge"] = $_payment_surcharge;
			$return["paymentid"] = $paymentid;

			if (!$single_mode) {
				# Distribute the payment method surcharge or discount among orders
				$_payment_surcharge_part = price_format($_payment_surcharge / count($return["orders"]));
				for ($i=0; $i<count($return["orders"])-1; $i++) {
					$return["orders"][$i]["total_cost"] += $_payment_surcharge_part;
					$return["orders"][$i]["payment_surcharge"] = $_payment_surcharge_part;
				}

				$_payment_surcharge_rest = price_format($_payment_surcharge - ($_payment_surcharge_part * (count($return["orders"])-1)));
				$return["orders"][count($return["orders"])-1]["total_cost"] += $_payment_surcharge_rest;
				$return["orders"][count($return["orders"])-1]["payment_surcharge"] = $_payment_surcharge_rest;
			}
		}
		else {
			$return["payment_surcharge"] = 0;
			if (!$single_mode) {
				for ($i=0; $i<count($return["orders"]); $i++)
					$return["orders"][$i]["payment_surcharge"] = 0;
			}
		}
	}

	$return["display_cart_products_tax_rates"] = "N";
	$return["product_tax_name"] = "";
	if ($config["Taxes"]["display_cart_products_tax_rates"] == "Y") {
		$_taxes = array();
		foreach ($return["orders"] as $k=>$v) {
			if (is_array($v["products"])) {
				foreach ($v["products"] as $i=>$j) {
					if (!is_array(@$j["taxes"]))
						continue;

					foreach ($j["taxes"] as $_tn=>$_tax) {
						if ($_tax["tax_value"] == 0)
							continue;
						
						if (!isset($_taxes[$_tn]))
							$_taxes[] = $_tax["tax_display_name"];
					}
				}
			}
		}
		
		if (count($_taxes) > 0) {
			$return["display_cart_products_tax_rates"] = "Y";
			if (count($_taxes) == 1)
				$return["product_tax_name"] = $_taxes[0];
		}
	}

	#
	# Recalculating applied gift certificates
	#
	$giftcert_cost = 0;
	$applied_giftcerts = array();
	if (!empty($cart["applied_giftcerts"])) {
		$gc_payed_sum = 0;
		$applied_giftcerts = array();
		foreach ($cart["applied_giftcerts"] as $k=>$v) {
			if (($gc_payed_sum + $v["giftcert_cost"]) <= $return["total_cost"]) {
				$gc_payed_sum += $v["giftcert_cost"];
				$applied_giftcerts[] = $v;
				continue;
			}

			db_query("UPDATE $sql_tbl[giftcerts] SET status='A' WHERE gcid='$v[giftcert_id]'");
		}

		$giftcert_cost = $gc_payed_sum;
	}

	if ($return["total_cost"] >= $giftcert_cost)
		$return["giftcert_discount"] = $giftcert_cost;
	else
		$return["giftcert_discount"] = $giftcert_cost - $return["total_cost"];

	$return["total_cost"] = price_format($return["total_cost"] - $return["giftcert_discount"]);
	$return["applied_giftcerts"] = $applied_giftcerts;

#
## 14.02.2014
###
	if (!empty($return["additional_fee"]) && is_array($return["additional_fee"])){
		foreach ($return["additional_fee"] as $k => $v){
			if (!empty($v["additional_fee_value"])){
				$return["total_cost"] += price_format($v["additional_fee_value"]);
			}
		}
	}
###
##
#
	if ($single_mode) {
		$return ["orders"][0]["total_cost"] = $return["total_cost"];
	}
	elseif (is_array($applied_giftcerts)) {
		#
		# Apply GC to all orders in cart in single_mode Off
		#
		foreach ($return["orders"] as $k=>$order) {
			$giftcert_discount = 0;
			foreach ($applied_giftcerts as $k1=>$applied_giftcert) {
				if ($applied_giftcert["giftcert_cost"] == 0)
					continue;

				if ($applied_giftcert["giftcert_cost"] > $order["total_cost"])
					$applied_giftcert["giftcert_cost"] = $order["total_cost"];

				$giftcert_discount += $applied_giftcert["giftcert_cost"];
				$order["total_cost"] -= $applied_giftcert["giftcert_cost"];
				$applied_giftcert["giftcert_cost"] = price_format($applied_giftcert["giftcert_cost"]);
				$applied_giftcerts[$k1]["giftcert_cost"] = $applied_giftcert["giftcert_cost"];
				$return["orders"][$k]["applied_giftcerts"][] = $applied_giftcert;
				$return["orders"][$k]["giftcert_discount"] = price_format($giftcert_discount);
			}

			$return["orders"][$k]["total_cost"] = price_format($return["orders"][$k]["total_cost"] - $return["orders"][$k]["giftcert_discount"]);
		}
	}
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
	$return['whole_taxes'] = $result['whole_taxes'];
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
	return $return;
}

#
# This function distributes the discount among the product prices and
# decreases the subtotal
#
function func_distribute_discount($field_name, $products, $discount, $discount_type, $avail_discount_total=0, $taxes=array()) {
	global $config;

	$sum_discount = 0;
	$return = array();
	$_orig_discount = $taxed_discount = $discount;

	if (!empty($taxes) && $config["Taxes"]["display_taxed_order_totals"] == "Y" && $config["Taxes"]["apply_discount_on_taxed_amount"] == "Y") {
		if ($discount_type=="absolute") {
			$_taxes = func_tax_price($discount, 0, false, NULL, "", $taxes, false);
			$taxed_discount = $_taxes ["net_price"];
		}
		else {
			$_taxes = func_tax_price($discount, 0, false, NULL, "", $taxes, true);
			$taxed_discount = $_taxes ["taxed_price"];
		}
	}

	if ($discount_type=="absolute" && $avail_discount_total > 0) {
		# Distribute absolute discount among the products
		$index = 0;
		$_considered_sum_discount = 0;
		$_total_discounted_products = 0;
		foreach ($products as $k=>$product) {
			if (@$product["deleted"]) continue; # for Advanced_Order_Management module
			if ($product['hidden'])
				continue;
			$_total_discounted_products++;
		}
		foreach ($products as $k=>$product) {
			if (@$product["deleted"]) continue; # for Advanced_Order_Management module
			if ($product['hidden'])
				continue;
			$index++;
			if ($field_name == "coupon_discount" || $product["discount_avail"] == "Y") {
				$koefficient = $product["price"] / $avail_discount_total;
				if ($index < $_total_discounted_products) {
					$products[$k][$field_name] = price_format($taxed_discount * $koefficient * $product["amount"]);
					$products[$k]["taxed_".$field_name] = price_format($taxed_discount * $koefficient * $product["amount"]);

					$_considered_sum_discount += $products[$k][$field_name];
					$_considered_sum_taxed_discount += $products[$k]["taxed_".$field_name];
				}
				else {
					$products[$k][$field_name] = $taxed_discount - $_considered_sum_discount;
					$products[$k]["taxed_".$field_name] = $taxed_discount - $_considered_sum_taxed_discount;
				}

				$products[$k]["discounted_price"] = max($products[$k]["discounted_price"] - $products[$k][$field_name], 0.00);
			}
		}
	}
	elseif ($discount_type=="percent") {
		# Distribute percent discount among the products
		foreach ($products as $k=>$product) {
			if (@$product["deleted"]) continue; # for Advanced_Order_Management module
			if ($product['hidden'])
				continue;

			if ($field_name == "coupon_discount" || $product["discount_avail"] == "Y") {
				$products[$k][$field_name] = price_format($product["price"] * $discount / 100 * $product["amount"]);
				if ($taxed_discount != $discount) {
					if ($product["display_price"] > 0)
						$_price = $product["display_price"];
					else
						$_price = $product["taxed_price"];
					$products[$k]["taxed_".$field_name] = price_format($_price * $_orig_discount / 100 * $product["amount"]);
				}
				else
					$products[$k]["taxed_".$field_name] = $products[$k][$field_name];

				$products[$k]["discounted_price"] = max($product["discounted_price"] - $products[$k][$field_name], 0.00);
			}
		}
	}

	foreach($products as $product) {
		if (@$product["deleted"]) continue; # for Advanced_Order_Management module
		if ($product['hidden'])
			continue;

		$sum_discount += $product["taxed_".$field_name];
	}

	if ($discount_type == "absolute" && $sum_discount > $discount)
		$sum_discount = $discount;

	if ($discount_type=="percent")
		$return[$field_name."_orig"] = $sum_discount;
	else
		$return[$field_name."_orig"] = $_orig_discount;

	$return["products"] = $products;
	$return[$field_name] = $sum_discount;

	return $return;
}

#
# Sort discounts in func_calculate_discounts in descent order
#
function func_sort_max_discount($a, $b) {
	return $b['max_discount'] - $a['max_discount'];
}

#
# This function calculates discounts on subtotal
#
function func_calculate_discounts($membershipid, $products, $discount_coupon = "", $provider="") {
	global $sql_tbl, $config, $active_modules, $single_mode, $global_store, $current_storefront;

	#
	# Prepare provider condition for discounts gathering
	#
	$provider_condition = ($single_mode ? "" : "AND provider='$provider'");

	if (!empty($active_modules['Multiple_Storefronts'])) {
		$sf_condition = "AND $sql_tbl[discounts].storefrontid=$current_storefront";
	} else {
		$sf_condition = '';
	}

	#
	# Search for subtotal to apply the global discounts
	#
	$avail_discount_total = 0;
	$total = 0;
	$_taxes = array();
	foreach($products as $k=>$product) {
		if (@$product["deleted"]) continue; # for Advanced_Order_Management module
		if ($product['hidden'])
			continue;

		$products[$k]["discount"] = 0;
		$products[$k]["coupon_discount"] = 0;
		
		if ($products[$k]["product_type"] == 'C')
			continue;
		
		$products[$k]["discounted_price"] = $product["price"] * $product["amount"];
		if ($product["discount_avail"] == "Y")
			$avail_discount_total += $product["price"] * $product["amount"];

		$total += $product["price"] * $product["amount"];
	
		if ($config["Taxes"]["apply_discount_on_taxed_amount"] == "Y" && is_array($product["taxes"]))
			$_taxes = func_array_merge($_taxes, $product["taxes"]);
	}

	$return = array(
		"discount" => 0,
		"coupon_discount" => 0,
		"discount_coupon" => $discount_coupon,
		"products" => $products);

	if ($avail_discount_total > 0) {
		#
		# Calculate global discount
		#
		if (!empty($global_store['discounts'])) {
			$discount_info = array();
			$__discounts = $global_store['discounts'];
			foreach ($__discounts as $k => $v) {
				if ($v['discount_type'] == 'absolute') {
					$__discounts[$k]['max_discount'] = $v['discount'];
				} else {
					$__discounts[$k]['max_discount'] = $avail_discount_total*$v['discount']/100;
				}
			}

			usort($__discounts, "func_sort_max_discount");

			foreach ($__discounts as $v) {
				if (($v['__override']) || ($v['minprice'] <= $avail_discount_total && (empty($v['memberships']) || @in_array($membershipid, $v['memberships'])) && ($single_mode || $v['provider'] == $provider))) {
					$discount_info = $v;
					break;
				}
			}

			unset($__discounts);
		}
		else {

			$max_discount_str =
"IF ($sql_tbl[discounts].discount_type='absolute', $sql_tbl[discounts].discount, ('$avail_discount_total' * $sql_tbl[discounts].discount / 100)) as max_discount ";

			$discount_info = func_query_first("SELECT $sql_tbl[discounts].*, $max_discount_str FROM $sql_tbl[discounts] LEFT JOIN $sql_tbl[discount_memberships] ON $sql_tbl[discounts].discountid = $sql_tbl[discount_memberships].discountid WHERE minprice<='$avail_discount_total' $provider_condition $sf_condition AND ($sql_tbl[discount_memberships].membershipid IS NULL OR $sql_tbl[discount_memberships].membershipid = '$membershipid') ORDER BY max_discount DESC");
		}

		if (!empty($discount_info) && $discount_info['discount_type'] == 'percent' && $discount_info['discount'] > 100)
			unset($discount_info);

		if (!empty($discount_info)) {

			if ($discount_info['discount_type'] == 'absolute' && $discount_info['discount'] > $total) {
				$discount_info['discount'] = 100;
				$discount_info['discount_type'] = 'percent';
			}

			$return["discount"] += price_format($discount_info['max_discount']);
			#
			# Distribute the discount among the products prices
			#
			$updated = func_distribute_discount("discount", $products, $discount_info["discount"], $discount_info["discount_type"], $avail_discount_total, $_taxes);
			#
			# $products and $discount are extracted from the array $updated
			#
			extract($updated);
			unset($updated);
			$return["products"] = $products;
			$return["discount"] = $discount;
			if (isset($discount_orig))
				$return["discount_orig"] = $discount_orig;
		}
	}

	#
	# Apply discount coupon
	#
	if ($active_modules["Discount_Coupons"] && !empty($discount_coupon)) {
		#
		# Calculate discount value of the discount coupon
		#
		$coupon_total = 0;
		$coupon_amount = 0;

		if (!empty($active_modules['Multiple_Storefronts'])) {
			$sf_condition = "AND storefrontid=$current_storefront";
		} else {
			$sf_condition = '';
		}

		if (!empty($global_store['discount_coupons'])) {
			$discount_coupon_data = array();
			foreach ($global_store['discount_coupons'] as $v) {
				if ($v['__override'] || ($v['coupon'] == $discount_coupon && ($single_mode || $v['provider'] == $provider))) {
					$discount_coupon_data = $v;
					break;
				}
			}
		}
		else {
			$discount_coupon_data = func_query_first("select * from $sql_tbl[discount_coupons] where coupon='$discount_coupon' $provider_condition $sf_condition");
		}

		$return["discount_coupon_data"] = $discount_coupon_data;

		if (!$single_mode && ($discount_coupon_data["provider"] != $provider || empty($products)))
			$return["discount_coupon"] = $discount_coupon_data = "";

		$return["coupon_type"] = $discount_coupon_data["coupon_type"];

		if (!empty($discount_coupon_data) && (($discount_coupon_data["coupon_type"] == "absolute") || ($discount_coupon_data["coupon_type"] == "percent"))) {
			$coupon_discount = 0;
			if ($discount_coupon_data["productid"] > 0) {
				#
				# Apply coupon to product
				#
				foreach($products as $k=>$product) {
					if (@$product["deleted"]) continue; # for Advanced_Order_Management module

					if (!empty($active_modules["Special_Offers"]) && !empty($product['free_amount'])) {
						# it's a "free product"
						# necessary only for absolute discount
						continue;
					}

					if ($product["productid"] != $discount_coupon_data["productid"])
						continue;

					$price = $product["discounted_price"];

					if ($discount_coupon_data["coupon_type"] == "absolute" && $discount_coupon_data["discount"] > $price) {
						$discount_coupon_data["discount"] = 100;
						$discount_coupon_data["coupon_type"] = 'percent';
					}

					if ($discount_coupon_data["coupon_type"]=="absolute" && $discount_coupon_data["apply_product_once"] == "N")
						$multiplier = $product["amount"];
					else
						$multiplier = 1;

					$_coupon_discount = $_taxed_coupon_discount = $discount_coupon_data["discount"] * $multiplier;

					if ($config["Taxes"]["apply_discount_on_taxed_amount"] == "Y" && !empty($product["taxes"]) && is_array($product["taxes"])) {

						$_taxes = func_tax_price($_coupon_discount, 0, false, NULL, "", $product["taxes"], ($discount_coupon_data["coupon_type"] == "percent"));
						$_taxed_coupon_discount = $_taxes["taxed_price"];
						$_coupon_discount = $_taxes["net_price"];
					}

					if ($discount_coupon_data["coupon_type"]=="absolute") {
						$taxed_coupon_discount = $_taxed_coupon_discount;
						$taxed_coupon_discount = $coupon_discount = $_coupon_discount;
					}
					else {
						$taxed_coupon_discount = price_format($price * $_taxed_coupon_discount / 100 );
						$coupon_discount = price_format($price * $_coupon_discount / 100 );
					}

					$products[$k]["coupon_discount"] = $taxed_coupon_discount;
					$products[$k]["discounted_price"] = max($price - $coupon_discount, 0.00);
	
					$return["coupon_discount"] += $taxed_coupon_discount;
				}
			}
			elseif ($discount_coupon_data["categoryid"] > 0) {
				#
				# Apply coupon to category (and subcategories)
				#
				$category_ids[] = $discount_coupon_data["categoryid"];

				if ($discount_coupon_data["recursive"] == "Y") {
					$categoryid_path = func_query_first_cell("SELECT categoryid_path FROM $sql_tbl[categories] WHERE categoryid='$discount_coupon_data[categoryid]'");
					if (!empty($categoryid_path))
						$tmp = db_query("SELECT categoryid FROM $sql_tbl[categories] WHERE categoryid_path LIKE '$categoryid_path/%'");
					while($row = db_fetch_array($tmp))
						$category_ids[] = $row["categoryid"];
				}

				if ($discount_coupon_data['coupon_type'] == 'absolute') {
					#
					# Check if absolute discount does not exceeds total
					#
					foreach ($products as $k=>$product) {

						if (@$product["deleted"]) continue; # for Advanced_Order_Management module

						if (!empty($active_modules["Special_Offers"]) && !empty($product['free_amount'])) {
							# it's a "free product"
							# necessary only for absolute discount
							continue;
						}

						$product_categories = func_query("SELECT categoryid FROM $sql_tbl[products_categories] WHERE productid='$product[productid]'");
						$is_valid_product = false;
						foreach ($product_categories as $pc) {
							if (in_array($pc["categoryid"], $category_ids)) {
								$is_valid_product = true;
								break;
							}
						}

						if ($is_valid_product) {
							if ($discount_coupon_data["coupon_type"]=="absolute" && $discount_coupon_data["apply_product_once"] == "N")
								$multiplier = $product["amount"];
							else
								$multiplier = 1;

							$sum_discount += $discount_coupon_data['discount'] * $multiplier;
						}
				
					}

					if ($sum_discount > $total) {
						# Transform coupon discount to 100%
						$discount_coupon_data['discount'] = 100;
						$discount_coupon_data['coupon_type'] = 'percent';
					}
				}

				#
				# Apply coupon to one category
				#
				foreach ($products as $k=>$product) {
					if (@$product["deleted"]) continue; # for Advanced_Order_Management module

					if (!empty($active_modules["Special_Offers"]) && !empty($product['free_amount'])) {
						# it's a "free product"
						# necessary only for absolute discount
						continue;
					}

					$product_categories = func_query("SELECT categoryid FROM $sql_tbl[products_categories] WHERE productid='$product[productid]'");
					$is_valid_product = false;
					foreach ($product_categories as $pc) {
						if (in_array($pc["categoryid"], $category_ids)) {
							$is_valid_product = true;
							break;
						}
					}

					if ($is_valid_product) {

						if ($discount_coupon_data["coupon_type"]=="absolute" && $discount_coupon_data["apply_product_once"] == "N")
							$multiplier = $product["amount"];
						else
							$multiplier = 1;

						$_coupon_discount = $_taxed_coupon_discount = $discount_coupon_data["discount"] * $multiplier;

						if ($config["Taxes"]["apply_discount_on_taxed_amount"] == "Y" && !empty($product["taxes"]) && is_array($product["taxes"])) {

							$_taxes = func_tax_price($_coupon_discount, 0, false, NULL, "", $product["taxes"], ($discount_coupon_data["coupon_type"] == "percent"));
							$_taxed_coupon_discount = $_taxes["taxed_price"];
							$_coupon_discount = $_taxes["net_price"];

						}

						$price = $product["discounted_price"];

						if ($discount_coupon_data["coupon_type"]=="absolute") {
							$taxed_coupon_discount = $_taxed_coupon_discount;
							$coupon_discount = $_coupon_discount;
						}
						else {
							$taxed_coupon_discount = price_format($price * $_taxed_coupon_discount / 100 );
							$coupon_discount = price_format($price * $_coupon_discount / 100 );
						}

						$taxed_coupon_discount = price_format($taxed_coupon_discount);

						$products[$k]["coupon_discount"] = $taxed_coupon_discount;
						$products[$k]["discounted_price"] = max($price - $coupon_discount, 0.00);
	
						$return["coupon_discount"] += $taxed_coupon_discount;

						if ($discount_coupon_data["coupon_type"] == "absolute" && $discount_coupon_data["apply_category_once"] == "Y")
							break;
					}
				}
			}
			else {
				#
				# Apply coupon to subtotal
				#
				if ($discount_coupon_data["coupon_type"]=="absolute" && $discount_coupon_data['discount'] > $total) {
					$discount_coupon_data['discount'] = 100;
					$discount_coupon_data['coupon_type'] = 'percent';
				}

				if ($discount_coupon_data["coupon_type"]=="absolute") {
					$return["coupon_discount"] = $discount_coupon_data["discount"];
				}
				elseif ($discount_coupon_data["coupon_type"]=="percent")
					$return["coupon_discount"] = $total * $discount_coupon_data["discount"] / 100;
				$updated = func_distribute_discount("coupon_discount", $products, $discount_coupon_data["discount"], $discount_coupon_data["coupon_type"], $total, $_taxes);

				#
				# $products and $discount are extracted from the array $updated
				#
				extract($updated);
				unset($updated);

				$return["coupon_discount"] = $coupon_discount;

			}
		}

		if (isset($coupon_discount_orig))
			$return["coupon_discount_orig"] = $coupon_discount_orig;
		else
			$return["coupon_discount_orig"] = $return["coupon_discount"];

		$return["products"] = $products;
	}

	return $return;
}

#
# This function calculates delivery cost
#
# Shipping also calculated based on zones
#
# Advanced shipping formula:
# AMOUNT = amount of ordered products
# SUM = total sum of order
# TOTAL_WEIGHT = total weight of products
#
# SHIPPING = rate+TOTAL_WEIGHT*weight_rate+AMOUNT*item_rate+SUM*rate_p/100
#
function func_calculate_shippings($products, $shipping_id, $customer_info, $provider="", $approximation_intershipper_rates="") {
	global $sql_tbl, $config, $active_modules, $single_mode;

	$return = array("shipping_cost" => 0);
	#
	# Prepare provider condition for shipping rates gathering
	#
	$provider_condition = ($single_mode ? "" : "AND provider='$provider'");

	#
	# Initial definitions
	#
	$total_shipping = 0;
	$total_weight_shipping = 0;
	$total_ship_items = 0;
	$shipping_cost = 0;
	$shipping_freight = 0;

	if (!empty($products)) {
		foreach($products as $k=>$product) {
			if (@$product["deleted"]) continue; # for Advanced_Order_Management module

# START: random:20460 [2010 Mar 18 13:43] 
			if ($active_modules["Egoods"] && $product["distribution"] != "") {
# END: random:20460 [2010 Mar 18 13:43] 
				continue;
			}
			else {
# START: random:20460 [2010 Mar 18 13:43] 

				$free_shipping_arr = func_is_customer_free_ship_zone($product["free_ship_zone"], $customer_info, $product["provider"]);
				$free_shipping = $free_shipping_arr["is_customer_free_ship_zone"];

//if ($product["productid"] == "39768"){
//func_print_r($product["productid"], $free_shipping_arr);
//}


# END: random:20460 [2010 Mar 18 13:43] 
				if (!($config["Shipping"]["replace_shipping_with_freight"] == "Y" && $product["shipping_freight"] > 0)) {
# START: random:20460 [2010 Mar 18 13:43] 
					if (!$free_shipping) {
# END: random:20460 [2010 Mar 18 13:43] 
					$total_shipping += $product["subtotal"];
					$total_weight_shipping += $product["weight"] * $product["amount"];
# START: random:20460 [2010 Mar 18 13:43] 
					}
# END: random:20460 [2010 Mar 18 13:43] 
					if ($product["product_type"] != 'C')
						$total_ship_items += $product["amount"];
				}

# START: random:20460 [2010 Mar 18 13:43] 
				if (!$free_shipping) {
# END: random:20460 [2010 Mar 18 13:43] 
					$shipping_freight += $product["shipping_freight"] * $product["amount"];
				}
# START: random:20460 [2010 Mar 18 13:43] 
			}
# END: random:20460 [2010 Mar 18 13:43] 
# START: random:17710_17631 [2009 Mar 26 09:25] 
			if (!isset($for_manufacturerid)){
# END: random:17710_17631 [2009 Mar 26 09:25] 
# START: random:20341 [2010 Jul 29 14:46] 
				$for_manufacturerid = func_manufacturerid_for_group($product['shipping_freight'], $product['manufacturerid']);


			}
# END: random:20341 [2010 Jul 29 14:46] 
		}
	}

	#
	# Nothing to ship
	#
	if ($total_ship_items == 0 && $shipping_freight == 0)
		return $return;

	$customer_zone = func_get_customer_zone_ship($customer_info, $provider,"D", $for_manufacturerid, $shipping_id);
	if (!is_null($customer_zone))
		$shipping = func_query("SELECT * FROM $sql_tbl[shipping_rates] WHERE shippingid='$shipping_id' $provider_condition AND zoneid='$customer_zone' AND mintotal<='$total_shipping' AND maxtotal>='$total_shipping' AND minweight<='$total_weight_shipping' AND maxweight>='$total_weight_shipping' AND type='D' AND manufacturerid='$for_manufacturerid' ORDER BY maxtotal, maxweight");

	if ($shipping && $total_ship_items > 0) {
		$shipping_cost =
			$shipping[0]["rate"] +
			($total_weight_shipping * $shipping[0]["weight_rate"]) +
			($total_ship_items * $shipping[0]["item_rate"]) +
			($total_shipping * $shipping[0]["rate_p"] / 100);
	}

	#
	# Get realtime shipping rates
	#
	$result = func_query_first ("SELECT * FROM $sql_tbl[shipping] WHERE shippingid='$shipping_id' AND code!=''");
	if ($config["Shipping"]["realtime_shipping"]=="Y" && $result && $total_ship_items>0) {
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
		$tmp = func_real_shipping($shipping_id, $for_manufacturerid, $approximation_intershipper_rates);

#
##
###		
		if ($shipping_id == "1"){  // approximation used in shipping/shipping.php
			$tmp["surcharge"] = 0;
		}
###
##
#

		$shipping_cost = $tmp['rate'];
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
		$customer_zone = func_get_customer_zone_ship($customer_info, $provider,"R", $for_manufacturerid, $shipping_id);
# START: random:1073746882_1073747063 [2008 Dec 24 16:25]
		if (!is_null($customer_zone))
			$shipping_rt = func_query("SELECT * FROM $sql_tbl[shipping_rates] WHERE shippingid='$shipping_id' $provider_condition AND zoneid='$customer_zone' AND manufacturerid='$for_manufacturerid' AND mintotal<='$total_shipping' AND maxtotal>='$total_shipping' AND minweight<='$total_weight_shipping' AND maxweight>='$total_weight_shipping' AND type='R' ORDER BY maxtotal, maxweight");
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 

		if ($shipping_rt && $shipping_cost > 0){
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 

			if ($shipping_rt[0]['cost_marcup'] > 0){
				$shipping_cost *= $shipping_rt[0]['cost_marcup'];
			}

			$shipping_cost += $shipping_rt[0]['rate'] 
        	        + $total_weight_shipping * $shipping_rt[0]['weight_rate'] 
	                + $total_ship_items * $shipping_rt[0]['item_rate'] 
                	+ $total_shipping * $shipping_rt[0]['rate_p'] / 100 
//      	          + $shipping_cost * $shipping_rt[0]['cost_marcup'] / 100 
	                + $tmp['surcharge'];
		}
	
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
	}

	$shipping_cost += $shipping_freight;

#
##
###
	$shipping = func_query_first_cell("SELECT shipping FROM $sql_tbl[shipping] WHERE shippingid='$shipping_id'");
	if ($shipping == "_USE_MY_UPS_FEDEX_ACCOUNT_"){
		$shipping_cost = "5.00";
	} elseif ($shipping == "_USE_MY_TRUCKING_ACCOUNT_"){
		$shipping_cost = "5.00";
	} elseif ($shipping == "_SHIP_BY_FASTEST_METHOD_"){
		$shipping_cost = "0.00";
	}
###
##
#

	$return["shipping_cost"] = $shipping_cost;
	return $return;
}

#
# This function calculates taxes
#
# SUM = total sum of order
#
# TAX_US = country_tax_flat + SUM*country_tax_percent/100 + state_tax_flat + SUM*state_tax_percent/100;
#
# TAX_CAN = SUM*gst_tax/100 + SUM*pst_tax/100;
#
function func_calculate_taxes(&$products, $customer_info, $shipping_cost, $provider="") {
	global $sql_tbl, $config, $active_modules, $single_mode, $shop_language;
	global $xcart_dir;

	$taxes = array();
	$taxes["total"] = 0;
	$taxes["shipping"] = 0;
	$_tmp_taxes = array();

	foreach ($products as $k=>$product) {

		$__taxes = array();
	
		if (@$product["deleted"]) continue; # for Advanced_Order_Management module

		if ($product["free_tax"] != "Y") {
			$product_taxes = func_get_product_taxes($products[$k], $customer_info["login"], true);

			if ($config["Taxes"]["display_taxed_order_totals"] =="Y")
				$products[$k]["display_price"] = doubleval($product["taxed_price"]);

			if (is_array($product_taxes)) {
				$formula_data = array();
				$formula_data["ST"] = $product["price"] * $product["amount"];
				$formula_data["DST"] = $product["discounted_price"];
				$formula_data["SH"] = 0;

				$tax_result = array();

				if (empty($shipping_cost)) {
					$index = 1;
					$tax_result[1] = 0;
				}
				else
					$index = 0;

				while ($index < 2) {
					$index++;

					foreach ($product_taxes as $tax_name=>$v) {
						if ($v["skip"])
							continue;

						if (!isset($taxes["taxes"][$tax_name])) {
							$taxes["taxes"][$tax_name] = $v;
							$taxes["taxes"][$tax_name]["tax_cost"] = 0;
						}

						if ($index == 2) {
							$formula_data["SH"] = $shipping_cost;

							if (!empty($__taxes[$tax_name]))
								$formula_data["SH"] = 0;
							else
								$__taxes[$tax_name] = true;
						}

						if ($v["rate_type"] == "%") {
							$assessment = func_calculate_assessment($v["formula"], $formula_data);
							$tax_value = $assessment * $v["rate_value"] / 100;
						}
						else
							$tax_value = $v["rate_value"] * $product["amount"];

						$formula_data[$tax_name] = $tax_value;

						$tax_result[$index] += $tax_value;

						if (empty($formula_data["SH"])) {
							$_tmp_taxes[$tax_name]["tax_cost_no_shipping"] = $tax_value;
							$taxes["taxes"][$tax_name]["tax_cost_no_shipping"] += $tax_value;
						}

						if ($index == 2) {
							$taxes["taxes"][$tax_name]["tax_cost_shipping"] = $tax_value-$_tmp_taxes[$tax_name]["tax_cost_no_shipping"];
						}

					}
				}
			}
		}
	}

	if (is_array($taxes["taxes"])) {
		foreach ($taxes["taxes"] as $tax_name=>$tax) {
			$taxes["taxes"][$tax_name]["tax_cost"] = price_format($tax["tax_cost_no_shipping"] + $tax["tax_cost_shipping"]);
			$taxes["total"] += $taxes["taxes"][$tax_name]["tax_cost"];
			$taxes["shipping"] += $tax["tax_cost_shipping"];
		}
	}

	if ($shipping_cost == 0)
		$taxes["shipping"] = 0;
	
	return $taxes;
}

#
# Calculate total products price
# 1) calculate total sum,
# 2) a) total = total - discount
#    b) total = total - coupon_discount
# 3) calculate shipping
# 4) calculate tax
# 5) total_cost = total + shipping + tax
# 6) total_cost = total_cost + giftcerts_cost
#
function func_calculate_single($cart, $products, $login, $login_type, $provider_for="") {
	global $single_mode, $current_storefront;
# START: random:17710_17631 [2009 Mar 26 09:25] 
	global $active_modules, $config, $sql_tbl;
# END: random:17710_17631 [2009 Mar 26 09:25] 
	global $xcart_dir;

	if ($config["Taxes"]["display_taxed_order_totals"] == "Y")
		$config["Taxes"]["apply_discount_on_taxed_amount"] = "Y";

	if ($products) {
		#
		# Set the fields filter to avoid storing too much redundant data
		# in the session
		#
		list($tmp_k, $tmp_v) = each($cart["products"]);

		foreach(array_keys($tmp_v) as $k)
			$product_keys[] = $k;

		unset($tmp_k, $tmp_v);
		reset($cart["products"]);

		$product_keys[] = "cartid";
		$product_keys[] = "product";
		$product_keys[] = "productcode";
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
		$product_keys[] = "manufacturerid";
		$product_keys[] = "shipping_freight";
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
		$product_keys[] = "product_options";
		$product_keys[] = "price";
		$product_keys[] = "display_price";
		$product_keys[] = "display_discounted_price";
		$product_keys[] = "display_subtotal";
		$product_keys[] = "free_price";
		$product_keys[] = "discount";
		$product_keys[] = "coupon_discount";
		$product_keys[] = "discounted_price";
		$product_keys[] = "taxes";
		$product_keys[] = "subtotal";
		$product_keys[] = "product_type";
		$product_keys[] = "options_surcharge";
		$product_keys[] = "extra_data"; # Additional data for storing in the DB
		$product_keys[] = "provider";
		$product_keys[] = "discount_avail";
		$product_keys[] = "weight";
		$product_keys[] = "itemid";
		$product_keys[] = "oProduct";
		$product_keys[] = "oOrderDetail";
# START: random:20460 [2010 Mar 18 13:43]
		$product_keys[] = "free_ship_zone";
# END: random:20460 [2010 Mar 18 13:43] 
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
		$product_keys[] = "taxed_shipping_freight";
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
# START: random:20341 [2010 Jul 29 14:46] 
		$product_keys[] = "deleted";
# END: random:20341 [2010 Jul 29 14:46] 

		if ($active_modules["Google_Checkout"])
			$product_keys[] = "valid_for_gcheckout";

		if ($active_modules["Wishlist"])
			$product_keys[] = "wishlistid";

		if ($active_modules["Egoods"])
			$product_keys[] = "distribution";

		if ($active_modules["Advanced_Order_Management"]) {
			$product_keys[] = "new";
			$product_keys[] = "use_shipping_cost_alt";
			$product_keys[] = "shipping_cost_alt";
		}

		if ($active_modules["Product_Configurator"]) {
			$product_keys[] = "hidden";
			$product_keys[] = "pconf_price";
			$product_keys[] = "pconf_display_price";
			$product_keys[] = "pconf_data";
			$product_keys[] = "slotid";
			$product_keys[] = "price_modifier";
		}

		if ($active_modules["Subscriptions"]) {
			$product_keys[] = "catalogprice";
			$product_keys[] = "sub_plan";
			$product_keys[] = "sub_days_remain";
			$product_keys[] = "sub_onedayprice";
		}

		if ($active_modules["Special_Offers"]) {
			$product_keys[] = "free_amount";
			$product_keys[] = "have_offers";
			$product_keys[] = "special_price_used";
			$product_keys[] = "free_shipping_used";
			$product_keys[] = "saved_original_price";
		}
	}
	else
		$products = array();

	#
	# Calculate totals for one provider only or for all ($single_mode=true)
	#
	$provider_condition = ($single_mode ? "" : "and provider='$provider_for'");

	$giftcerts = @$cart["giftcerts"];
	$discount_coupon = @$cart["discount_coupon"];

	#
	# Get the user information
	#
	if (!empty($login)) $customer_info = func_userinfo($login,$login_type);

	if (defined('XAOM'))
		$customer_info = func_array_merge($customer_info, $cart["userinfo"]);
	
	if (!empty($active_modules["Special_Offers"])) {
		include $xcart_dir."/modules/Special_Offers/calculate_prepare.php";
		include $xcart_dir."/modules/Special_Offers/calculate.php";
	}

	if (!empty($products)) {
		#
		# Apply discounts to the products
		#
		$discounts_ret = func_calculate_discounts($customer_info["membershipid"], $products, $discount_coupon, $provider_for);

		#
		# Extract returned variables to global variables set:
		# $discount, $coupon_discount, $discount_coupon, $products
		#
		extract($discounts_ret);
		unset($discounts_ret);
	}

	#
	# Initial definitions
	#
	$subtotal = 0;
	$discounted_subtotal = 0;
	$shipping_cost = 0;
	$total_tax = 0;
	$giftcerts_cost = 0;

	#
	# Update $products array: calculate discounted prices, subtotal and
	# discounted subtotal
	#
	
	foreach($products as $k=>$product) {
		if (@$product["deleted"]) continue; # for Advanced_Order_Management module

		if (empty($product["discount"]) && empty($product["coupon_discount"]))
			$product["discounted_price"] = $product["price"] * $product["amount"];

		if ($product["product_type"] == "C") {
			# Corrections for Product Configurator module
			$product["pconf_price"] = $product["price"] = max(doubleval($product["options_surcharge"]), 0);
			$product["discounted_price"] = $product["price"] * $product["amount"];
			foreach ($products as $k1=>$v1) {
				if ($v1["hidden"] == $product["cartid"]) {
					$product["pconf_price"] += price_format($v1["price"]);
				}
			}

			$product["pconf_display_price"] = $product["pconf_price"];
		}

		$product["subtotal"] = price_format($product["discounted_price"]);
		$product["display_price"] = price_format($product["price"]);
		$product["display_discounted_price"] = $product["discounted_price"];
		$product["display_subtotal"] = $product["subtotal"];

		$products[$k] = $product;

		if (!empty($active_modules["Special_Offers"])) {
			include $xcart_dir."/modules/Special_Offers/calculate_subtotal.php";
		}
		else {
			if ($config["Taxes"]["display_taxed_order_totals"] != 'Y') {
				$subtotal += price_format($product["price"]) * $product["amount"];
				$discounted_subtotal = $subtotal - $discount - $coupon_discount;
			}
			else {
				$subtotal += $product["price"] * $product["amount"];
				$discounted_subtotal += $product["subtotal"];
			}
		}
	}

	$total = $subtotal;
	$display_subtotal = $subtotal;
	$display_discounted_subtotal = $discounted_subtotal;

	#
	# Enable shipping and taxes calculation if "apply_default_country" is ticked.
	#
	$calculate_enable_flag = true;

	if (empty($login)) {
		#
		# If user is not logged in
		#
		if ($config["General"]["apply_default_country"] == "Y") {
			$customer_info["s_country"] = $config["General"]["default_country"];
			$customer_info["s_state"] = $config["General"]["default_state"];
			$customer_info["s_zipcode"] = $config["General"]["default_zipcode"];
			$customer_info["s_city"] = $config["General"]["default_city"];
		}
		else {
			$calculate_enable_flag = false;
		}
	}

# START: random:17710_17631 [2009 Mar 26 09:25] 
	if (defined('IS_SHIPPING_QUOTE')) {
		global $shipquote_userinfo;
		if (empty($customer_info))
			$customer_info = array();
		$customer_info = array_merge($customer_info, $shipquote_userinfo);
		$calculate_enable_flag = true;
	}

# END: random:17710_17631 [2009 Mar 26 09:25] 
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
	$whole_taxes = array();
# END: random:1073746882_1073747063 [2008 Dec 24 16:25]
	if (empty($config['Shipping']['new_shipping_calculation']) || (!empty($config['Shipping']['new_shipping_calculation'])) && $config['Shipping']['new_shipping_calculation'] !='Y') {
		if ($config["Shipping"]["disable_shipping"] != "Y" && $calculate_enable_flag || $cart["use_shipping_cost_alt"] == "Y") {
			#
			# Calculate shipping cost
			#
# START: random:20341 [2010 Jul 29 14:46] 
			if ($cart["use_shipping_costs_alt"] == "Y") {
				$shipping_cost = $display_shipping_cost = $cart["shipping_cost_alt"];
				$shipping_costs = $display_shipping_costs = $cart["shipping_costs_alt"];
			} elseif ($cart["use_shipping_cost_alt"] == "Y") {
# END: random:20341 [2010 Jul 29 14:46] 
# END: random:17710_17631 [2009 Mar 26 09:25] 
				$shipping_cost = $cart["shipping_cost_alt"];
# START: random:17710_17631 [2009 Mar 26 09:25] 
				$display_shipping_costs = array($shipping_cost);
# END: random:17710_17631 [2009 Mar 26 09:25] 
			} else {
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
				$display_shipping_costs = array();
# START: random:17710_17631 [2009 Mar 26 09:25] 
# START: random:20341 [2010 Jul 29 14:46] 
				$shipping_taxes = array();
# END: random:20341 [2010 Jul 29 14:46] 
				$shippingids = @$cart["shippingids"];
				if (!empty($shippingids) && is_array($shippingids)) {
					foreach ($shippingids as $ks => $shipping_id) {
						if (!empty($shipping_id)) {
# END: random:17710_17631 [2009 Mar 26 09:25] 
							$_products_ = array();
# START: random:17710_17631 [2009 Mar 26 09:25] 
							foreach ($products as $kp => $vp) {
# START: random:20341 [2010 Jul 29 14:46] 
								if ($ks == func_manufacturerid_for_group($vp['shipping_freight'], $vp['manufacturerid']))
# END: random:20341 [2010 Jul 29 14:46] 
									$_products_[] = $vp;
# END: random:17710_17631 [2009 Mar 26 09:25] 
							}

							$shippings_ret = func_calculate_shippings($_products_, $shipping_id, $customer_info, $provider_for);
#
# Extract returned variables to global variables set:
# $shipping_cost
#
							extract($shippings_ret);
							unset($shippings_ret);
							$shipping_costs[$ks] = $shipping_cost;
							$manuf_taxes = func_calculate_taxes($_products_, $customer_info, $shipping_cost, $provider_for);
# START: random:20341 [2010 Jul 29 14:46] 
							$shipping_taxes[$ks] = array();
							if ($manuf_taxes["taxes"]) {
								foreach ($manuf_taxes["taxes"] as $__tk => $__tv) {
									if ($__tk == 'GST' || $__tk == 'HST') {
										$shipping_taxes[$ks]['gst'] = $__tv['tax_cost_shipping'];
									} elseif ($__tk == 'PST') {
										$shipping_taxes[$ks]['pst'] = $__tv['tax_cost_shipping'];
									}
								}
							}
# END: random:20341 [2010 Jul 29 14:46] 
							$display_shipping_costs[$ks] = $shipping_cost + $manuf_taxes["shipping"];
							if (empty($whole_taxes)) {
								$whole_taxes = $manuf_taxes;
							} else {
								$whole_taxes["total"] += $manuf_taxes["total"];
								$whole_taxes["shipping"] += $manuf_taxes["shipping"];
								if ($manuf_taxes["taxes"]) {
									foreach ($manuf_taxes["taxes"] as $__tk => $__tv) {
# START: random:20341 [2010 Jul 29 14:46] 
										if (!empty($whole_taxes["taxes"]) && array_key_exists($__tk, $whole_taxes["taxes"])) {
# END: random:20341 [2010 Jul 29 14:46] 
											$whole_taxes["taxes"][$__tk]["tax_value_precise"] += $__tv["tax_value_precise"];
											$whole_taxes["taxes"][$__tk]["tax_value"] += $__tv["tax_value"];
											$whole_taxes["taxes"][$__tk]["taxed_price"] += $__tv["taxed_price"];
											$whole_taxes["taxes"][$__tk]["tax_cost"] += $__tv["tax_cost"];
											$whole_taxes["taxes"][$__tk]["tax_cost_no_shipping"] += $__tv["tax_cost_no_shipping"];
											$whole_taxes["taxes"][$__tk]["tax_cost_shipping"] += $__tv["tax_cost_shipping"];
										} else {
											$whole_taxes["taxes"][$__tk] = $__tv;
										}
									}
								}
							}
						}
					}
					$shipping_cost = 0;
					$display_shipping_cost = 0;
					if (is_array($shipping_costs)) {
						foreach ($shipping_costs as $ksc => $vsc) {
							$shipping_cost += $shipping_costs[$ksc];
							$display_shipping_cost += $display_shipping_costs[$ksc];
						}
					}

				}
# START: random:17710_17631 [2009 Mar 26 09:25] 
				if (empty($shipping_cost) && !empty($cart['shippingid'])) {
					$shipping_id = $cart['shippingid'];
# END: random:17710_17631 [2009 Mar 26 09:25] 
					$shippings_ret = func_calculate_shippings($products, $shipping_id, $customer_info, $provider_for);
					extract($shippings_ret);
					unset($shippings_ret);
				}
			}
			if (!empty($coupon_type) && $coupon_type == "free_ship") {
				#
				# Apply discount coupon 'Free shipping'
				#
				if (($single_mode) || ($provider_for == $discount_coupon_data["provider"])) {
					$coupon_discount = $shipping_cost;
					$shipping_cost = 0;
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
					$display_shipping_cost = $shipping_cost;
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
				}
			}
		}
	} else {
		if ($cart["use_shipping_costs_alt"] == "Y") {
			$shipping_cost = $display_shipping_cost = $cart["shipping_cost_alt"];
			$shipping_costs = $display_shipping_costs = $cart["shipping_costs_alt"];
		} else {
			$shipping_cost = $cart['shipping_cost'];
			$display_shipping_cost = $cart['display_shipping_cost'];
			$shipping_costs = $cart['shipping_costs'];
			$display_shipping_costs = $cart['display_shipping_costs'];
		}
	}


	if ($calculate_enable_flag && !($customer_info["tax_exempt"] == "Y" && ($config["Taxes"]["enable_user_tax_exemption"] == "Y" || defined('XAOM')))) {
		#
		# Calculate taxes cost
		#
		$taxes = func_calculate_taxes($products, $customer_info, $shipping_cost, $provider_for);
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
		$sum_freight = 0;
		if(!empty($products))
		foreach($products as $k=>$p){
			$prod_taxes = array();
			$sum_freight += $p['shipping_freight'] * $p['amount'];
			$prod_taxes = func_calculate_taxes($products, $customer_info, $p['shipping_freight'] * $p['amount'], $provider_for);
			$products[$k]['taxed_shipping_freight'] = $p['shipping_freight'] + $prod_taxes['shipping'];

		}

		if (empty($whole_taxes))
			$whole_taxes = func_calculate_taxes($products, $customer_info, $sum_freight + $shipping_cost, $provider_for);

		$total_tax = $taxes["total"];

		if ($config["Taxes"]["display_taxed_order_totals"] == "Y") {

			$_display_discounted_subtotal_tax = 0;
			if (is_array($taxes["taxes"])) {
				# Calculate the additional tax value if "display_including_tax"
				# option for tax is disabled (for $_display_discounted_subtotal)
				foreach ($taxes["taxes"] as $k=>$v)
					if ($v["display_including_tax"] != "Y")
						$_display_discounted_subtotal_tax += $v["tax_cost"];
			}
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
			if (empty($display_shipping_costs))
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
			$display_shipping_cost = $shipping_cost + $taxes["shipping"];
			$_display_subtotal = 0;
			$_display_discounted_subtotal = 0;
			if (is_array($products)) {
				foreach ($products as $k=>$v) {
					if (@$v["deleted"]) continue; # for Advanced_Order_Management module

					$v["display_price"] = $products[$k]["display_price"] = price_format($products[$k]["display_price"]);
					if (is_array($v["taxes"])) {
						# Correct $_display_subtotal if "display_including_tax"
						# option for the tax is disabled
						foreach ($v["taxes"] as $tn=>$tv) {
							if ($tv["display_including_tax"] == "N")
								$_display_subtotal += $tv["tax_value"];
						}
					}

					if (!empty($v["discount"]) || !empty($v["coupon_discount"])) {
						$subscription_flag = ( !empty($active_modules["Subscriptions"]) && $v["sub_plan"] ? false : true );
						$_taxes = func_tax_price($v["price"], $v["productid"], false, $v["discounted_price"], $customer_info, "", $subscription_flag);
						if ($v['discounted_price'] > 0)
							$products[$k]["display_discounted_price"] = price_format($_taxes["taxed_price"]);
					}
					else {
						$products[$k]["display_discounted_price"] = $v["display_price"] * $v["amount"];
					}

					$products[$k]["display_subtotal"] = $products[$k]["display_discounted_price"];
					$_display_discounted_subtotal += $products[$k]["display_subtotal"];
					if ($v["product_type"] == "C") {
						# Corrections for Product Configurator module
						$products[$k]["display_price"] = $_pconf_display_price = max(doubleval($products[$k]["options_surcharge"]), 0);
						$_display_subtotal += ($_pconf_display_price * $products[$k]["amount"]);
						$_pconf_taxes = array();
						foreach ($products as $k1=>$v1) {
							if (@$v1["deleted"]) continue; # for Advanced_Order_Management module

							if ($v1["hidden"] == $v["cartid"]) {
								$_pconf_display_price += price_format($v1["display_price"]);
								if (is_array($v1["taxes"])) {
									foreach ($v1["taxes"] as $_tax_name=>$_tax) {
										if (!isset($_pconf_taxes[$_tax_name])) {
											$_pconf_taxes[$_tax_name] = $_tax;
											$_pconf_taxes[$_tax_name]["tax_value"] = 0;
										}

										$_pconf_taxes[$_tax_name]["tax_value"] += $_tax["tax_value"];
									}
								}
							}
						}

						$products[$k]["taxes"] = $_pconf_taxes;
						$products[$k]["pconf_display_price"] = $_pconf_display_price;
					}
					else
						$_display_subtotal += $v["display_price"] * $v["amount"];
					
					if (!empty($active_modules["Subscriptions"]) && $products[$k]["sub_plan"] && $config["Taxes"]["display_taxed_order_totals"] == "Y") {
						$subscription_markup = $products[$k]["sub_days_remain"] * $products[$k]["sub_onedayprice"];
						$_display_subtotal += $subscription_markup;
						$products[$k]["display_price"] += $subscription_markup;
						if ($display_subtotal == $display_discounted_subtotal)
							$products[$k]["display_subtotal"] += $subscription_markup;
					}

				}

				if (empty($coupon_discount) && empty($discount))
					$display_discounted_subtotal = $_display_subtotal;
				else
					$display_discounted_subtotal = $_display_discounted_subtotal;

				$display_subtotal = $_display_subtotal;
			}
		}
	}

	#
	# Calculate Gift Certificates cost (purchased giftcerts)
	#
	if ((($single_mode) || (!$provider_for)) && ($giftcerts)) {
		foreach($giftcerts as $giftcert) {
			if (@$giftcert["deleted"]) continue; # for Advanced_Order_Management module

			$giftcerts_cost+=$giftcert["amount"];
		}
	}

	$subtotal += $giftcerts_cost;
	$display_subtotal += $giftcerts_cost;
	$discounted_subtotal += $giftcerts_cost;
	$display_discounted_subtotal += $giftcerts_cost;

	if ($discount > $display_subtotal)
		$discount = $display_subtotal - $display_discounted_subtotal;

	if ($coupon_discount > $display_subtotal)
		$coupon_discount = $display_subtotal - $display_discounted_subtotal;

	$display_shipping_cost = price_format($display_shipping_cost);
	$display_discounted_subtotal = price_format($display_discounted_subtotal);

	#
	# Calculate total
	#
	if ($config["Taxes"]["display_taxed_order_totals"] == "Y") {
		if ($config["Taxes"]["apply_discount_on_taxed_amount"] == "Y" && ($display_discounted_subtotal != $display_subtotal - $coupon_discount_orig - $discount_orig)) {
			$display_discounted_subtotal = $display_subtotal - $coupon_discount_orig - $discount_orig;
			$coupon_discount = $coupon_discount_orig;
			$discount = $discount_orig;
		}
		else {
			if ($discount > 0)
				$discount = $display_subtotal - ($display_discounted_subtotal + $coupon_discount);
			else
				$coupon_discount = $display_subtotal - ($display_discounted_subtotal + $discount);
		}
		$total = $display_discounted_subtotal + $display_shipping_cost;
	}
	else
# START: random:17710_17631 [2009 Mar 26 09:25] 
		$total = $discounted_subtotal + $shipping_cost + $total_tax;
# END: random:17710_17631 [2009 Mar 26 09:25] 

# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
	$freight_cost = 0;
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
	$_products = array();
# START: random:17710_17631 [2009 Mar 26 09:25] 
	$shipping_groups = array();
	foreach ($products as $index=>$product) {
# END: random:17710_17631 [2009 Mar 26 09:25] 
		foreach($product as $key=>$value)
			if (in_array($key, $product_keys))
				$_products[$index][$key] = $value;
			$freight_cost += $product['taxed_shipping_freight']*$product['amount'];	

# START: random:17710_17631 [2009 Mar 26 09:25] 
# START: random:20341 [2010 Jul 29 14:46] 
			$m_id = func_manufacturerid_for_group($product['shipping_freight'], $product['manufacturerid']);

			if (!isset($current_order_amount_in_us[$m_id])){
				$current_order_amount_in_us[$m_id] = 0;
			}
			$current_order_amount_in_us[$m_id] += $product["display_subtotal"];

//func_print_r($product);

# END: random:20341 [2010 Jul 29 14:46] 
			if (!isset($shipping_groups[$m_id])) {
# START: random:20341 [2010 Jul 29 14:46] 
				$manufact_data = func_query_first("SELECT * FROM $sql_tbl[manufacturers] WHERE manufacturerid = '$m_id'");
# END: random:20341 [2010 Jul 29 14:46]

//func_print_r($manufact_data); 
				$shipping_groups[$m_id]['group_name'] = $manufact_data['manufacturer'];
				$shipping_groups[$m_id]['manufact_text_displayed'] = $manufact_data['manufact_text_displayed'];
				$shipping_groups[$m_id]['cart_manufact_text_displayed'] = $manufact_data['cart_manufact_text_displayed'];
#
##
###
				$shipping_groups[$m_id]['m_state_code'] = $manufact_data['m_state'];
				$shipping_groups[$m_id]['m_country_code'] = $manufact_data['m_country'];

				$shipping_groups[$m_id]['d_minimum_order_amount'] = $manufact_data['d_minimum_order_amount'];
				$shipping_groups[$m_id]['d_minimum_order_amount_in_us'] = $manufact_data['d_minimum_order_amount_in_us'];
				$shipping_groups[$m_id]['d_for_orders_below_min_order_amount'] = $manufact_data['d_for_orders_below_min_order_amount'];
###
##
#
				$shipping_groups[$m_id]['m_city'] = $manufact_data['m_city'];
				$shipping_groups[$m_id]['m_state'] = func_get_state($manufact_data['m_state'], $manufact_data['m_country']);            
				$shipping_groups[$m_id]['m_country'] = func_get_country($manufact_data['m_country']);
# END: random:17710_17631 [2009 Mar 26 09:25] 
			}


//func_print_r($current_order_amount_in_us[$m_id]);

		}

	
		if (!empty($shipping_groups) && is_array($shipping_groups))
		foreach ($shipping_groups as $m_id => $s_g){
                        if ($shipping_groups[$m_id]['d_minimum_order_amount_in_us'] > 0 && $shipping_groups[$m_id]['d_minimum_order_amount'] == "applies_to_all_orders" && $current_order_amount_in_us[$m_id] < $shipping_groups[$m_id]['d_minimum_order_amount_in_us'] && $shipping_groups[$m_id]['d_for_orders_below_min_order_amount'] == "are_rejected"){
                                $need_add_more = $shipping_groups[$m_id]['d_minimum_order_amount_in_us'] - $current_order_amount_in_us[$m_id];
                                $shipping_groups[$m_id]['need_add_more'] = $need_add_more;
                        }
		}

		ksort($shipping_groups);

	$return = array(
		"total_cost" => price_format($total),
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
		"freight_cost" => price_format($freight_cost),
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
		"shipping_cost" => price_format($shipping_cost),
		"taxes" => $taxes["taxes"],
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
		"whole_taxes" => $whole_taxes['taxes'],
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
		"tax_cost" => price_format($taxes["total"]),
		"discount" => price_format($discount),
		"coupon" => $discount_coupon,
		"coupon_discount" => price_format($coupon_discount),
		"subtotal" => price_format($subtotal),

#
##
###
		"additional_fee" => $cart["additional_fee"],
###
##
#

# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
		"shipping_groups" => $shipping_groups,
		"shipping_costs" => $shipping_costs,
# START: random:20341 [2010 Jul 29 14:46] 
		"shipping_taxes" => $shipping_taxes,
# END: random:20341 [2010 Jul 29 14:46] 
		"display_shipping_costs" => $display_shipping_costs,
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
		"display_subtotal" => price_format($display_subtotal),
		"discounted_subtotal" => price_format($discounted_subtotal),
		"display_shipping_cost" => price_format($display_shipping_cost),
		"display_discounted_subtotal" => price_format($display_discounted_subtotal),
		"products" => $_products);

	if (!empty($active_modules["Special_Offers"])) {
		include $xcart_dir."/modules/Special_Offers/calculate_result.php";
	}

	return $return;
}

#
# This function calculates the payment method surcharge
#
function func_payment_method_surcharge ($total, $paymentid) {
	global $sql_tbl;

	$surcharge = 0;

	if (!empty($total))
		$surcharge = func_query_first_cell("SELECT IF (surcharge_type='$', surcharge, surcharge * $total / 100) as surcharge FROM $sql_tbl[payment_methods] WHERE paymentid='$paymentid'");

	return $surcharge;
}

#
# Generate products array in $cart
#
function func_products_in_cart($cart, $membershipid) {
	if (empty($cart) || empty($cart["products"]))
		return array();

	return func_products_from_scratch($cart["products"], $membershipid, false);
}

#
# Generate products array from scratch
#
function func_products_from_scratch($scratch_products, $membershipid, $persistent_products) {
	global $active_modules, $sql_tbl, $config, $xcart_dir;
	global $current_area, $store_language;

	$products = array();

	if (empty($scratch_products))
		return $products;

	$pids = array();
	foreach ($scratch_products as $product_data) {
		$pids[] = $product_data["productid"];
	}

	$int_res = func_query_hash("SELECT * FROM $sql_tbl[products_lng] WHERE code = '$store_language' AND productid IN ('".implode("','", $pids)."')", "productid", false);

	unset($pids);

	$hash = array();
	foreach ($scratch_products as $product_data) {

		$productid = $product_data["productid"];
		$cartid = $product_data["cartid"];
		$amount = $product_data["amount"];
		$variantid = $product_data["variantid"];
		if (isset($product_data['catalog_price'])) {
			$catalog_price = $product_data['catalog_price'];
		}
		$productcodes .= $product_data['productcode']. '", "';
		
		if (!is_numeric($amount))
			$amount = 0;

		$options = $product_data["options"];
		$product_options = false;
		$variant = array();

		if (!empty($active_modules['Product_Options']) && !empty($options) && is_array($options)) {
			if (!func_check_product_options($productid, $options))
				continue;

			list($variant, $product_options) = func_get_product_options_data($productid, $options, $membershipid);

			if (empty($variantid) && isset($variant['variantid']))
				$variantid = $variant['variantid'];

			if ($config["General"]["unlimited_products"]=="N" && !$persistent_products) {
				if ((isset($variant['avail']) && $variant['avail'] < $amount) || ($variant['variantid'] != $variantid && !empty($variantid)))
					continue;
			}
		}

		$avail_condition = "";
		if ($config["General"]["unlimited_products"] == "N" && !$persistent_products && empty($variant))
			$avail_condition = "($sql_tbl[products].avail >= ".doubleval($amount)." OR $sql_tbl[products].product_type = 'C') AND ";

		if (defined("X_MYSQL5018_COMP_MODE")) {
			$products_array = func_query_first("SELECT $sql_tbl[products].*, MIN($sql_tbl[pricing].price) as price FROM $sql_tbl[pricing],$sql_tbl[products] WHERE $sql_tbl[products].productid=$sql_tbl[pricing].productid AND $sql_tbl[products].forsale != 'N' AND $sql_tbl[products].productid='$productid' AND $avail_condition $sql_tbl[pricing].quantity<='$amount' AND $sql_tbl[pricing].membershipid IN('$membershipid', 0) AND $sql_tbl[pricing].variantid = '$variantid' GROUP BY $sql_tbl[products].productid ORDER BY $sql_tbl[pricing].quantity DESC");
			if (!empty($products_array)) {
				$tmp = func_query_first("SELECT id, image_path, image_x, image_y FROM $sql_tbl[images_T] WHERE id = '$productid' LIMIT 1");
				$products_array['is_thumbnail'] = $tmp['id'] ? 'Y' : '';
				$products_array['image_path'] = $tmp['image_path'];
				$products_array['image_x'] = $tmp['image_x'];
				$products_array['image_y'] = $tmp['image_y'];

				$tmp = func_query_first("SELECT id, image_path, image_x, image_y FROM $sql_tbl[images_P] WHERE id = '$productid' LIMIT 1");
				$products_array['is_pimage'] = $tmp['id'] ? 'Y' : '';
				$products_array['pimage_path'] = $tmp['image_path'];
				$products_array['pimage_x'] = $tmp['image_x'];
				$products_array['pimage_y'] = $tmp['image_y'];
			}

		} else {
			$products_array = func_query_first("SELECT $sql_tbl[products].*, MIN($sql_tbl[pricing].price) as price, IF($sql_tbl[images_T].id IS NULL, '', 'Y') as is_thumbnail, $sql_tbl[images_T].image_path, $sql_tbl[images_T].image_x, $sql_tbl[images_T].image_y, IF($sql_tbl[images_P].id IS NULL, '', 'P') as is_pimage, $sql_tbl[images_P].image_path as pimage_path, $sql_tbl[images_P].image_x as pimage_x, $sql_tbl[images_P].image_y as pimage_y FROM $sql_tbl[pricing],$sql_tbl[products] LEFT JOIN $sql_tbl[images_T] ON $sql_tbl[images_T].id = $sql_tbl[products].productid LEFT JOIN $sql_tbl[images_P] ON $sql_tbl[images_P].id = $sql_tbl[products].productid WHERE $sql_tbl[products].productid=$sql_tbl[pricing].productid AND $sql_tbl[products].forsale != 'N' AND $sql_tbl[products].productid='$productid' AND $avail_condition $sql_tbl[pricing].quantity<='$amount' AND $sql_tbl[pricing].membershipid IN('$membershipid', 0) AND $sql_tbl[pricing].variantid = '$variantid' GROUP BY $sql_tbl[products].productid ORDER BY $sql_tbl[pricing].quantity DESC");
		}

		if ($products_array) {


#
##
###
        if ($current_area == 'C' && $products_array["new_map_price"]>0){

                if ($products_array["new_map_price"] > $products_array["price"]){
                        $products_array["price"] = $products_array["new_map_price"];
                        $products_array['taxed_price'] = $products_array['price'];
                }

                $products_array["discount_avail"] = "N";
                $products_array["discount_slope"] = "";
                $products_array["discount_table"] = "";
        }
###
##
#


			$products_array = func_array_merge($product_data, $products_array);

			if ($catalog_price) {
				$products_array['catalog_price'] = $catalog_price;
			}

# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
			$products_array['manufact_text_displayed'] = func_query_first_cell("SELECT manufact_text_displayed FROM $sql_tbl[manufacturers] WHERE manufacturerid ='".$products_array['manufacturerid']."'");
			$products_array['cart_manufact_text_displayed'] = func_query_first_cell("SELECT cart_manufact_text_displayed FROM $sql_tbl[manufacturers] WHERE manufacturerid ='".$products_array['manufacturerid']."'");

# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
			$hash_key = $productid;

			#
			# If priduct's price is 0 then use customer-defined price
			#
			$free_price = false;
			if ($products_array["price"] == 0 && empty($products_array["slotid"])) {
				$free_price = true;
				$products_array["taxed_price"] = $products_array["price"] = price_format($product_data["free_price"] ? $product_data["free_price"] : 0);
			}

			
			if (!empty($active_modules['Product_Options']) && $options) {
				if (!empty($variant) && $products_array['product_type'] != 'C') {
					unset($variant['price']);
					if (is_null($variant['pimage_path'])) {
						func_unset($variant, "pimage_path", "pimage_x", "pimage_y");
					} else {
						$variant['is_pimage'] = 'W';
					}
					$products_array = func_array_merge($products_array, $variant);
				}

				$hash_key .= "|".$products_array['variantid'];

				if ($product_options === false) {
					unset($product_options);

				} else {
					$variant['price'] = $products_array['price'];
					$products_array["options_surcharge"] = 0;
					if ($product_options) {
						foreach($product_options as $o) {
							$products_array["options_surcharge"] += ($o['modifier_type'] == '%' ? ($products_array['price']*$o['price_modifier']/100) : $o['price_modifier']);
						}
					}
				}

			}

			if ($config["General"]["unlimited_products"]=="N" && !$persistent_products && ($products_array['avail']-$hash[$hash_key]) < $amount && $products_array['product_type'] != 'C')
				continue;

			#
			# Get thumbnail's URL (uses only if images stored in FS)
			#
			$products_array['is_thumbnail'] = ($products_array['is_thumbnail'] == 'Y');
# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 

			$products_array['provider'] = (empty($config['General']['default_provider_name'])) ? $products_array['provider'] : $config['General']['default_provider_name'];

# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
			if (!empty($products_array['pimage_path']) && !empty($products_array['is_pimage'])) {
				if ($products_array['is_pimage'] == 'P') {
					$products_array["pimage_url"] = func_get_image_url($products_array["productid"], 'P', $products_array['pimage_path']);
				} else {
					$products_array["pimage_url"] = func_get_image_url($products_array["variantid"], 'W', $products_array['pimage_path']);
				}

			} elseif ($products_array['is_thumbnail'] && !empty($products_array['image_path'])) {
				$products_array["pimage_url"] = func_get_image_url($products_array["productid"], 'T', $products_array['image_path']);

			} elseif (empty($products_array['is_pimage']) && !$products_array['is_thumbnail']) {
				$products_array["pimage_url"] = func_get_default_image("T");
				
			}

			if ($products_array["product_type"] != "C")
				$products_array["price"] += $products_array["options_surcharge"];
			else
				$products_array["price"] = $products_array["options_surcharge"];

			if ($products_array["price"] < 0)	
				$products_array["price"] = 0;

			if (!empty($active_modules["Product_Configurator"])) {
				include $xcart_dir."/modules/Product_Configurator/pconf_customer_price_modifier.php";
			}

			if ($current_area == "C" && $products_array["product_type"] != "C") {
				#
				# Calculate taxes and price including taxes
				#
				global $login;

				if (!empty($active_modules["Special_Offers"])) {
					include $xcart_dir."/modules/Special_Offers/calculate_taxes_restore.php";
				}
				else {
					$products_array["taxes"] = func_get_product_taxes($products_array, $login);
				}
			}

			$products_array["total"] = price_format($amount*$products_array["price"]);
			$products_array["product_options"] = $product_options;
			$products_array["options"] = $options;
			$products_array["amount"] = $amount;
			$products_array["cartid"] = $cartid;

			$products_array["product_orig"] = $products_array["product"];

			if (isset($int_res[$productid])) {
				$products_array["product"] = stripslashes($int_res[$productid]["product"]);
				$products_array["descr"] = stripslashes($int_res[$productid]["descr"]);
				$products_array["fulldescr"] = stripslashes($int_res[$productid]["fulldescr"]);

				func_unset($int_res, $productid);
			}

			if ($products_array["descr"] == strip_tags($products_array["descr"]))
				$products_array["descr"] = str_replace("\n", "<br />", $products_array["descr"]);

			if ($products_array["fulldescr"] == strip_tags($products_array["fulldescr"]))
				$products_array["fulldescr"] = str_replace("\n", "<br />", $products_array["fulldescr"]);

#
##
###
			$products_array["brand"] = func_query_first_cell("SELECT brand FROM $sql_tbl[brands] WHERE brandid='$products_array[brandid]'");
		        $category_info = func_query_first("SELECT $sql_tbl[products_categories].categoryid, $sql_tbl[categories].category FROM $sql_tbl[products_categories]  LEFT JOIN $sql_tbl[categories] ON $sql_tbl[products_categories].categoryid=$sql_tbl[categories].categoryid WHERE $sql_tbl[products_categories].productid = '$products_array[productid]' and $sql_tbl[products_categories].main = 'Y' LIMIT 1");
			$products_array["categoryid"] = $category_info["categoryid"];
			$products_array["category"] = $category_info["category"];
###
##
#

			$products[] = $products_array;

			$hash[$hash_key] += $amount;
		}
	}
	$manufacturer_products = array();
	$catalogs = array();
	$productcodes = substr($productcodes, 0, count($productcodes) - 5);
	$is_catalogs = func_query_column('SELECT catalog_sku FROM '.$sql_tbl['manufacturers'].' WHERE catalog_sku IN ("'.$productcodes.'")', 'catalog_sku');

	foreach ($products as $k => $p) {
		if (is_array($is_catalogs) && in_array($p['productcode'], $is_catalogs)){
			$catalogs[$k] = $p['manufacturerid'];
		} else {
			$manufacturer_products[$p['manufacturerid']][] = $p['productid'];
		}
	}
	
	foreach ($catalogs as $k => $m) {
		if (isset($manufacturer_products[$m]) && count($manufacturer_products[$m]) > 0 ) {
			if (isset($products[$k]['catalog_price'])) {
				$products[$k]['price'] = $products[$k]['catalog_price'];
			}
		}
	}

	if (!empty($active_modules["Product_Configurator"])) {
		include $xcart_dir."/modules/Product_Configurator/pconf_customer_sort_products.php";
	}

# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
	if (!empty($products)) {
		uasort($products, "func_manufacturerid_sort");
	}
	
# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
	return $products;
}

# START: random:1073746882_1073747063 [2008 Dec 24 16:25] 
function func_manufacturerid_sort($a, $b) {
	return strcmp($a["manufacturerid"], $b["manufacturerid"]);
}


# END: random:1073746882_1073747063 [2008 Dec 24 16:25] 
#
# This function generates the unique cartid number
#
function func_generate_cartid($cart_products) {
	global $cart;

	if (empty($cart["max_cartid"]))
		$cart["max_cartid"] = 0;

	$cart["max_cartid"]++;

	return $cart["max_cartid"];
}

#
# Detectd ESD product(s) in cart
#
function func_esd_in_cart($cart) {
	if (!empty($cart['products'])) {
		foreach($cart['products'] as $p) {
			if (!empty($p['distribution'])) {
				return true;
			}
		}
	}

	return false;
}

#
# Calculate total amount of all products in cart. Used for cart validation
#
function func_get_cart_products_amount($products) {
	$amount = 0;
	if (!empty($products) && is_array($products)) {
		foreach ($products as $product) {
			$amount += $product['amount'];
		}
	}

	return $amount;
}

#
# Validate cart contents
#
function func_cart_is_valid($cart, $userinfo) {
	# test: all total amount should not change
	$current_amount = func_get_cart_products_amount($cart['products']);
	$validated_products = func_products_in_cart($cart, $userinfo['membershipid']);
	$validated_amount = func_get_cart_products_amount($validated_products);

	$is_valid = ($current_amount == $validated_amount);

	return $is_valid;
}

