<?php

x_load('cart', 'mail', 'order', 'product', 'taxes');

function func_check_tracking_number($linkid, $tracknum)
{
    global $sql_tbl;

    $carrier_id = func_query_first_cell("SELECT carrier_id FROM $sql_tbl[tracking_links] WHERE linkid='$linkid'");
    $link       = func_query_first_cell("SELECT link FROM $sql_tbl[tracking_links_carrier] WHERE carrier_id='$carrier_id'");

    if (empty($link)) {
        return false;
    }

    if (preg_match('/(http|https).*/', $link) && empty($tracknum)) {
        return false;
    }

    return true;
}

function func_recalculate_accounting(&$group, $all_processors = [], $apply_per_trans = false, $refund = false)
{
    $oOrderGroup = new Xcart\OrderGroup(['orderid' => $group['orderid'], 'manufacturerid' => $group['manufacturerid']]);
    $oOrderGroup->recalculateAccounting();
}

function func_sort_taxes_priority($a, $b)
{
    if ($a['priority'] == $b['priority']) {
        return 0;
    }

    return ($a['priority'] < $b['priority']) ? 1 : -1;
}

function func_tax_price_details($price, $taxes, $de_tax = false)
{
    global $sql_tbl;

    $return = ['net' => $price, 'gross' => $price, 'pst' => 0, 'gst' => 0];

    if (empty($taxes)) {
        return $return;
    }

    $tax_array = [];

    if ($de_tax) {

        uasort($taxes, "func_sort_taxes_priority");

        foreach ($taxes as $k => $tax_rate) {
            $_price = $price;
            if ($tax_rate["rate_type"] == "%") {
                $price = $price * 100 / ($tax_rate["rate_value"] + 100);
            }
            else {
                $price -= $tax_rate["rate_value"];
            }
            $tax_array[$k] = $_price - $price;
        }

        $return['net'] = $price;
    }
    else {
        $tmp = func_tax_price($price, 0, false, null, '', $taxes);
        if (!empty($tmp['taxes'])) {
            foreach ($tmp['taxes'] as $tid => $v) {
                foreach ($taxes as $tname => $allv) {
                    if ($tid == $allv['taxid']) {
                        $tax_array[$tname] = $v;
                        break;
                    }
                }
            }
        }

        $return['net']   = $tmp['net_price'];
        $return['gross'] = $tmp['taxed_price'];
    }

    if (!empty($tax_array)) {
        foreach ($tax_array as $k => $v) {
            if ($k == 'GST' || $k == 'HST') {
                $return['gst'] += $v;
            }
            elseif ($k == 'PST') {
                $return['pst'] += $v;
            }
        }
    }

    return $return;
}

#
# This function calculates the product's quantity in stock to display it
# on the Edit products dialog
#
function func_oe_get_quantity_in_stock($productid, $cb_status, $dc_status, $options = [], $order_product = [])
{
    global $sql_tbl, $active_modules;

    $allowed_statuses = ["P", "Q", "I", "AP"];

    $quantity_in_stock = 0;

    if ( (in_array($cb_status, $allowed_statuses) || $dc_status == 'C' )
         && (empty($active_modules['Egoods']) && empty($order_product['distribution']))
    ) {
        $quantity_in_stock = $order_product['amount'];
    }


    if (!empty($active_modules['Product_Options']) && !empty($options))
    {
        $is_equal = false;

        if (!empty($order_product['product_options']) && is_array($order_product['product_options'])) {
            $order_options = [];
            foreach ($order_product['product_options'] as $cid => $o) {
                $order_options[$cid] = $o['optionid'];
            }
            $order_variantid = func_get_variantid($order_options);
            $variantid       = func_get_variantid($options);

            $is_equal = ($order_variantid == $variantid);
        }

        $quantity_in_stock += ($is_equal) ? func_query_first_cell("SELECT avail FROM $sql_tbl[variants] WHERE variantid='$variantid'") : 0;
    }
    else {
        $quantity_in_stock += func_query_first_cell("SELECT avail FROM $sql_tbl[products] WHERE productid='$productid'");
    }

    if (!empty($active_modules["RMA"])) {
        $quantity_in_stock -= (int)func_query_first_cell("SELECT SUM(returned_amount) FROM $sql_tbl[returns] WHERE itemid = '$order_product[itemid]'");
    }

    return $quantity_in_stock;
}

#
# This function validates the price that can be entered e.g. as $15.07
#
function func_oe_validate_price($price)
{
    return func_detect_price($price);
}

#
# This function updates products prices with VAT values
#
function func_oe_update_prices($products, $customer_info)
{
    global $config, $real_taxes;

    foreach ($products as $k => $v)
    {
        $products[$k]["price_deducted_tax"] = "Y";

        if ($real_taxes == "Y") {
            $_taxes = func_get_product_taxes($products[$k], $customer_info["login"], false);
        }
        else {
            $_taxes = func_get_product_taxes($products[$k], $customer_info["login"], false, $v["extra_data"]["taxes"]);
        }

        $products[$k]["extra_data"]["taxes"] = $products[$k]["taxes"] = $_taxes;
    }

    return $products;
}

