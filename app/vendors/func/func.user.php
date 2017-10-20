<?php

#
# Delete profile from customers table + all associated information
#
function func_delete_profile($user,$usertype, $is_redirect = true) {
	global $files_dir_name, $single_mode, $sql_tbl;
	global $active_modules;


#
##
###
	if ($usertype != "C"){
		die("Forbidden. Access denied. You cannot delete profile!");
		return false;
	}
###
##
#

	x_load('files','product');

	if ($usertype == "A" || ($active_modules["Simple_Mode"] && $usertype == "P")) {
		$users_count = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[customers] WHERE usertype='$usertype'");
		if ($users_count == 1) {
			if ($is_redirect)
				func_header_location("error_message.php?last_admin");
			return false;
		}
	}

	if ($usertype=="P" && !$single_mode) {
/*
		# If user is provider delete some associated info to keep DB integrity
		# Delete products
		#
		$products = func_query("SELECT productid FROM $sql_tbl[products] WHERE provider='$user'");
		if (!empty($products)) {
			foreach($products as $product)
				func_delete_product($product["productid"]);
		}

		#
		# Delete Shipping, Discounts, Coupons, States/Tax, Countries/Tax
		#
		db_query("DELETE FROM $sql_tbl[shipping_rates] where provider='$user'");
		db_query("DELETE FROM $sql_tbl[discounts] where provider='$user'");
		db_query("DELETE FROM $sql_tbl[discount_coupons] where provider='$user'");
		db_query("DELETE FROM $sql_tbl[extra_fields] where provider='$user'");
		db_query("DELETE FROM $sql_tbl[tax_rates] where provider='$user'");
		db_query("DELETE FROM $sql_tbl[zones] where provider='$user'");

		#
		# Delete provider's file dir
		#
		@func_rm_dir ("$files_dir_name/$user");
*/
	}

	#
	# If it is partner, then remove all his information
	#
	if ($usertype == "B" && func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[modules] WHERE module_name='XAffiliate'") > 0) {
		if (empty($active_modules["XAffiliate"])) {
			include $xcart_dir."/modules/XAffiliate/config.php";
		}

		db_query("DELETE FROM $sql_tbl[partner_clicks] WHERE login='$user'");
		db_query("DELETE FROM $sql_tbl[partner_commissions] WHERE login='$user'");
		db_query("DELETE FROM $sql_tbl[partner_payment] WHERE login='$user'");
		db_query("DELETE FROM $sql_tbl[partner_views] WHERE login='$user'");
		db_query("UPDATE $sql_tbl[customers] SET parent = '' WHERE parent = '$user' AND usertype = '$usertype'");
	}

	db_query("DELETE FROM $sql_tbl[register_field_values] WHERE login='$user'");
	db_query("DELETE FROM $sql_tbl[customers] WHERE login='$user' AND usertype='$usertype'");
	db_query("DELETE FROM $sql_tbl[login_history] WHERE login='$user'");

	if (!empty($active_modules['Special_Offers'])) {
		db_query("DELETE FROM $sql_tbl[customer_bonuses] WHERE login = '$user'");
	}

}

