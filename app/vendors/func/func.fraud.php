<?php
x_load('cart','mail','order','product','taxes');

/*
$order = $order_data["order"];
$userinfo = $order_data["userinfo"];
$products = $order_data["products"];
$giftcerts = $order_data["giftcerts"];
*/

function func_correct_field($field){

	$field = trim($field);
	$field = preg_replace('/\s+/',' ',$field);
	$field = preg_replace("/[^\w\s\[,.\-\/\@_\]]/", "", $field);
	$field = strtoupper($field);
	return $field;
}

function func_CHECK_B_S($order_data){

	$full_address_s = $order_data["userinfo"]["s_address"] . "-". $order_data["userinfo"]["s_address_2"] . "-". $order_data["userinfo"]["s_city"] . "-". $order_data["userinfo"]["s_state"] . "-". $order_data["userinfo"]["s_country"] . "-". $order_data["userinfo"]["s_zipcode"];
	$full_address_s = func_correct_field($full_address_s);

        $full_addresb_b = $order_data["userinfo"]["b_address"] . "-". $order_data["userinfo"]["b_address_2"] . "-". $order_data["userinfo"]["b_city"] . "-". $order_data["userinfo"]["b_state"] . "-". $order_data["userinfo"]["b_country"] . "-". $order_data["userinfo"]["b_zipcode"];
        $full_addresb_b = func_correct_field($full_addresb_b);

	if ($full_address_s == $full_addresb_b){
		$fraud_score = "1";
		$fraud_result = "positive";
	} else {
		$fraud_score = "-1";
		$fraud_result = "negative";
	}

	$fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;

	return $fraud_score_arr;
}

function func_IS_EMAIL_DOMAIN_FREE($order_data){
	global $config;

	$email = $order_data["userinfo"]["email"];
	$fraud_score = "1";
	$fraud_result = "positive";

	$fraud_domains_free_email_provider_arr = explode(',', $config["Fraud_check"]["fraud_domains_free_email_provider"]);
	if (!empty($fraud_domains_free_email_provider_arr) && is_array($fraud_domains_free_email_provider_arr)){
		foreach ($fraud_domains_free_email_provider_arr as $k => $v){

			$domain = "@".trim($v);
			if (strpos($email, $domain) !== false){
				$fraud_score = "-1";
				$fraud_result = "negative";
				break;
			}
		}
	}

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;

	return $fraud_score_arr;
}

function func_CHECK_EMAIL_VS_NAME($order_data){

	$fraud_score = "-1";
	$fraud_result = "negative";
	$email_arr = explode('@', $order_data["userinfo"]["email"]);
	$email_1 = strtoupper($email_arr[0]);

	$firstname = func_correct_field($order_data["userinfo"]["firstname"]);
	$firstname_arr = explode(" ", $firstname);

	if (!empty($firstname_arr) && is_array($firstname_arr) && !empty($email_1)){
		foreach ($firstname_arr as $k => $v){
			$name = trim($v);

			if (!empty($email_1) && !empty($name))
			if (strpos($email_1, $name) !== false){
				$fraud_score = "1";
				$fraud_result = "positive";
				break;
			}
		}
	}

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;

	return $fraud_score_arr;
}

function func_ORDER_FULLNAMES($order_data){

	$fraud_score = "-1";

	$names = array();
	
	$firstname = func_correct_field($order_data["userinfo"]["firstname"]);
	if (!empty($firstname)){
		$names[] = $firstname;
	}

	$b_firstname = func_correct_field($order_data["userinfo"]["b_firstname"]);
        if (!empty($b_firstname)){
                $names[] = $b_firstname;
        }

	$s_firstname = func_correct_field($order_data["userinfo"]["s_firstname"]);
        if (!empty($s_firstname)){
                $names[] = $s_firstname;
        }

	$names = array_unique($names);

	$count_names = count($names);

	if ($count_names > 0){
		$fraud_score = 1/$count_names;

	        if ($fraud_score == "1"){
        	        $fraud_result = "positive";
	        } elseif ($fraud_score < 1) {
			$fraud_result = "negative";
		}
	}

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;

	return $fraud_score_arr;
}