#
# This function recalculate order totals
#
function func_oe_recalculate_totals($cart)
{
    global $active_modules, $real_taxes, $order_data, $config, $global_store;

    if ($real_taxes == "Y") {
        #
        # Calculate taxes etc depending on the current store settings
        #
        global $current_area, $login, $user_account;
        $_saved_data  = compact("current_area", "login", "user_account");
        $current_area = "C";
        $login        = $cart["userinfo"]["login"];
        $user_account = $cart["userinfo"];
    }

    $saved_state = false;
    if (!empty($active_modules["Special_Offers"])) {
        $saved_state = true;
        unset($active_modules["Special_Offers"]);
    }

    if ($cart['use_discount_alt'] == 'Y') {

        if (!defined('XAOM_WO_DISCOUNT_DATA') && !empty($cart['extra']['discount_info']) && !empty($cart['extra']['discount_info']['discount'])) {
            define('XAOM_WO_DISCOUNT_DATA', 1);
        }

        $global_store['discounts'] = [[
            "__override"    => true,
            "discountid"    => 999999999,
            "minprice"      => 0,
            "discount"      => ((!empty($cart['extra']['discount_info']) && !empty($cart['extra']['discount_info']['discount'])) ? $cart['extra']['discount_info']['discount'] : $cart['discount_alt']),
            "discount_type" => ((!empty($cart['extra']['discount_info']) && !empty($cart['extra']['discount_info']['discount_type'])) ? $cart['extra']['discount_info']['discount_type'] : "absolute"),
        ],
        ];
    }

    if (!empty($cart['use_coupon_discount_alt']) && $cart['use_coupon_discount_alt'] == 'Y') {
        $global_store['discount_coupons'] = [[
            "__override"  => true,
            "coupon"      => "Order#" . $cart['orderid'],
            "discount"    => $cart['coupon_discount_alt'],
            "coupon_type" => "absolute",
            "minimum"     => 0,
            "times"       => 999999999,
            "times_used"  => 0,
            "expire"      => time() + 30879000,
            "status"      => "A",
        ],
        ];
    }
    elseif (isset($cart['extra']['discount_coupon_info']) && !empty($cart['extra']['discount_coupon_info']) && $cart['extra']['discount_coupon_info']['coupon'] == $cart['coupon']) {
        $coupon_data                      = $cart['extra']['discount_coupon_info'];
        $coupon_data['__override']        = true;
        $global_store['discount_coupons'] = [$coupon_data];
    }

    $cart["products"] = func_oe_update_prices($cart["products"], $cart["userinfo"]);

    $cart = func_array_merge($cart, func_calculate($cart, $cart["products"], $cart["userinfo"]["login"], $cart["userinfo"]["usertype"], $cart["paymentid"]));

    $cart["total"] = $cart["total_cost"];

    $cart["applied_taxes"] = $cart["taxes"];

    if (is_array($cart["orders"])) {
        $cart["tax"]   = $cart["orders"][0]["tax_cost"];
        $cart["taxes"] = $cart["orders"][0]["taxes"];
    }

    #
    # Correct state, country and county full names (if its modified)
    #
    $uinfo = $cart["userinfo"];

    # Correct the billing address
    if ($uinfo["b_state"] . $uinfo["b_country"] . $uinfo["b_county"] != $order_data["userinfo"]["b_state"] . $order_data["userinfo"]["b_country"] . $order_data["userinfo"]["b_county"]) {
        $uinfo["b_statename"]   = $uinfo["b_state_text"] = func_get_state($uinfo["b_state"], $uinfo["b_country"]);
        $uinfo["b_countryname"] = $uinfo["b_country_text"] = func_get_country($uinfo["b_country"]);
        if ($config["General"]["use_counties"] == "Y") {
            $uinfo["b_countyname"] = $uinfo["b_county_text"] = func_get_county($uinfo["b_county"]);
        }
    }

    # Correct the shipping address
    if ($uinfo["s_state"] . $uinfo["s_country"] . $uinfo["s_county"] != $order_data["userinfo"]["s_state"] . $order_data["userinfo"]["s_country"] . $order_data["userinfo"]["s_county"]) {
        $uinfo["s_statename"]   = $uinfo["s_state_text"] = func_get_state($uinfo["s_state"], $uinfo["s_country"]);
        $uinfo["s_countryname"] = $uinfo["s_country_text"] = func_get_country($uinfo["s_country"]);
        if ($config["General"]["use_counties"] == "Y") {
            $uinfo["s_countyname"] = $uinfo["s_county_text"] = func_get_county($uinfo["s_county"]);
        }
    }

    $cart["userinfo"] = $uinfo;

    if ($saved_state) {
        $active_modules["Special_Offers"] = true;
    }

    if (!empty($_saved_data)) {
        extract($_saved_data);
    }

    return $cart;
}