#
# Get information associated with user
#
function func_userinfo($user, $usertype, $need_password=false, $need_cc=false, $profile_area=NULL) {
	global $sql_tbl, $single_mode, $shop_language, $default_user_profile_fields, $config;
	global $active_modules;
	global $store_cc, $store_cvv2;

	if ($need_password || $need_cc)
		x_load('crypt');

	if (is_null($profile_area) || empty($profile_area))
		$profile_area = $usertype;

	if (!is_array($profile_area))
		$profile_area = array($profile_area);


	$userinfo = func_query_first("SELECT $sql_tbl[customers].*, $sql_tbl[memberships].membership, pm.membership as pending_membership, $sql_tbl[memberships].flag FROM $sql_tbl[customers] LEFT JOIN $sql_tbl[memberships] ON $sql_tbl[memberships].membershipid = $sql_tbl[customers].membershipid LEFT JOIN $sql_tbl[memberships] as pm ON pm.membershipid = $sql_tbl[customers].pending_membershipid WHERE $sql_tbl[customers].login='$user' AND $sql_tbl[customers].usertype='$usertype'");

	if (!empty($userinfo)) {
		$userinfo['titleid'] = func_detect_title(addslashes($userinfo['title']));
		$userinfo['b_titleid'] = func_detect_title(addslashes($userinfo['b_title']));
		$userinfo['s_titleid'] = func_detect_title(addslashes($userinfo['s_title']));
		$userinfo['title'] = func_get_title($userinfo['titleid']);
		$userinfo['b_title'] = func_get_title($userinfo['b_titleid']);
		$userinfo['s_title'] = func_get_title($userinfo['s_titleid']);
	}

	if ($need_password) {
		$userinfo["passwd1"] = $userinfo["passwd2"] = $userinfo["password"] = text_decrypt($userinfo["password"]);
		if (is_null($userinfo["password"])) {
			x_log_flag("log_decrypt_errors", "DECRYPT", "Could not decrypt password for the user ".$userinfo['login'], true);

		} elseif ($userinfo["password"] !== false) {
			$userinfo["passwd1"] = $userinfo["passwd2"] = stripslashes($userinfo["password"]);
		}
	}

	if ($store_cc && $need_cc) {
		$userinfo["card_number"] = text_decrypt($userinfo["card_number"]);
		if (is_null($userinfo["card_number"])) {
			x_log_flag("log_decrypt_errors", "DECRYPT", " Could not decrypt the field 'Card number' for the user ".$userinfo['login'], true);
		}
	}

	if ($store_cvv2 && $need_cc) {
		$userinfo["card_cvv2"] = text_decrypt($userinfo["card_cvv2"]);
		if (is_null($userinfo["card_cvv2"])) {
			x_log_flag("log_decrypt_errors", "DECRYPT", " Could not decrypt the field 'Card CVV2' for the user ".$userinfo['login'], true);
		}
	}

	list($userinfo["b_address"], $userinfo["b_address_2"]) = preg_split("/[\n\r]+/", $userinfo["b_address"]);
	$userinfo["b_statename"] = func_get_state($userinfo["b_state"], $userinfo["b_country"]);
	$userinfo["b_countryname"] = func_get_country($userinfo["b_country"]);
	list($userinfo["s_address"], $userinfo["s_address_2"]) = preg_split("/[\n\r]+/", $userinfo["s_address"]);
	$userinfo["s_statename"] = func_get_state($userinfo["s_state"], $userinfo["s_country"]);
	$userinfo["s_countryname"] = func_get_country($userinfo["s_country"]);
	if ($config["General"]["use_counties"] == "Y") {
		$userinfo["b_countyname"] = func_get_county($userinfo["b_county"]);
		$userinfo["s_countyname"] = func_get_county($userinfo["s_county"]);
	}

	if ($userinfo["usertype"] == "B" && !empty($active_modules["XAffiliate"])) {
		$userinfo["plan_id"] = func_query_first_cell("SELECT plan_id FROM $sql_tbl[partner_commissions] WHERE login = '$userinfo[login]'");
	}

	$email = $userinfo["email"];

	$userinfo['field_sections'] = array();

	# Get additional fields
	$fields = func_query("SELECT $sql_tbl[register_fields].fieldid, $sql_tbl[register_fields].section, $sql_tbl[register_field_values].value FROM $sql_tbl[register_fields] LEFT JOIN $sql_tbl[register_field_values] ON $sql_tbl[register_fields].fieldid = $sql_tbl[register_field_values].fieldid AND $sql_tbl[register_field_values].login = '$user' WHERE ($sql_tbl[register_fields].avail LIKE '%".implode("%' OR $sql_tbl[register_fields].avail LIKE '%", $profile_area)."%') ORDER BY $sql_tbl[register_fields].section, $sql_tbl[register_fields].orderby");
	if (empty($fields)) {
		$userinfo['additional_fields'] = array();
	} else {
		foreach($fields as $k => $v) {
			$fields[$k]['title'] = func_get_languages_alt("lbl_register_field_".$v['fieldid'], $shop_language);
			$userinfo['field_sections'][$v['section']] = true;
		}

		$userinfo['additional_fields'] = $fields;
	}

	# Get default fields
	$default_fields = unserialize($config["User_Profiles"]["register_fields"]);
	
	if (empty($default_fields) || !is_array($default_fields)) {
		$default_fields = array();
		if (!empty($default_user_profile_fields) && is_array($default_user_profile_fields)) {
			foreach($default_user_profile_fields as $k => $v) {
				if (is_array($v["avail"])) {
					$field_is_available = false;
					foreach ($profile_area as $a) {
						if ($v["avail"][$a] == 'Y') {
							$field_is_available = true;
							break;
						}
					}

				} else {
					$field_is_available = ($v["avail"] == 'Y' ? true : false);
				}
				
				if ($field_is_available)
					$default_fields[$k] = true;
			}
		}

	} else {
		$tmp = array();
		foreach($default_fields as $k => $v) {
			$found = false;
			foreach ($profile_area as $a) {
				if (strpos($v['avail'], $a) !== false) {
					$found = true;
					break;
				}
			}
			if (!$found)
				continue;

			$tmp[$v['field']] = true;
		}

		$default_fields = $tmp;
		unset($tmp);
	}

	if ($default_fields) {
		if (!$userinfo['field_sections']['P'] && ($default_fields['title'] || $default_fields['firstname'] || $default_fields['lastname'] || $default_fields['company'])) {
			$userinfo['field_sections']['P'] = true;
		}

		if (!$userinfo['field_sections']['B'] && ($default_fields['b_title'] || $default_fields['b_firstname'] || $default_fields['b_lastname'] || $default_fields['b_address'] || $default_fields['b_address_2'] || ($default_fields['b_county'] && $config["General"]["use_counties"] == "Y") || $default_fields['b_state'] || $default_fields['b_city'] || $default_fields['b_country'] || $default_fields['b_zipcode'])) {
			$userinfo['field_sections']['B'] = true;
		}

		if (!$userinfo['field_sections']['S'] && ($default_fields['s_title'] || $default_fields['s_firstname'] || $default_fields['s_lastname'] || $default_fields['s_address'] || $default_fields['s_address_2'] || ($default_fields['s_county'] && $config["General"]["use_counties"] == "Y") || $default_fields['s_state'] || $default_fields['s_city'] || $default_fields['s_country'] || $default_fields['s_zipcode'])) {
			$userinfo['field_sections']['S'] = true;
		}

		if (!$userinfo['field_sections']['C'] && ($default_fields['phone'] || $default_fields['email'] || $default_fields['fax'] || $default_fields['url'])) {
			$userinfo['field_sections']['C'] = true;
		}

		$userinfo['default_fields'] = $default_fields;
	}

	return $userinfo;
}