function func_CHECK_STATES($order_data){
	global $sql_tbl, $countries;

        $fraud_score = "-1";
	$fraud_result = "negative";

	$s_state = func_correct_field($order_data["userinfo"]["s_state"]);
	$b_state = func_correct_field($order_data["userinfo"]["b_state"]);

	$customer_ip = $order_data["order"]["extra"]["ip"];

	$geoip_state = "";
	$phone_area_code_state = "";

	$geo_litecity_location = func_get_geoip_locations($customer_ip);
	if (!empty($geo_litecity_location)) {
		$geoip_state = func_correct_field($geo_litecity_location["region"]);
	}

        $userinfo_phone = $order_data["userinfo"]["phone"];
        $userinfo_phone = str_replace(" ", "", $userinfo_phone);
        $userinfo_phone = str_replace("(", "", $userinfo_phone);
        $userinfo_phone = str_replace(")", "", $userinfo_phone);
        $userinfo_area_code = substr($userinfo_phone, 0, 3);

        $Telephone_area_codes = func_query_first("SELECT * FROM $sql_tbl[Telephone_area_codes] WHERE area_code='".addslashes($userinfo_area_code)."'");

        if (!empty($Telephone_area_codes)){

		foreach ($countries as $k => $v){
			if ($v["country"] == $Telephone_area_codes["country"]){
				$country_code = $v["country_code"];
				break;
			}
		}

	        if (!empty($country_code)){
        	        $state_name = trim($Telephone_area_codes["state"]);
                	$areacode_state = func_query_first_cell("SELECT code FROM $sql_tbl[states] WHERE state='$state_name' AND country_code='$country_code'");
	                $Telephone_area_codes["state"] = $areacode_state;
	        }
        	else {
                	$areacode_state = $Telephone_area_codes["state"];
	        }
        }

	if ($s_state == $b_state && $b_state == $geoip_state && $s_state == $areacode_state){
		$fraud_score = "1";
		$fraud_result = "positive";
	}

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;

	return $fraud_score_arr;
}

function func_GEOIP_CITY_VS_B_S($order_data){
        global $sql_tbl;

        $fraud_score = "-1";

        $s_city = func_correct_field($order_data["userinfo"]["s_city"]);
        $b_city = func_correct_field($order_data["userinfo"]["b_city"]);

        $customer_ip = $order_data["order"]["extra"]["ip"];

        $geoip_city = "";

		$geo_litecity_location = func_get_geoip_locations($customer_ip);
		if (!empty($geo_litecity_location)) {
			$geoip_city = func_correct_field($geo_litecity_location["city"]);
		}

	$names = array();

//	if (!empty($s_city)){
		$names[] = $s_city;
//	}

//        if (!empty($b_city)){
                $names[] = $b_city;
//        }

//        if (!empty($geoip_city)){
                $names[] = $geoip_city;
//        }

        $names = array_unique($names);

        $count_names = count($names);

        if ($count_names > 0){
                $fraud_score = 1/$count_names;

                if ($fraud_score == "1"){
                        $fraud_result = "positive";
                } elseif ($fraud_score < 1) {
                        $fraud_result = "negative";
                }
        }

//func_print_r($names, $count_names, $fraud_score);
//die();

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;

	return $fraud_score_arr;
}