#
# This function updates the order info in the database
#
function func_oe_update_order($cart, $shipping_groups, $old_products = "")
{
    global $sql_tbl, $config, $active_modules, $xcart_dir, $dhl_ext_country, $all_languages, $price_details_names, $user_account, $login;

    $cart = func_oe_recalculate_totals($cart);

    $userinfo  = $cart["userinfo"];
    $products  = $cart["products"];
    $giftcerts = $cart["giftcerts"];

    #
    # Update stock level
    #
    if (in_array($cart["status"], ["AP", "Q", "I", "P", "C"]) && $config["General"]["unlimited_products"] != "Y") {

        $_products = $_old_products = [];

        if (is_array($products)) {
            foreach ($products as $k => $product) {
                if (!empty($active_modules["Egoods"]) && !empty($product['distribution'])) {
                    continue;
                }

                if ($product["deleted"]) {
                    $product["amount"] = 0;
                }

                if ($product["stock_update"] == "Y") {
                    $amount_orig   = (is_array($old_products) && $old_products[$k]["amount"]) ? $old_products[$k]["amount"] : 0;
                    $amount_ret    = ($active_modules["RMA"] && $product["returned_to_stock"]) ? $product["returned_to_stock"] : 0;
                    $amount_change = $amount_orig - $product["amount"] - $amount_ret;

                    if ($amount_change) {
                        $product["amount"] = abs($amount_change);
                        if (@$user_account["flag"] != "FS") {
                            func_update_quantity([$product], $amount_change > 0);
                        }
                    }
                }
            }
        }
    }

    #
    # Prepare data
    #
    $_extra                                          = $cart["extra"];
    $_extra["tax_info"]["taxed_subtotal"]            = $cart["display_subtotal"];
    $_extra["tax_info"]["taxed_discounted_subtotal"] = $cart["display_discounted_subtotal"];
    $_extra["tax_info"]["taxed_shipping"]            = $cart["display_shipping_cost"];
    unset($_extra["tax_info"]["product_tax_name"]);

    $_extra['additional_fields'] = $userinfo['additional_fields'];

    $taxes_applied = serialize($cart["taxes"]);

    if (!empty($cart["use_shipping_cost_alt"])) {
        $cart["shipping_cost"] = $cart["shipping_cost_alt"];
    }

    $userinfo["b_address"] .= "\n" . $userinfo["b_address_2"];
    $userinfo["s_address"] .= "\n" . $userinfo["s_address_2"];

    $count_userinfo_additional_fields   = count($userinfo["additional_fields"]);
    $count_cart_extra_additional_fields = count($cart["extra"]["additional_fields"]);
    $count_additional_fields            = max($count_userinfo_additional_fields, $count_cart_extra_additional_fields);

    $log                          = "";
    $insert_additional_fields_log = false;

    for ($i = 0; $i < $count_additional_fields; $i++) {

        if ($userinfo["additional_fields"][$i]["value"] != $cart["extra"]["additional_fields"][$i]["value"]) {

            if (!empty($userinfo["additional_fields"][$i]["title"])) {
                $field_title = $userinfo["additional_fields"][$i]["title"];
            }
            else {
                $field_title = $cart["extra"]["additional_fields"][$i]["title"];
            }

            $log .= $field_title . ": " . $cart["extra"]["additional_fields"][$i]["value"] . " -> " . $userinfo["additional_fields"][$i]["value"] . "<br />";
            $insert_additional_fields_log = true;
        }
    }

    if ($insert_additional_fields_log) {
        func_log_order($cart["orderid"], 'X', $log, $login);
    }

    #
    # Update order info
    #
    $memberships = func_get_memberships('C', true);
    $query_data  = [
        "total"                                    => $cart['total'],
        "giftcert_discount"                        => $cart['giftcert_discount'],
        "giftcert_ids"                             => $cart['giftcert_ids'],
        "subtotal"                                 => $cart['subtotal'],
        "shipping_cost"                            => $cart['shipping_cost'],
        "shippingid"                               => $cart['shippingid'],
        "tax"                                      => $cart['tax'],
        "taxes_applied"                            => $taxes_applied,
        "discount"                                 => $cart['discount'],
        "coupon"                                   => ($cart["coupon"] ? ((preg_match("/(free_ship|percent|absolute)/S", $cart["coupon_type"])) ? ($cart["coupon_type"] . "``" . $cart["coupon"]) : $cart["coupon"]) : ""),
        "coupon_discount"                          => $cart['coupon_discount'],
        "payment_method"                           => $cart['payment_method'],
        "paymentid"                                => $cart["paymentid"],
        "payment_surcharge"                        => $cart["payment_surcharge"],
        'tax_info_display_taxed_order_totals'      => $_extra["tax_info"]['display_taxed_order_totals'],
        'tax_info_display_cart_products_tax_rates' => $_extra["tax_info"]['display_cart_products_tax_rates'],
        'tax_info_taxed_subtotal'                  => $_extra["tax_info"]['taxed_subtotal'],
        'tax_info_taxed_discounted_subtotal'       => $_extra["tax_info"]['taxed_discounted_subtotal'],
        'tax_info_taxed_shipping'                  => $_extra["tax_info"]['taxed_shipping'],

        "membership"   => !empty($memberships[$userinfo["membershipid"]]) ? $memberships[$userinfo["membershipid"]]['membership'] : '',
        "membershipid" => $userinfo["membershipid"],
        "title"        => $userinfo["title"],
        "firstname"    => $userinfo["firstname"],
        "lastname"     => $userinfo["lastname"],
        "company"      => $userinfo["company"],
        "tax_number"   => $userinfo["tax_number"],
        "tax_exempt"   => $userinfo["tax_exempt"],
        "b_title"      => $userinfo["b_title"],
        "b_firstname"  => $userinfo["b_firstname"],
        "b_lastname"   => $userinfo["b_lastname"],
        "b_address"    => $userinfo["b_address"],
        "b_city"       => $userinfo["b_city"],
        "b_county"     => @$userinfo["b_county"],
        "b_state"      => $userinfo["b_state"],
        "b_country"    => $userinfo["b_country"],
        "b_zipcode"    => $userinfo["b_zipcode"],
        "s_title"      => $userinfo["s_title"],
        "s_firstname"  => $userinfo["s_firstname"],
        "s_lastname"   => $userinfo["s_lastname"],
        "s_address"    => $userinfo["s_address"],
        "s_city"       => $userinfo["s_city"],
        "s_county"     => @$userinfo["s_county"],
        "s_state"      => $userinfo["s_state"],
        "s_country"    => $userinfo["s_country"],
        "s_zipcode"    => $userinfo["s_zipcode"],
        "phone"        => $userinfo["phone"],
        "phone_ext"    => $userinfo["phone_ext"],
        "fax"          => $userinfo["fax"],
        "email"        => $userinfo["email"],
        "url"          => $userinfo["url"],

    ];

    if (empty($extra['additional_fields'])) {
        $extra['additional_fields'] = $userinfo["additional_fields"];
    }
    if (!empty($extra['additional_fields'])) {
        foreach ($extra['additional_fields'] as $aAddFiled) {
            if ($aAddFiled['title'] == 'Company') {
                $sFiledCompany = strtolower($aAddFiled['section']) . '_company';
                if (!empty($sFiledCompany)) {
                    $query_data[$sFiledCompany] = $aAddFiled['value'];
                }
            }
        }
    }

    $query_data = func_array_map("addslashes", $query_data);

    if (@$user_account["flag"] != "FS") {

        $log = "";

        $log_name = ["membership", "title", "firstname", "lastname", "company", "tax_number", "tax_exempt", "b_title", "b_firstname", "b_lastname", "b_address", "b_city", "b_county", "b_state", "b_country", "b_zipcode", "s_title", "s_firstname", "s_lastname", "s_address", "s_city", "s_county", "s_state", "s_country", "s_zipcode", "phone", "phone_ext", "fax", "email", "url"];

        $insert_log = false;
        foreach ($log_name as $field_in_db) {
            $current = func_query_first_cell("SELECT $field_in_db  FROM $sql_tbl[orders] WHERE orderid='$cart[orderid]'");
            if ($current != $userinfo[$field_in_db]) {
                $log .= $field_in_db . ": " . $current . " -> " . $userinfo[$field_in_db] . "<br />";
                $insert_log = true;
            }
        }

        if ($insert_log) {
            func_log_order($cart["orderid"], 'X', $log, $login);
        }

        if (!empty($userinfo)) {
            $oCustomer   = Xcart\Customer::model(['login' => $userinfo['login']]);
            $arrNewValue = ['b_city'      => $userinfo['b_city'],
                            'b_firstname' => addslashes($userinfo['b_firstname']),
                            'b_address'   => addslashes($userinfo['b_address']),
                            'b_state'     => $userinfo['b_state'],
                            'b_country'   => $userinfo['b_country'],
                            'b_zipcode'   => $userinfo['b_zipcode'],
                            's_address'   => addslashes($userinfo['s_address']),
                            's_firstname' => addslashes($userinfo['s_firstname']),
                            's_city'      => $userinfo['s_city'],
                            's_state'     => $userinfo['s_state'],
                            's_country'   => $userinfo['s_country'],
                            's_zipcode'   => $userinfo['s_zipcode'],
            ];
            $oCustomer->updateFields($arrNewValue);
        }

        func_array2update("orders", $query_data, "orderid='$cart[orderid]'");
    }

    if (!empty($shipping_groups)) {
        foreach ($shipping_groups as $mid => $v) {
            $shipping_groups[$mid]['products'] = [];
        }
    }
    #
    # Update order details info
    #

    // Check for backordered, shipped, backordered/shipped statuses
    // -1 - initial value
    // 0 - no backordered products
    // 1 - some (not all) of the products are backordered
    // 2 - all products are backordered (back == amount for all products)
    $back_products = [];
    $do_refund     = [];

    if (is_array($products)) {
        $items         = [];
        $manufacturers = [];
        foreach ($products as $pk => $product) {
            if ($product["deleted"]) {
                continue;
            }

            if (!empty($active_modules['Product_Options'])) {

                $options = [];

                if (isset($product["keep_options"]) && $product["keep_options"] == "Y") {

                    # Keep original options choice
                    $options                    = $product["extra_data"]["product_options"];
                    $options_alt                = $product["extra_data"]["product_options_alt"];
                    $product["product_options"] = isset($options_alt[$config['default_admin_language']]) ? $options_alt[$config['default_admin_language']] : "";
                }
                else {

                    # Save selected options
                    if (is_array($product["product_options"])) {
                        foreach ($product["product_options"] as $k => $v) {
                            $options[intval($v["classid"])] = ($v['is_modifier'] == 'T') ? $v["option_name"] : $v["optionid"];
                        }
                    }

                    if ($all_languages && is_array($all_languages) && count($all_languages) > 1 && !empty($active_modules['Product_Options'])) {
                        foreach ($all_languages as $lng) {
                            $options_alt[$lng["code"]] = func_serialize_options($options, false, $lng["code"]);
                        }
                    }

                    $product["product_options"] = func_serialize_options($options);
                }
            }
            else {

                $product["product_options"] = "";
            }

            if ($shipping_groups[$product['manufacturerid']]["cb_status"] == "P") {

                if (empty($product['new'])) {

                    $amount = func_query_first_cell("SELECT amount FROM $sql_tbl[order_details] WHERE orderid='$cart[orderid]' AND itemid='$product[itemid]'");

                    if ($amount != "") {
                        $product['amount'] = $amount;
                    }
                }
            }

            $query_data = [];
            if (!empty($product['itemid'])) {
                $query_data = func_query_first("SELECT * FROM $sql_tbl[order_details] WHERE itemid='$product[itemid]'");
            }
            $query_data_tmp = [
                "itemid"          => $product['itemid'],
                "orderid"         => $cart['orderid'],
                "productid"       => $product['productid'],
                "product_options" => $product["product_options"],
                "amount"          => $product['amount'],
                'back'            => $product['back'],
                "price"           => $product['price'],
                "provider"        => $product["provider"],
                "extra_data"      => serialize($product["extra_data"]),
                "productcode"     => $product['productcode'],
                "product"         => $product['product'],
                "item_cost_to_us" => $product['cost_to_us'],
            ];
            if (floatval($query_data['item_cost_to_us']) != 0) {
                unset($query_data_tmp['item_cost_to_us']);
            }
            $query_data_tmp = func_array_map("addslashes", $query_data_tmp);
            $query_data     = array_merge($query_data, $query_data_tmp);

            if (@$user_account["flag"] != "FS") {

                $log = "";
                $log_name = ["amount", "back", "price"];

                $insert_log = false;
                foreach ($log_name as $field_in_db) {
                    $current = func_query_first_cell("SELECT $field_in_db FROM $sql_tbl[order_details] WHERE itemid='$product[itemid]'");
                    if ($current != $product[$field_in_db] && $current != "") {
                        $log .= "<B>" . $product['productcode'] . "</B>: " . $field_in_db . ": " . $current . " -> " . $product[$field_in_db] . "<br />";
                        $insert_log = true;
                    }
                }

                if ($insert_log) {
                    func_log_order($cart["orderid"], 'X', $log, $login);
                }

                $items[] = $products[$pk]['itemid'] = func_array2insert("order_details", $query_data, true);
            }

            if (!isset($back_products[$product['manufacturerid']])) {
                $back_products[$product['manufacturerid']] = -1;
            }

            if ($product['back'] < 1) {
                if ($back_products[$product['manufacturerid']] < 1) {
                    $back_products[$product['manufacturerid']] = 0;
                }
                else {
                    $back_products[$product['manufacturerid']] = 1;
                }
            }
            elseif ($product['back'] == $product['amount']) {
                if ($back_products[$product['manufacturerid']] == -1 || $back_products[$product['manufacturerid']] > 1) {
                    $back_products[$product['manufacturerid']] = 2;
                }
                else {
                    $back_products[$product['manufacturerid']] = 1;
                }
            }
            else {
                $back_products[$product['manufacturerid']] = 1;
            }

            $mid                                 = func_manufacturerid_for_group($product['shipping_freight'], $product['manufacturerid']);
            $manufacturers[$mid]                 = true;
            $shipping_groups[$mid]['products'][] = $product;

            if (isset($product['refund']) && is_array($product['refund'])) {
                func_refund_product($cart['orderid'], $mid, $product, $userinfo);
                $do_refund[$mid] = true;
                // Do not rewrite these changes with the posted values
                unset($_POST['ref_products'][$mid][$product['productid']]);
            }

            if (!isset($back_products[$product['manufacturerid']])) {
                $back_products[$product['manufacturerid']] = -1;
            }
        }
        if (@$user_account["flag"] != "FS") {
            db_query("DELETE FROM $sql_tbl[order_details] WHERE orderid='$cart[orderid]' AND itemid NOT IN ('" . implode("','", $items) . "')");
        }
    }

    if (!empty($shipping_groups))
    {
        # Reset order detailed totals
        $_extra['total'] = $_extra['product_total'] = $_extra['shipping_total'] = ['net' => 0, 'gst' => 0, 'pst' => 0, 'gross' => 0];

        $force_send_notification = false;

        $status_of_all_groups = func_query_column('SELECT type FROM ' . $sql_tbl['order_statuses']
                                                  . ' GROUP BY type');
        $status_of_all_groups = array_fill_keys($status_of_all_groups, '');

        $applied_per_trans_payments = [];
        $last_status_change         = '';

        foreach ($shipping_groups as $mid => $v) {

            $old_statuses = func_query_first('SELECT cb_status, dc_status, bd_status'
                                             . ' FROM ' . $sql_tbl['order_groups']
                                             . ' WHERE orderid = "' . $cart['orderid'] . '" AND manufacturerid = "' . $mid . '"');

            if (empty($v['tracking']) && $back_products[$mid] == 2) {
//        	        $v['dc_status'] = 'B';
//        	        $v['dc_status'] = 'M';
            }
            elseif (!empty($v['tracking']) && $back_products[$mid] >= 1) {
                $v['dc_status'] = 'G';
            }
            elseif (!empty($v['tracking']) && $back_products[$mid] == 0) {
                $v['dc_status'] = 'S';
            }

            foreach ($status_of_all_groups as $type => $sag) {
                if (empty($sag)) {
                    $status_of_all_groups[$type] = $v[strtolower($type) . '_status'];
                }
                elseif ($sag != $v[strtolower($type) . '_status']
                        && $sag != '-'
                ) {
                    $status_of_all_groups[$type] = '-';
                }
            }

            if ($v['dc_status'] == 'S' && defined('TRACKING_ADDED')) {
                $force_send_notification = true;
            }

            $query_data = [];
            $v['total'] = func_get_group_totals($v['products'], $v['shipping_cost']);
            if ($apply_per_trans = !in_array($v['acc_paymentid'], $applied_per_trans_payments)) {
                $applied_per_trans_payments[] = $v['acc_paymentid'];
            }

            $v['apply_per_trans']                     = $apply_per_trans;
            $shipping_groups[$mid]['apply_per_trans'] = $apply_per_trans;

            func_recalculate_accounting($v, [], $apply_per_trans);
            foreach ($v['total'] as $totk => $totv) {
                $_extra['total'][$totk] += $totv;
                $_extra['shipping_total'][$totk] += $v['shipping_cost'][$totk];
                $_extra['product_total'][$totk] += $totv - $v['shipping_cost'][$totk];
            }
            foreach ($price_details_names as $pn) {
                $query_data["total_$pn"]    = $v["total"][$pn];
                $query_data["shipping_$pn"] = $v["shipping_cost"][$pn];
            }

            $query_data = func_add_accounting_fields($query_data, $v);

            $query_data['profit_margin'] = $v['profit_margin'];

            if (!empty($v["new"]) && @$user_account["flag"] != "FS")
            {
                $query_data['orderid']                            = $cart['orderid'];
                $query_data['manufacturerid']                     = $mid;
                $status                                           = (empty($cart['status'])) ? 'Q' : $cart['status'];
                $status_type                                      = func_get_order_status_type($status);
                $query_data[strtolower($status_type) . '_status'] = $status;

                if (!empty($query_data["cb_status"]) && $query_data["cb_status"] == "P") {
                    $query_data["paid_date"] = time();
                }

                // Get manufacturer data
                $manufact_data = func_query_first('SELECT m_city, m_state, m_country FROM ' . $sql_tbl['manufacturers']
                                                  . ' WHERE manufacturerid = "' . $mid . '"');
                if (!empty($manufact_data) && is_array($manufact_data)) {
                    $query_data['manufacturer_data'] = serialize($manufact_data);
                }

                func_log_order_groups($query_data, $cart["orderid"], $mid, 'X', $login);

                func_array2insert('order_groups', $query_data);

                $last_status_change = $status;
            }
            else {
                if (@$user_account["flag"] != "FS") {
                    $query_data['shippingid'] = $v['shippingid'];

                    if (isset($v['shipping'])) {
                        $query_data['shipping'] = $v['shipping'];
                    }

                    if (isset($v['real_shipping_method'])) {
                        $query_data['real_shipping_method'] = $v['real_shipping_method'];
                    }
                }
                else {
                    $query_data = [];
                }

                foreach ($status_of_all_groups as $type => $sag) {
                    $status_column = strtolower($type) . '_status';
                    if (!empty($v[$status_column])) {
                        $query_data[$status_column] = $v[$status_column];
                    }
                    if (isset($old_statuses[$status_column]) && $old_statuses[$status_column] != $v[$status_column]) {
                        $last_status_change = $v[$status_column];
                    }
                }

                // Update D2C dispatched time
                if ($old_statuses['dc_status'] != 'C' && $v['dc_status'] == 'C' && empty($v['dc_dispatched_time'])) {
                    $query_data['dc_dispatched_time'] = time() - $config["Appearance"]["timezone_offset"];
                }

                $query_data['tracking'] = addslashes(serialize($v['tracking']));

                if (!empty($query_data["cb_status"]) && $query_data["cb_status"] == "P" && $old_statuses["cb_status"] != "P") {
                    $query_data["paid_date"] = time();
                }

                func_log_order_groups($query_data, $cart["orderid"], $mid, 'X', $login);

                func_array2update('order_groups', $query_data, "orderid='$cart[orderid]' AND manufacturerid='$mid'");
            }

            if ( (isset($do_refund[$mid]) && $do_refund[$mid] == true) || (isset($v['refund']) && !empty($v['refund'])) )
            {
                $v['manufacturerid'] = $mid;

                if (!isset($refund_group_status) || empty($refund_group_status)) {
                    $refund_group_status = func_add_refund_group($v);
                }
                else {
                    func_add_refund_group($v);
                }
                unset($_POST['ref_groups'][$mid]); // Do not rewrite these changes with the posted values
            }
        }

        if (!empty($cart["additional_fee"]) && is_array($cart["additional_fee"])) {
            foreach ($cart["additional_fee"] as $k => $v) {
                $_extra["total"]["net"] += price_format($v["additional_fee_value"]);
                $_extra["total"]["gross"] += price_format($v["additional_fee_value"]);
            }
        }

        if (!empty($last_status_change) || $force_send_notification) {

            if ($force_send_notification) {
                $last_status_change = 'S';
            }
            func_send_order_status_notification($cart['orderid'], $last_status_change);
        }

        $query_data = [];
        foreach ($price_details_names as $dn) {
            $query_data["shipping_total_$dn"] = $_extra['shipping_total'][$dn];
            $query_data["product_total_$dn"]  = $_extra['product_total'][$dn];
            $query_data["total_$dn"]          = $_extra['total'][$dn];
        }

        if (@$user_account["flag"] != "FS") {
            func_array2update("orders", $query_data, "orderid='$cart[orderid]'");
        }
    }

    if (isset($status_of_all_groups)
        && is_array($status_of_all_groups)
        && (empty($refund_group_status) || !isset($refund_group_status))
    ) {
        foreach ($status_of_all_groups as $type => $gas) {
            if (!empty($status_of_all_groups[$type]) && $status_of_all_groups[$type] != '-') {
                # All groups have same status - change it for the whole order
                define('SKIP_NOTIFICATION', true);
                func_change_order_status($cart['orderid'], $gas);
            }
        }
    }

    if (isset($_POST['ref_delete']) && is_array($_POST['ref_delete']))
    {
        foreach ($_POST['ref_delete'] as $mid => $m)
        {
            foreach ($m as $pid => $p)
            {
                if (isset($_POST['ref_products'][$mid][$pid])) {
                    unset($_POST['ref_products'][$mid][$pid]);
                }
                func_delete_refunded_product($pid, $mid, $cart['orderid']);
            }

            if (count($_POST['ref_products'][$mid]) == 0) {
                unset($_POST['ref_products'][$mid]);
                func_delete_refund_group($mid, $cart['orderid']);
            }
        }
    }

    if (isset($_POST['ref_products']))
    {
        if (!empty($_POST['ref_products']) && is_array($_POST['ref_products']) && !empty($items) && is_array($items))
        {
            foreach ($_POST['ref_products'] as $k_mid => $v_item_arr)
            {
                if (!empty($v_item_arr) && is_array($v_item_arr))
                {
                    foreach ($v_item_arr as $kk_itemid => $vv_arr)
                    {
                        $item_qty = trim($_POST["items"][$kk_itemid]["amount"]);
                        $item_qty = strtoupper($item_qty);

                        if (strpos($item_qty, 'R') !== false) {

                            $item_qty = str_replace('R', '', $item_qty);

                            $vv_ref_qty = $vv_arr["ref_qty"];

                            if ($item_qty != $vv_ref_qty) {

                                $_POST['ref_products'][$k_mid][$kk_itemid]["ref_qty"] = $item_qty + $vv_ref_qty;
                            }
                        }
                    }
                }
            }
        }

        func_update_refunded_products($_POST['ref_products'], $cart['orderid'], $userinfo);
    }

    if (isset($_POST['ref_groups'])) {

        $ref_notify_mode = true;

        if (!empty($_POST['ref_groups']) && is_array($_POST['ref_groups'])) {
            foreach ($_POST['ref_groups'] as $kp => $vp) {
                if ($vp["delete"] == "Y") {
                    $_POST['ref_groups'][$kp]["ref_ship"] = 0;
                }
            }
        }


        func_update_refunded_groups($_POST['ref_groups'], $cart['orderid'], true, $ref_notify_mode);
    }

    $orderid = $cart["orderid"];

    if (!empty($shipping_groups))
    {
        // After all refund data is calculated we need to update the ref_to_cust data
        foreach ($shipping_groups as $k => $v) {
            $v['manufacturerid'] = $k;
            func_recalculate_accounting($v, [], $v['apply_per_trans'], true);

            $query_data_a                  = func_add_accounting_fields($query_data_a, $v);
            $query_data_a["profit_margin"] = $v['profit_margin'];

            $where = 'orderid = "' . $v['orderid'] . '" AND manufacturerid = "' . $k . '"';

            func_array2update('order_groups', $query_data_a, $where);
        }
    }
}