#
# This function generates password for anonymous customers
#
function func_generate_anonymous_password() {
	return md5(uniqid(rand(), true));
}

#
# This function generates username for anonymous customers
#
function func_generate_anonymous_username () {
	global $anonymous_username_prefix;

	return $anonymous_username_prefix.'-'.func_genid("U");
}

#
# This function validate accordance a county ID to a state and country code
#
function func_check_county($countyid, $statecode, $countrycode) {
	global $sql_tbl;

	$return = true;
	if (is_numeric($countyid)) {
		$statecode = addslashes($statecode);
		if (func_query_first_cell("SELECT COUNT(stateid) FROM $sql_tbl[states] WHERE code='$statecode' AND country_code='$countrycode'") > 0) {
			$return = (func_query_first_cell("SELECT COUNT(countyid) FROM $sql_tbl[counties], $sql_tbl[states] WHERE $sql_tbl[counties].stateid=$sql_tbl[states].stateid AND $sql_tbl[counties].countyid='$countyid' AND $sql_tbl[states].code='$statecode' AND $sql_tbl[states].country_code='$countrycode'") == 1);
		}
	}

	return $return;
}

#
# This function validate accordance a state code to a country code
#
function func_check_state($states, $statecode, $countrycode)
{
    if (!in_array($countrycode, ['US', 'CA'])) { //disable if not USA or Canada
        return true;
    }

    $country_flag = $state_flag = false;
    $return = true;
    foreach ($states as $val) {
        if ($val["country_code"] == $countrycode) {
            $country_flag = true;
            if ($val["state_code"] == $statecode)
                $state_flag = true;
        }
    }

    if ($country_flag && !$state_flag) {
        $return = false;
    }

    return $return;
}