function func_CHECK_OK_ORDERS_FOR_EMAIL($order_data){
        global $sql_tbl;

//        $fraud_score = "-1";
        $fraud_score = "0";
	$fraud_result = "neutral";

	$email = $order_data["userinfo"]["email"];
	$order_date = $order_data["order"]["date"];

	$_res = db_query("SELECT $sql_tbl[orders].orderid, $sql_tbl[orders].date, $sql_tbl[orders].order_prefix, $sql_tbl[order_groups].cb_status, $sql_tbl[order_groups].dc_status FROM $sql_tbl[orders] LEFT JOIN $sql_tbl[order_groups] ON $sql_tbl[order_groups].orderid=$sql_tbl[orders].orderid WHERE email='$email' AND ($sql_tbl[order_groups].dc_status='S' || $sql_tbl[order_groups].dc_status='G') AND $sql_tbl[orders].date <= '$order_date' GROUP BY $sql_tbl[orders].orderid ORDER BY $sql_tbl[orders].orderid");
	$total_items = db_num_rows($_res);

	$additional_info = array();
	if ($total_items > 0){
		while($o = db_fetch_array($_res)) {
			$o["cb_status_name"] = func_query_first_cell("SELECT name FROM $sql_tbl[order_statuses] WHERE code='$o[cb_status]'");
			$o["dc_status_name"] = func_query_first_cell("SELECT name FROM $sql_tbl[order_statuses] WHERE code='$o[dc_status]'");
			$additional_info[] = $o;
		}
	}

	$time_condition = $order_date - 60*60*24*1;
        $_res_min_day = db_query("SELECT $sql_tbl[orders].orderid FROM $sql_tbl[orders] LEFT JOIN $sql_tbl[order_groups] ON $sql_tbl[order_groups].orderid=$sql_tbl[orders].orderid WHERE email='$email' AND ($sql_tbl[order_groups].dc_status='S' || $sql_tbl[order_groups].dc_status='G') AND $sql_tbl[orders].date <= '$time_condition' GROUP BY $sql_tbl[orders].orderid");
        $total_items_min_day = db_num_rows($_res_min_day);

	if ($total_items_min_day > 0){
		$fraud_score = $total_items_min_day/$total_items;

                if ($fraud_score > "0"){
                        $fraud_result = "positive";
                }
	}

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;
	$fraud_score_arr["additional_info"] = $additional_info;

	return $fraud_score_arr;
}

function func_CHECK_FULLNAMES_FOR_EMAIL($order_data){
        global $sql_tbl;

        $fraud_score = "-1";

	$email = $order_data["userinfo"]["email"];
	$order_date = $order_data["order"]["date"];

	$_res = db_query("SELECT DISTINCT (firstname), orderid, order_prefix, date FROM $sql_tbl[orders] WHERE email='$email' AND date <= '$order_date' GROUP BY firstname");
        $total_items = db_num_rows($_res);

	$additional_info = array();
	if ($total_items > 0){

                while($o = db_fetch_array($_res)) {
                        $additional_info[] = $o;
                }

		$fraud_score = 1/$total_items;

                if ($fraud_score == "1"){
                        $fraud_result = "positive";
                } elseif ($fraud_score < 1) {
                        $fraud_result = "negative";
                }
	}

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;
	$fraud_score_arr["additional_info"] = $additional_info;

	return $fraud_score_arr;
}

function func_CHECK_DIFFERENT_SHIPPINGS_FOR_IP($order_data){
        global $sql_tbl;

        $fraud_score = "-1";

	$customer_ip = $order_data["order"]["extra"]["ip"];
	$order_date = $order_data["order"]["date"];

        $time_condition = $order_date - 60*60*24*7;
        $orders = func_query("SELECT orderid, order_prefix, date, s_address, s_city, s_state, s_country, s_zipcode FROM $sql_tbl[orders] WHERE date >= '$time_condition' AND date <= '$order_date'");

	$additional_info = array();

	if (!empty($orders) && is_array($orders)){

		$names = array();
		$full_address_names = array();

		foreach ($orders as $k => $v){
			$ip = func_query_first_cell("SELECT value FROM $sql_tbl[order_extras] WHERE orderid='$v[orderid]' AND khash='ip'");

			if ($customer_ip == $ip){
				$full_address_s = $v["s_address"] . "-" . $v["s_city"] . "-". $v["s_state"] . "-". $v["s_country"] . "-". $v["s_zipcode"];
			        $full_address_s = func_correct_field($full_address_s);
				$names[$v["orderid"]] = $full_address_s;
				$full_address_names[$v["orderid"]] = $v;
			}
		}

	        $names = array_unique($names);
        	$count_names = count($names);

	        if ($count_names > 0){

			foreach ($names as $k => $v){
				$s_address_arr = explode("\n", $full_address_names[$k]["s_address"]);
				$full_address_names[$k]["s_address1"] = trim($s_address_arr[0]);
				$full_address_names[$k]["s_address2"] = trim($s_address_arr[1]);
				$additional_info[] = $full_address_names[$k];
			}

        	        $fraud_score = 1/$count_names;

	                if ($fraud_score == "1"){
        	                $fraud_result = "positive";
                	} elseif ($fraud_score < 1) {
                        	$fraud_result = "negative";
	                }
	        }
	}

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;
	$fraud_score_arr["additional_info"] = $additional_info;

	return $fraud_score_arr;
}