#
# Analize the string and return the array of the refund values.
# $val  - string: [R|r]{amount}[,{fee}]
# $type - char: 'Q' - qty, 'S' - shipping cost
# return: array(is_refunded, amount, fee)
#

function func_get_refund_values($val, $type = '')
{

    if (empty($type)) {
        return false;
    }

    if ($type == 'S') {
        $pattern = '/[R|r]([0-9]+\.*[0-9]*)(,([0-9]*))?/';
    }
    else {
        $pattern = '/[R|r]([0-9]+)(,([0-9]*))?/';
    }

    if (preg_match($pattern, $val, $match)) {

        // max fee = 100%
        $match[3] = intval($match[3]);
        if ($match[3] > 100) {
            $match[3] = 100;
        }

        $result = [
            'is_refunded' => true,
            'amount'      => ($type == 'Q') ? intval($match[1]) : floatval($match[1]),
            'fee'         => ($type == 'Q') ? $match[3] : 0,
        ];

        return $result;
    }

    return false;
}

function func_adjust_refund_price($price, $fee)
{
    if (!empty($fee)) {
        $fee   = intval($fee);
        $price = abs($price * (1 - $fee / 100));
    }

    return $price;
}

function func_refund_product($orderid, $mid, &$product, $customer_info)
{
    global $sql_tbl, $active_modules;

    if ( !isset($product['refund']) || !$product['refund']['is_refunded'] ) {
        return;
    }

    $product['refund']['amount'] = intval($product['refund']['amount']);
    $product['refund']['price']  = func_convert_number($product['refund']['price']);

    if (!empty($product['refund']['amount'])) {

        $where = 'manufacturerid = "' . $mid . '" AND orderid = "' . $orderid . '"'
                 . ' AND productid = "' . $product['productid'] . '" AND itemid = "' . $product['itemid'] . '"';

        $ref_values = func_query_first('SELECT ref_qty, ref_price FROM ' . $sql_tbl['refunded_products'] . ' AS r'
                                       . ' WHERE ' . $where);

        if (!empty($ref_values)) {
            $product['refund']['amount'] += intval($ref_values['ref_qty']);
        }

        if ($product['amount'] < $product['refund']['amount']) {
            $product['refund']['amount'] = $product['amount'];
        }

        $login = func_query_first_cell('SELECT login FROM ' . $sql_tbl['orders']
                                       . ' WHERE orderid = ' . $orderid);

        x_load('taxes');

        $_product           = $product;
        $_product['amount'] = $product['refund']['amount'];
        $_product['price']  = $product['refund']['price'];

        $product['extra_data']['taxes'] = func_get_product_taxes($_product, $login, false, $product['taxes']);

        $_taxes = func_tax_price($product['refund']['price'], 0, false, null, $customer_info, $product['extra_data']['taxes']);

        $product['extra_data']['display_subtotal'] = price_format($_taxes['taxed_price'] * $product['refund']['amount']);
        $product['extra_data']['display']['price'] = price_format($_taxes['taxed_price']);
        $product['extra_data']['product']          = $product['product'];
        $product['extra_data']['productcode']      = $product['productcode'];
        $product['extra_data']['price']            = $product['price'];

        if (!empty($ref_values)) {
            $query_data = [
                'ref_price'  => $product['refund']['price'],
                'ref_qty'    => $product['refund']['amount'],
                'extra_data' => addslashes(serialize($product['extra_data'])),
            ];

            func_array2update('refunded_products', $query_data, $where);
        }
        else {

            $query_data = [
                'orderid'        => $orderid,
                'manufacturerid' => $mid,
                'productid'      => $product['productid'],
                'itemid'         => $product['itemid'],
                'provider'       => $product['provider'],
                'ref_price'      => $product['refund']['price'],
                'ref_qty'        => $product['refund']['amount'],
                'extra_data'     => addslashes(serialize($product['extra_data'])),
            ];

            func_array2insert('refunded_products', $query_data);
        }
    }
}