#
# Get default register fields settings
#
function func_get_default_fields($fields_area) {
	global $config, $default_user_profile_fields, $sql_tbl;

	$default_fields = unserialize($config["User_Profiles"]["register_fields"]);

#
##
###
	if (!empty($default_fields) && is_array($default_fields)){
		$position_found = false;
		foreach ($default_fields as $k => $v){

			if ($v["field"] == "position"){
				$position_found = true;
				break;
			}
		}

		if (!$position_found){
			$position["field"] = "position";
			$position["avail"] = "AP";
			$position["required"] = "";
			$default_fields[] = $position;

			$update_default_fields = serialize($default_fields);
			db_query("UPDATE $sql_tbl[config] SET value='".addslashes($update_default_fields)."' WHERE name='register_fields'");
		}
	}

        if (!empty($default_fields) && is_array($default_fields)){
                $pbx_extension_found = false;
                foreach ($default_fields as $k => $v){

                        if ($v["field"] == "pbx_extension"){
                                $pbx_extension_found = true;
                                break;
                        }
                }

                if (!$pbx_extension_found){
                        $pbx_extension["field"] = "pbx_extension";
                        $pbx_extension["avail"] = "AP";
                        $pbx_extension["required"] = "";
                        $default_fields[] = $pbx_extension;

                        $update_default_fields = serialize($default_fields);
                        db_query("UPDATE $sql_tbl[config] SET value='".addslashes($update_default_fields)."' WHERE name='register_fields'");
                }
        }
###
##
#

//func_print_r($default_fields);

	if (!$default_fields) {
		$default_fields = array();
		foreach ($default_user_profile_fields as $k => $v) {
			$default_fields[$k]["title"] = func_get_default_field($k);
			$default_fields[$k]['field'] = $k;
    
			foreach (array("avail", "required") as $fn) {
				if (is_array($v[$fn]) && is_array($fields_area)) {
					foreach ($fields_area as $fa) {
						if ($v[$fn][$fa] == 'Y') {
							$default_fields[$k][$fn] = 'Y';
							break;
						}
					}
    
				} else {
					$default_fields[$k][$fn] = is_array($v[$fn]) ? $v[$fn][$fields_area] : $v[$fn];
				}
			}
		}

	} else {
		$tmp = array();
	    foreach ($default_fields as $k => $v) {
			$is_avail = false;
			$is_required = false;
			if (is_array($fields_area)) {
				foreach ($fields_area as $fa) {
					if (strpos($v['avail'], $fa) !== FALSE)
						$is_avail = true;

					if (strpos($v['required'], $fa) !== FALSE)
						$is_required = true;
				}

			} else {
				$is_avail = strpos($v['avail'], $fields_area) !== FALSE;
				$is_required = strpos($v['required'], $fields_area) !== FALSE;
			}

	        $tmp[$v['field']] = array(
    	        "avail" => $is_avail ? "Y" : "",
        	    "required" => $is_required ? "Y" : "",
            	"title" => func_get_default_field($v['field'])
	        );
    	}

	    $default_fields = $tmp;
    	unset($tmp);
	}

	return $default_fields;
}