function func_CHECK_DIFFERENT_BILLINGS_FOR_IP($order_data){
        global $sql_tbl;

        $fraud_score = "-1";

        $customer_ip = $order_data["order"]["extra"]["ip"];
	$order_date = $order_data["order"]["date"];

        $time_condition = $order_date - 60*60*24*7;
        $orders = func_query("SELECT orderid, order_prefix, date, b_address, b_city, b_state, b_country, b_zipcode FROM $sql_tbl[orders] WHERE date >= '$time_condition' AND date <= '$order_date'");

	$additional_info = array();

        if (!empty($orders) && is_array($orders)){

                $names = array();
		$full_address_names = array();

                foreach ($orders as $k => $v){
                        $ip = func_query_first_cell("SELECT value FROM $sql_tbl[order_extras] WHERE orderid='$v[orderid]' AND khash='ip'");

                        if ($customer_ip == $ip){
                                $full_address_b = $v["b_address"] . "-" . $v["b_city"] . "-". $v["b_state"] . "-". $v["b_country"] . "-". $v["b_zipcode"];
                                $full_address_b = func_correct_field($full_address_b);
                                $names[$v["orderid"]] = $full_address_b;
                                $full_address_names[$v["orderid"]] = $v;
                        }
                }

                $names = array_unique($names);

                $count_names = count($names);

                if ($count_names > 0){

                        foreach ($names as $k => $v){
                                $b_address_arr = explode("\n", $full_address_names[$k]["b_address"]);
                                $full_address_names[$k]["b_address1"] = trim($b_address_arr[0]);
                                $full_address_names[$k]["b_address2"] = trim($b_address_arr[1]);
                                $additional_info[] = $full_address_names[$k];
                        }

                        $fraud_score = 1/$count_names;

	                if ($fraud_score == "1"){
                        	$fraud_result = "positive";
                	} elseif ($fraud_score < 1) {
        	                $fraud_result = "negative";
	                }

                }
        }

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;
	$fraud_score_arr["additional_info"] = $additional_info;

	return $fraud_score_arr;
}