function func_add_refund_group($group)
{
    global $sql_tbl, $price_details_names;

    if (isset($group['orderid']) && isset($group['manufacturerid']))
    {
        $query_data = [
            'orderid'        => $group['orderid'],
            'manufacturerid' => $group['manufacturerid'],
            'ref_ship'       => $group['refund']['amount'],
        ];

        $ref_ship = func_query_first_cell('SELECT ref_ship FROM ' . $sql_tbl['refund_groups']
                                          . ' WHERE orderid = "' . $group['orderid'] . '" AND manufacturerid = "' . $group['manufacturerid'] . '"');

        if ($ref_ship !== false) {
            $query_data['ref_ship'] += floatval($ref_ship);
        }

        if ($query_data['ref_ship'] > $group['shipping_cost_net_orig']) {
            $query_data['ref_ship'] = $group['shipping_cost_net_orig'];
        }

        $group['ref_ship'] = $query_data['ref_ship'];

        $query_data = array_merge($query_data, func_manage_refund_group($group));

        unset($query_data['have_products']);

        if (isset($query_data['refund_status'])) {
            $return = $query_data['refund_status'];
            unset($query_data['refund_status']);
        }
        else {
            $return = false;
        }

        $query_data['extra_data']['apply_per_trans'] = $group['apply_per_trans'];
        $query_data['extra_data']                    = serialize($query_data['extra_data']);

        $query_data['shippingid'] = $group['shippingid'];
        $query_data['shipping']   = $group['shipping'];

        if ($ref_ship === false) {
            func_array2insert('refund_groups', $query_data);
        }
        else {
            $where = 'orderid="' . $query_data['orderid'] . '" AND manufacturerid="' . $query_data['manufacturerid'] . '"';
            func_array2update('refund_groups', $query_data, $where);
        }

        return $return;
    }

    return false;
}