#
# Get additional register fields settings
#
function func_get_additional_fields($area = '', $user = '') {
	global $sql_tbl, $shop_language;

	if ($area) {
		if (!is_array($area))
			$area = array($area);

		$avail_condition = "($sql_tbl[register_fields].avail LIKE '%".implode("%' OR $sql_tbl[register_fields].avail LIKE '%", $area)."%')";
		$required_condition = "($sql_tbl[register_fields].required LIKE '%".implode("%' OR $sql_tbl[register_fields].required LIKE '%", $area)."%')";

		$fields = func_query("SELECT $sql_tbl[register_fields].*, IF($avail_condition, 'Y', '') as avail, IF($required_condition, 'Y', '') as required, $sql_tbl[register_field_values].value FROM $sql_tbl[register_fields] LEFT JOIN $sql_tbl[register_field_values] ON $sql_tbl[register_fields].fieldid = $sql_tbl[register_field_values].fieldid AND $sql_tbl[register_field_values].login = '$user' ORDER BY $sql_tbl[register_fields].section, $sql_tbl[register_fields].orderby");

	} else {
		$fields = func_query("SELECT * FROM $sql_tbl[register_fields] ORDER BY section, orderby");
	}

	if ($fields) {
		foreach ($fields as $k => $v) {
			$fields[$k]['title'] = func_get_languages_alt("lbl_register_field_".$v['fieldid'], $shop_language);
			if (!$area) {
				$fields[$k]['avail'] = func_keys2hash($v['avail']);
				$fields[$k]['required'] = func_keys2hash($v['required']);

			} elseif ($v['type'] == 'S' && $v['variants']) {
				$fields[$k]['variants'] = @explode(";", $v['variants']);
			}
		}
	}

	return $fields;
}

#
# Get additional register fields settings
#
function func_get_add_contact_fields($area = '') {
	global $sql_tbl;

	if (!empty($area)) {
		$fields = func_query("SELECT *, IF(avail LIKE '%$area%', 'Y', '') as avail, IF(required LIKE '%$area%', 'Y', '') as required FROM $sql_tbl[contact_fields] ORDER BY orderby");
	}
	else {
		$fields = func_query("SELECT * FROM $sql_tbl[contact_fields] ORDER BY orderby");
	}

	if ($fields) {
		foreach ($fields as $k => $v) {
			$fields[$k]['title'] = func_get_languages_alt("lbl_contact_field_".$v['fieldid']);
			$fields[$k]['ftype'] = 'additional';
			if (empty($area)) {
				$fields[$k]['avail'] = func_keys2hash($v['avail']);
				$fields[$k]['required'] = func_keys2hash($v['required']);
			}
			elseif ($v['type'] == 'S' && !empty($v['variants'])) {
				$fields[$k]['variants'] = explode(";", $v['variants']);
			}
		}
	}

	return $fields;
}

#
# Transform key string to hash-array
#
function func_keys2hash($str) {
	$tmp = array();

	if (strlen($str) == 0)
		return $tmp;

	for ($x = 0; $x < strlen($str); $x++)
		$tmp[$str[$x]] = 'Y';

	return $tmp;
}

#
# Merge and sort default and additional contact fields
#
function func_contact_fields_sort($default_fields = array(), $additional_fields = array()) {
    
    if (!is_array($default_fields)) {
        $default_fields = array();
    }
    if (!is_array($additional_fields)) {
        $additional_fields = array();
    }

    if (empty($default_fields) && empty($additional_fields)) {
        return array();
    }

    $all_fields = array();

    foreach ($default_fields as $df) {
        $all_fields[] = $df;
    }

    foreach ($additional_fields as $af) {
        $all_fields[] = $af;
    }
    
    usort($all_fields, 'func_sort_arr_by_orderby');

    return $all_fields;
}