function func_CHECK_DIFFERENT_SHIPPINGS_FOR_PHONE($order_data){
        global $sql_tbl;

        $fraud_score = "-1";

        $phone = $order_data["userinfo"]["phone"];
	$phone = preg_replace("/[^0-9]/", "", $phone);
	$order_date = $order_data["order"]["date"];

        $time_condition = $order_date - 60*60*24*180;
        $orders = func_query("SELECT orderid, order_prefix, date, phone, s_address, s_city, s_state, s_country, s_zipcode FROM $sql_tbl[orders] WHERE date >= '$time_condition' AND date <= '$order_date'");

	$additional_info = array();

	if (!empty($orders) && is_array($orders)){

		$names = array();
		$full_address_names = array();

		foreach ($orders as $k => $v){

			$tmp_phone = preg_replace("/[^0-9]/", "", $v["phone"]);

                        if ($phone == $tmp_phone){
                                $full_address_s = $v["s_address"] . "-" . $v["s_city"] . "-". $v["s_state"] . "-". $v["s_country"] . "-". $v["s_zipcode"];
                                $full_address_s = func_correct_field($full_address_s);
                                $names[$v["orderid"]] = $full_address_s;
				$full_address_names[$v["orderid"]] = $v;
                        }
		}

                $names = array_unique($names);

                $count_names = count($names);

                if ($count_names > 0){

                        foreach ($names as $k => $v){
                                $s_address_arr = explode("\n", $full_address_names[$k]["s_address"]);
                                $full_address_names[$k]["s_address1"] = trim($s_address_arr[0]);
                                $full_address_names[$k]["s_address2"] = trim($s_address_arr[1]);
                                $additional_info[] = $full_address_names[$k];
                        }

                        $fraud_score = 1/$count_names;

	                if ($fraud_score == "1"){
                        	$fraud_result = "positive";
                	} elseif ($fraud_score < 1) {
        	                $fraud_result = "negative";
	                }

                }
	}

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;
	$fraud_score_arr["additional_info"] = $additional_info;

	return $fraud_score_arr;
}

function func_CHECK_DIFFERENT_BILLINGSS_FOR_PHONE($order_data){
        global $sql_tbl;

        $fraud_score = "-1";

        $phone = $order_data["userinfo"]["phone"];
        $phone = preg_replace("/[^0-9]/", "", $phone);
	$order_date = $order_data["order"]["date"];

        $time_condition = $order_date - 60*60*24*180;
        $orders = func_query("SELECT orderid, order_prefix, date, phone, b_address, b_city, b_state, b_country, b_zipcode FROM $sql_tbl[orders] WHERE date >= '$time_condition' AND date <= '$order_date'");

	$additional_info = array();

        if (!empty($orders) && is_array($orders)){

                $names = array();
		$full_address_names = array();

                foreach ($orders as $k => $v){

                        $tmp_phone = preg_replace("/[^0-9]/", "", $v["phone"]);

                        if ($phone == $tmp_phone){
                                $full_address_b = $v["b_address"] . "-" . $v["b_city"] . "-". $v["b_state"] . "-". $v["b_country"] . "-". $v["b_zipcode"];
                                $full_address_b = func_correct_field($full_address_b);
                                $names[$v["orderid"]] = $full_address_b;
				$full_address_names[$v["orderid"]] = $v;
                        }
                }

                $names = array_unique($names);

                $count_names = count($names);

                if ($count_names > 0){

                        foreach ($names as $k => $v){
                                $b_address_arr = explode("\n", $full_address_names[$k]["b_address"]);
                                $full_address_names[$k]["b_address1"] = trim($b_address_arr[0]);
                                $full_address_names[$k]["b_address2"] = trim($b_address_arr[1]);
                                $additional_info[] = $full_address_names[$k];
                        }

                        $fraud_score = 1/$count_names;

	                if ($fraud_score == "1"){
                        	$fraud_result = "positive";
                	} elseif ($fraud_score < 1) {
        	                $fraud_result = "negative";
	                }

                }
        }

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;
	$fraud_score_arr["additional_info"] = $additional_info;

	return $fraud_score_arr;
}