function func_update_refunded_groups(&$groups, $orderid, $can_delete_group = false, $ref_notify_mode = false)
{
    global $sql_tbl, $login;

    $operator_login = $login;

    if (!empty($groups) && is_array($groups))
    {
        foreach ($groups as $mid => $group)
        {
            if ($group['ref_ship'] < 0) {
                $group['ref_ship'] = 0;
            }

            $where = 'orderid = "' . $orderid . '" AND manufacturerid = "' . $mid . '"';

            $query_data = func_query_first('SELECT * FROM ' . $sql_tbl['refund_groups'] . ' WHERE ' . $where);

            $max_ship = func_query_first_cell('SELECT shipping_net FROM ' . $sql_tbl['order_groups']
                                              . ' WHERE ' . $where);

            // order group exists
            if ($query_data && $max_ship !== false)
            {
                if ($group['ref_ship'] > $max_ship) {
                    $group['ref_ship'] = $max_ship;
                }

                $query_data['ref_ship']   = $group['ref_ship'];
                $query_data['extra_data'] = unserialize($query_data['extra_data']);
                $query_data['taxes']      = $query_data['extra_data']['taxes'];
                $query_data['tracking']   = unserialize($query_data['tracking']);
                $query_data['shipping_cost_net_orig'] = $max_ship;

                // Recalculate totals
                $query_data = array_merge($query_data, func_manage_refund_group($query_data, $ref_notify_mode));

                if ($can_delete_group && !$query_data['have_products'] && $group['ref_ship'] == 0) {
                    func_delete_refund_group($mid, $orderid, true);
                }
                else {
                    unset($query_data['have_products']);
                    unset($query_data['shipping_cost_net_orig']);
                    if (isset($query_data['refund_status'])) {
                        unset($query_data['refund_status']);
                    }

                    $lng_adj                = func_get_langvar_by_name('lbl_adjustment_to', null, false, true);
                    $query_data['shipping'] = str_replace($lng_adj . ' ', '', $group['shipping']);

                    $query_data['extra_data'] = serialize($query_data['extra_data']);
                    unset($query_data['taxes']);

                    func_log_order_refunded_groups($query_data, $orderid, $mid, 'X', $operator_login);

                    func_array2update('refund_groups', $query_data, $where);
                }
            }
        }
    }
}

function func_manage_refund_group(&$group, $ref_notify_mode = false)
{
    global $sql_tbl, $price_details_names, $customer_info, $order;

    $query_data = [];

    $fields = [
        'r.orderid', 'r.manufacturerid', 'r.productid', 'r.itemid', 'r.ref_price', 'r.ref_qty', 'r.provider', 'r.extra_data',
        'r.ref_price AS price', 'r.ref_qty AS amount',       // for funcs()
        'o.amount AS orig_amount, o.price AS orig_price',
    ];

    $products = func_query('SELECT ' . implode(', ', $fields) . ' FROM ' . $sql_tbl['refunded_products'] . ' AS r'
                           . ' LEFT JOIN ' . $sql_tbl['order_details'] . ' AS o ON r.productid = o.productid AND r.orderid = o.orderid AND r.itemid = o.itemid '
                           . ' WHERE r.orderid = "' . $group['orderid'] . '" AND r.manufacturerid = "' . $group['manufacturerid'] . '"');

    if (!empty($products))
    {
        if (is_array($products))
        {
            foreach ($products as $k => $product)
            {
                $products[$k]['extra_data']       = unserialize($product['extra_data']);
                $products[$k]['display_subtotal'] = $products[$k]['extra_data']['display_subtotal'];
            }
        }

        $taxes                             = func_calculate_taxes($products, $customer_info, $group['ref_ship']);
        $query_data['extra_data']['taxes'] = $taxes['taxes'];

        $group['shipping_cost'] = func_tax_price_details($group['ref_ship'], $group['taxes']);
        $group['total']         = func_get_group_totals($products, $group['shipping_cost']);

        $refund_status = func_define_refund_status($group);



        if (!empty($refund_status))
        {
            $query_data['refund_status'] = $refund_status;

            if ($refund_status == 'F')
            {
                if ($ref_notify_mode) {
                    if ($_POST["mode"] == "ref_notify") {
                        func_change_order_group_status($group['orderid'], $group['manufacturerid'], 'R');
                    }


                    elseif ($_POST["mode"] == "order_edit_apply" && !empty($group["total"]["gross"]) && $group["total"]["gross"] > 0 && $group["total"]["gross"] == $order["shipping_groups"][$group["manufacturerid"]]["total"]["gross"] && $order["shipping_groups"][$group["manufacturerid"]]["cb_status"] == "3") {
                        func_change_order_group_status($group['orderid'], $group['manufacturerid'], 'V');
                    }
                }
                else {
                    if ($_POST["mode"] != "ref_notify") {
                        func_change_order_group_status($group['orderid'], $group['manufacturerid'], 'V');
                    }
                }
            }
            elseif ($refund_status == 'P') {
                if ($ref_notify_mode) {
                    if ($_POST["mode"] == "ref_notify") {
                        func_change_order_group_status($group['orderid'], $group['manufacturerid'], 'H');
                    }
                    elseif ($_POST["mode"] == "order_edit_apply" && !empty($group["total"]["gross"]) && $group["total"]["gross"] > 0 && $group["total"]["gross"] == $order["shipping_groups"][$group["manufacturerid"]]["total"]["gross"] && $order["shipping_groups"][$group["manufacturerid"]]["cb_status"] == "3") {
                        func_change_order_group_status($group['orderid'], $group['manufacturerid'], 'V');
                    }
                }
                else {
                    if ($_POST["mode"] != "ref_notify") {

                        $set_statuse = "3";

                        if (!empty($group["total"]["gross"]) && $group["total"]["gross"] > 0 && $group["total"]["gross"] == $order["shipping_groups"][$group["manufacturerid"]]["total"]["gross"]) {
                            $set_statuse = "V";
                        }

                        func_change_order_group_status($group['orderid'], $group['manufacturerid'], $set_statuse);
                    }
                }
            }
        }

        func_recalculate_accounting($group, [], $group['apply_per_trans']);

        foreach ($price_details_names as $pn) {
            $query_data["total_$pn"]    = $group['total'][$pn];
            $query_data["shipping_$pn"] = $group['shipping_cost'][$pn];
        }

        $query_data = func_add_accounting_fields($query_data, $group);
        $query_data['tracking']      = addslashes(serialize($group['tracking']));
        $query_data['have_products'] = true; // flag
    }
    else {
        $query_data['shipping_net']   = $group['shipping_net'] = $group['ref_ship'];
        $query_data['shipping_gross'] = $group['shipping_gross'] = $group['ref_ship'];

        foreach ($price_details_names as $pn) {
            $query_data["total_$pn"] = $group["shipping_$pn"];
        }

        $group['total']['gross'] = $group['shipping_gross'];
        $refund_status           = func_define_refund_status($group);

        if (!empty($refund_status))
        {
            $query_data['refund_status'] = $refund_status;

            if ($refund_status == 'F')
            {
                if ($ref_notify_mode)
                {
                    if ($_POST["mode"] == "ref_notify") {
                        func_change_order_group_status($group['orderid'], $group['manufacturerid'], 'R');
                    }
                }
                else {
                    if ($_POST["mode"] != "ref_notify") {
                        func_change_order_group_status($group['orderid'], $group['manufacturerid'], 'V');
                    }
                }
            }
            elseif ($refund_status == 'P') {
                if ($ref_notify_mode) {
                    if ($_POST["mode"] == "ref_notify") {
                        func_change_order_group_status($group['orderid'], $group['manufacturerid'], 'H');
                    }
                }
                else {
                    if ($_POST["mode"] != "ref_notify") {

                        $set_statuse = "3";

                        if (!empty($refund_status["total"]["gross"]) && $refund_status["total"]["gross"] > 0 && $refund_status["total"]["gross"] == $order["shipping_groups"][$group["manufacturerid"]]["total"]["gross"]) {
                            $set_statuse = "V";
                        }

                        func_change_order_group_status($group['orderid'], $group['manufacturerid'], '3');
                    }
                }
            }
        }
    }

    return $query_data;
}