function func_CHECK_DIFFERENT_SHIPPINGS_FOR_EMAIL($order_data){
        global $sql_tbl;

        $fraud_score = "-1";

        $email = $order_data["userinfo"]["email"];
	$order_date = $order_data["order"]["date"];

        $time_condition = $order_date - 60*60*24*180;
        $orders = func_query("SELECT orderid, order_prefix, date, email, s_address, s_city, s_state, s_country, s_zipcode FROM $sql_tbl[orders] WHERE date >= '$time_condition' AND date <= '$order_date' AND email='$email'");

	$additional_info = array();

        if (!empty($orders) && is_array($orders)){

                $names = array();
		$full_address_names = array();

                foreach ($orders as $k => $v){
                	$full_address_s = $v["s_address"] . "-" . $v["s_city"] . "-". $v["s_state"] . "-". $v["s_country"] . "-". $v["s_zipcode"];
                        $full_address_s = func_correct_field($full_address_s);
                        $names[$v["orderid"]] = $full_address_s;
			$full_address_names[$v["orderid"]] = $v;
                }

                $names = array_unique($names);

                $count_names = count($names);

                if ($count_names > 0){

                        foreach ($names as $k => $v){
                                $s_address_arr = explode("\n", $full_address_names[$k]["s_address"]);
                                $full_address_names[$k]["s_address1"] = trim($s_address_arr[0]);
                                $full_address_names[$k]["s_address2"] = trim($s_address_arr[1]);
                                $additional_info[] = $full_address_names[$k];
                        }

                        $fraud_score = 1/$count_names;

	                if ($fraud_score == "1"){
                        	$fraud_result = "positive";
                	} elseif ($fraud_score < 1) {
        	                $fraud_result = "negative";
	                }
                }
        }

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;
	$fraud_score_arr["additional_info"] = $additional_info;

	return $fraud_score_arr;
}

function func_CHECK_DIFFERENT_BILLINGS_FOR_EMAIL($order_data){
        global $sql_tbl;

        $fraud_score = "-1";

        $email = $order_data["userinfo"]["email"];
	$order_date = $order_data["order"]["date"];

        $time_condition = $order_date - 60*60*24*180;
        $orders = func_query("SELECT orderid, order_prefix, date, email, b_address, b_city, b_state, b_country, b_zipcode FROM $sql_tbl[orders] WHERE date >= '$time_condition' AND date <= '$order_date' AND email='$email'");

	$additional_info = array();

        if (!empty($orders) && is_array($orders)){

                $names = array();
		$full_address_names = array();

                foreach ($orders as $k => $v){
                        $full_address_b = $v["b_address"] . "-" . $v["b_city"] . "-". $v["b_state"] . "-". $v["b_country"] . "-". $v["b_zipcode"];
                        $full_address_b = func_correct_field($full_address_b);
                        $names[$v["orderid"]] = $full_address_b;
			$full_address_names[$v["orderid"]] = $v;
                }

                $names = array_unique($names);

                $count_names = count($names);

                if ($count_names > 0){

                        foreach ($names as $k => $v){
                                $b_address_arr = explode("\n", $full_address_names[$k]["b_address"]);
                                $full_address_names[$k]["b_address1"] = trim($b_address_arr[0]);
                                $full_address_names[$k]["b_address2"] = trim($b_address_arr[1]);
                                $additional_info[] = $full_address_names[$k];
                        }

                        $fraud_score = 1/$count_names;

	                if ($fraud_score == "1"){
                        	$fraud_result = "positive";
                	} elseif ($fraud_score < 1) {
        	                $fraud_result = "negative";
	                }
                }
        }

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;
	$fraud_score_arr["additional_info"] = $additional_info;

	return $fraud_score_arr;
}