function func_update_refunded_products($products, $orderid)
{
    global $sql_tbl, $active_modules, $login;

    $operator_login = $login;

    if (is_array($products) && !empty($products))
    {
        foreach ($products as $mid => $mproducts)
        {
            if (is_array($mproducts))
            {
                foreach ($mproducts as $itemid => $product)
                {
                    $pid = $product["productid"];

                    if (intval($product['ref_qty']) == 0) {
                        func_delete_refunded_product($pid, $mid, $orderid);
                        continue;
                    }

                    $where = 'manufacturerid = "' . $mid . '" AND orderid = "' . $orderid . '"' . ' AND productid = "' . $pid . '" AND itemid = "' . $itemid . '"';

                    $query_data = func_query_first('SELECT * FROM ' . $sql_tbl['refunded_products'] . ' WHERE ' . $where);
                    $max_values = func_query_first('SELECT price, amount FROM ' . $sql_tbl['order_details'] . ' WHERE orderid = "' . $orderid . '" AND productid = "' . $pid . '" AND itemid = "' . $itemid . '"');

                    if (isset($product['ref_qty'])) {
                        $product['ref_qty'] = intval($product['ref_qty']);
                        if (isset($max_values['amount']) && $product['ref_qty'] > $max_values['amount']) {
                            $product['ref_qty'] = $max_values['amount'];
                        }
                    }
                    else {
                        $product['ref_qty'] = func_query_first_cell('SELECT ref_qty FROM ' . $sql_tbl['refunded_products']
                                                                    . ' WHERE ' . $where);
                    }

                    $product['ref_price'] = func_convert_number($product['ref_price']);

                    if (isset($max_values['price']) && $product['ref_price'] > $max_values['price']) {
                        $product['ref_price'] = $max_values['price'];
                    }

                    if ($query_data && !empty($product['ref_qty'])) {

                        $c_login = func_query_first_cell('SELECT login FROM ' . $sql_tbl['orders']
                                                         . ' WHERE orderid = ' . $orderid);

                        x_load('taxes');

                        $query_data['extra_data'] = unserialize($query_data['extra_data']);

                        $_product           = $query_data;
                        $_product['amount'] = $product['ref_qty'];
                        $_product['price']  = $product['ref_price'];

                        $query_data['extra_data']['taxes'] = func_get_product_taxes($_product, $c_login, false, $query_data['extra_data']['taxes']);

                        $_taxes = func_tax_price($product['ref_price'], 0, false, null, $customer_info, $query_data['extra_data']['taxes']);

                        if (empty($query_data['extra_data']['taxes'])) {
                            $query_data['extra_data']['display']['price'] = $product['ref_price'];
                        }

                        $query_data['extra_data']['display_subtotal'] = price_format($_taxes['taxed_price'] * $product['ref_qty']);

                        $query_data['ref_price']  = $product['ref_price'];
                        $query_data['ref_qty']    = $product['ref_qty'];
                        $query_data['extra_data'] = serialize($query_data['extra_data']);

                        $log      = "";
                        $log_name = ["ref_price", "ref_qty"];

                        $insert_log = false;
                        foreach ($log_name as $field_in_db)
                        {
                            $current = func_query_first_cell("SELECT $field_in_db FROM $sql_tbl[refunded_products] WHERE $where");
                            if ($current != $query_data[$field_in_db]) {
                                $product_code = func_query_first_cell("SELECT productcode FROM $sql_tbl[products] WHERE productid='$pid'");
                                $log .= "<B>" . $product_code . ":</B> " . $field_in_db . ": " . $current . " -> " . $query_data[$field_in_db] . "<br />";
                                $insert_log = true;
                            }
                        }

                        if ($insert_log) {
                            func_log_order($orderid, 'X', $log, $operator_login);
                        }

                        func_array2update('refunded_products', $query_data, $where);
                    }
                }
            }
        }
    }
}

function func_delete_refunded_product($pid, $mid, $orderid)
{
    global $sql_tbl;

    db_query('DELETE FROM ' . $sql_tbl['refunded_products'] . ' WHERE itemid = "' . $pid . '" AND manufacturerid = "' . $mid . '" AND orderid = "' . $orderid . '"');
}

function func_delete_refund_group($mid, $orderid, $full = false)
{
    global $sql_tbl;

    db_query('DELETE FROM ' . $sql_tbl['refunded_products'] . ' WHERE orderid = "' . $orderid . '" AND manufacturerid = "' . $mid . '"');

    if ($full)
    {
        db_query('DELETE FROM ' . $sql_tbl['refund_groups'] . ' WHERE orderid = "' . $orderid . '" AND manufacturerid = "' . $mid . '"');

        $current_cb_status = func_query_first_cell("SELECT cb_status FROM $sql_tbl[order_groups] WHERE orderid = '$orderid' AND manufacturerid='$mid'");

        if ($current_cb_status != "AP")
        {
            $current_cb_status_value = func_query_first_cell("SELECT name FROM $sql_tbl[order_statuses] WHERE code='$current_cb_status'");
            $code                    = func_query_first_cell("SELECT code FROM $sql_tbl[manufacturers] WHERE manufacturerid='$mid'");

            if ($current_cb_status != "P")
            {
                $new_value = func_query_first_cell("SELECT name FROM $sql_tbl[order_statuses] WHERE code='P'");
                $log       = "<B>" . $code . ":</B> cb_status: " . $current_cb_status_value . " -> " . $new_value;
                global $login;
                func_log_order($orderid, 'X', $log, $login);
            }

            db_query("UPDATE $sql_tbl[order_groups] SET cb_status='P' WHERE orderid='$orderid' AND manufacturerid='$mid'");
        }
    }
    else {
        $data   = func_query_first("SELECT shipping, ref_ship FROM $sql_tbl[refund_groups] WHERE orderid='$orderid' AND manufacturerid='$mid'");
        $groups = [$mid => $data];
        func_update_refunded_groups($groups, $orderid);
    }
}

function func_calculate_gross_ref_to_cust($orderid, $mid, $paymentid)
{
    global $sql_tbl;

    if (is_numeric($paymentid) && !empty($paymentid))
    {
        $ref_values = func_query_first('SELECT percent_ref, per_ref FROM ' . $sql_tbl['payment_methods']
                                       . ' WHERE paymentid = "' . intval($paymentid) . '"');

        if ($ref_values)
        {
            $refund_sum_products = func_query_first_cell('SELECT total_gross FROM ' . $sql_tbl['refund_groups']
                                                         . ' WHERE orderid = "' . $orderid . '" AND manufacturerid = "' . $mid . '"');

            $refund_sum_products = floatval($refund_sum_products);
            $gross_to_cust = (1 - $ref_values['percent_ref'] / 100) * $refund_sum_products + $ref_values['per_ref'];

            return $gross_to_cust;
        }
    }

    return 0;
}

function func_define_refund_status(&$group)
{
    global $sql_tbl;

    // Gather refund info
    // F - fully refunded
    // P - partitially refunded
    // '' - not refunded

    $refund_status = '';

    if (isset($group['manufacturerid']) && $group["cb_status"] != "AP")
    {
        if (!empty($group['total']['gross']))
        {
            $order_group_gross = func_query_first_cell('SELECT total_gross FROM ' . $sql_tbl['order_groups'] . ' WHERE orderid = "' . $group['orderid'] . '" AND manufacturerid = "' . $group['manufacturerid'] . '"');
            $order_group_gross = round(floatval($order_group_gross), 2);

            if (round($group['total']['gross'], 2) >= $order_group_gross) {
                $refund_status = 'F';
            }
            else {
                $refund_status = 'P';
            }
        }
    }

    return $refund_status;
}