function func_CHECK_DIFFERENT_SHIPPINGS_FOR_CARD($order_data){
        global $sql_tbl;

	$fraud_score = "1";
	$fraud_result = "positive";
	$additional_info = array();

	$order_date = $order_data["order"]["date"];

        $details = func_query_first_cell("SELECT details FROM $sql_tbl[orders] WHERE orderid='".$order_data["order"]["orderid"]."'");

	$credit_card_found = false;
	$credit_card_type_found = false;

	if (!empty($details)){

		$details = text_decrypt($details);

		if (strpos($details, "last 4 card numbers:") !== false){
			$details_arr = explode("last 4 card numbers:", $details);
			$credit_card_arr = explode(")", $details_arr[1]);
			$credit_card = trim($credit_card_arr[0]);
			$credit_card_found = true;
		}

                if (strpos($details, "card type:") !== false){
                        $details_arr = explode("card type:", $details);
                        $credit_card_arr = explode(")", $details_arr[1]);
                        $credit_card_type = trim($credit_card_arr[0]);
                        $credit_card_type_found = true;
                }
	}

	if ($credit_card_found && $credit_card_type_found){

		$credit_card_val = $credit_card."-".$credit_card_type;
		$credit_card_val = func_correct_field($credit_card_val);

	        $time_condition = $order_date - 60*60*24*180;
        	$all_details = func_query("SELECT details, orderid, order_prefix, date, s_address, s_city, s_state, s_country, s_zipcode FROM $sql_tbl[orders] WHERE date >= '$time_condition' AND date <= '$order_date'");

	        if (!empty($all_details) && is_array($all_details)){

        	        $names = array();
			$full_address_names = array();

                	foreach ($all_details as $k => $v){

				$details = text_decrypt($v["details"]);

				$tmp_credit_card_found = false;
				$tmp_credit_card_type_found = false;
				$tmp_credit_card_val = "";

		                if (strpos($details, "last 4 card numbers:") !== false){
                		        $details_arr = explode("last 4 card numbers:", $details);
		                        $credit_card_arr = explode(")", $details_arr[1]);
                		        $credit_card = trim($credit_card_arr[0]);
		                        $tmp_credit_card_found = true;
		                }

		                if (strpos($details, "card type:") !== false){
                		        $details_arr = explode("card type:", $details);
		                        $credit_card_arr = explode(")", $details_arr[1]);
                		        $credit_card_type = trim($credit_card_arr[0]);
		                        $tmp_credit_card_type_found = true;
                		}

				if ($tmp_credit_card_found && $tmp_credit_card_type_found){
			                $tmp_credit_card_val = $credit_card."-".$credit_card_type;
			                $tmp_credit_card_val = func_correct_field($tmp_credit_card_val);
				}

        	                if ($credit_card_val == $tmp_credit_card_val){
                	                $full_address_s = $v["s_address"] . "-" . $v["s_city"] . "-". $v["s_state"] . "-". $v["s_country"] . "-". $v["s_zipcode"];
                        	        $full_address_s = func_correct_field($full_address_s);
                                	$names[$v["orderid"]] = $full_address_s;
					$full_address_names[$v["orderid"]] = $v;
	                        }
        	        }

                	$names = array_unique($names);

	                $count_names = count($names);

        	        if ($count_names > 0){

	                        foreach ($names as $k => $v){
        	                        $s_address_arr = explode("\n", $full_address_names[$k]["s_address"]);
                	                $full_address_names[$k]["s_address1"] = trim($s_address_arr[0]);
                        	        $full_address_names[$k]["s_address2"] = trim($s_address_arr[1]);
                                	$additional_info[] = $full_address_names[$k];
	                        }

                	        $fraud_score = 1/$count_names;

	        	        if ($fraud_score == "1"){
        	        	        $fraud_result = "positive";
		                } elseif ($fraud_score < 1) {
                		        $fraud_result = "negative";
		                }
	                }
        	}
	}

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;
	$fraud_score_arr["additional_info"] = $additional_info;

	return $fraud_score_arr;
}

function func_CHECK_DIFFERENT_BILLING_FOR_SHIPPING($order_data){
        global $sql_tbl;

        $fraud_score = "-1";
	$additional_info = array();

	$order_date = $order_data["order"]["date"];

	$address_s_from_db = func_query_first("SELECT s_address, s_city, s_state, s_country, s_zipcode FROM $sql_tbl[orders] WHERE orderid='".$order_data["order"]["orderid"]."'");

        $full_address_s = $address_s_from_db["s_address"] . "-". $address_s_from_db["s_city"] . "-". $address_s_from_db["s_state"] . "-". $address_s_from_db["s_country"] . "-". $address_s_from_db["s_zipcode"];
        $full_address_s = func_correct_field($full_address_s);

        $time_condition = $order_date - 60*60*24*180;
        $orders = func_query("SELECT orderid, orderid, order_prefix, phone, s_address, s_city, s_state, s_country, s_zipcode, b_address, b_city, b_state, b_country, b_zipcode FROM $sql_tbl[orders] WHERE date >= '$time_condition' AND date <= '$order_date'");

        if (!empty($orders) && is_array($orders)){

		$names = array();
		$full_address_names = array();

                foreach ($orders as $k => $v){

                        $tmp_full_address_s = $v["s_address"] . "-" . $v["s_city"] . "-". $v["s_state"] . "-". $v["s_country"] . "-". $v["s_zipcode"];
                        $tmp_full_address_s = func_correct_field($tmp_full_address_s);

                        if ($full_address_s == $tmp_full_address_s){

                                $tmp_full_address_b = $v["b_address"] . "-" . $v["b_city"] . "-". $v["b_state"] . "-". $v["b_country"] . "-". $v["b_zipcode"];
                                $tmp_full_address_b = func_correct_field($tmp_full_address_b);

				$names[$v["orderid"]] = $tmp_full_address_b;
				$full_address_names[$v["orderid"]] = $v;
                        }
                }

		$names = array_unique($names);

		$count_names = count($names);

		if ($count_names > 0){

                        foreach ($names as $k => $v){
                                $b_address_arr = explode("\n", $full_address_names[$k]["b_address"]);
                                $full_address_names[$k]["b_address1"] = trim($b_address_arr[0]);
                                $full_address_names[$k]["b_address2"] = trim($b_address_arr[1]);

                                $s_address_arr = explode("\n", $full_address_names[$k]["s_address"]);
                                $full_address_names[$k]["s_address1"] = trim($s_address_arr[0]);
                                $full_address_names[$k]["s_address2"] = trim($s_address_arr[1]);

                                $additional_info[] = $full_address_names[$k];
                        }

			$fraud_score = 1/$count_names;

	                if ($fraud_score == "1"){
                        	$fraud_result = "positive";
                	} elseif ($fraud_score < 1) {
        	                $fraud_result = "negative";
	                }
		}
        }

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;
	$fraud_score_arr["additional_info"] = $additional_info;

	return $fraud_score_arr;
}

function func_CHECK_TOTAL($order_data){

	$order_total_div = 50/$order_data["order"]["total"];

	if ($order_total_div >= 1){
		$fraud_result = "positive";
	} else {
		$fraud_result = "negative";
	}

	$num = 50 - $order_data["order"]["total"];

	if ($num == 0){
		$num = 1;
	}

	$sign = $num / abs($num);	

	$fraud_score = ((max("50", $order_data["order"]["total"])/min("50", $order_data["order"]["total"]))-1)*$sign;


/*
	if ($order_total_div < 1){
		$fraud_score = 1 - $order_total_div;
	} else {
		$fraud_score = $order_total_div;
	}

	if ($fraud_score >= "1"){
		$fraud_result = "positive";
	} else {
		$fraud_result = "negative";
	}
*/

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;

	return $fraud_score_arr;
}


function func_CHECK_SHIPPING_ADDRESS_LINE2($order_data){

	$fraud_score = "1";
	$fraud_result = "positive";

	if (!empty($order_data["userinfo"]["s_address_2"])){
		$fraud_score = "-1";
		$fraud_result = "negative";
	}

        $fraud_score_arr["fraud_result"] = $fraud_result;
	$fraud_score_arr["score"] = $fraud_score;

	return $fraud_score_arr;
}

function func_CHECK_PURCHASE_ORDER($order_data){

        $fraud_score = "-1";
        $fraud_result = "negative";

	if ($order_data["order"]["paymentid"] == "2"){
		$fraud_score = "1";
		$fraud_result = "positive";
	}

        $fraud_score_arr["fraud_result"] = $fraud_result;
        $fraud_score_arr["score"] = $fraud_score;

        return $fraud_score_arr;
}

