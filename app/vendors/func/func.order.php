<?php

use Modules\Order\Models\OrderGroupInvoiceProductModel;
use Modules\User\Helpers\SurfingHelper;
use Modules\User\Models\SurfMetaModel;
use Modules\User\Models\SurfPathModel;

x_load('cart', 'crypt', 'mail', 'user', 'product');

function func_get_shipping_groups($orderid)
{
    global $sql_tbl, $price_details_names;

    $return = func_query_hash("SELECT og.*, m.manufacturer as group_name, m.code, s.shipping AS shippingid_name, m.submit_to_operator FROM $sql_tbl[order_groups] AS og LEFT JOIN $sql_tbl[manufacturers] AS m ON m.manufacturerid=og.manufacturerid LEFT JOIN $sql_tbl[shipping] AS s ON s.shippingid=og.shippingid WHERE og.orderid='$orderid'", "manufacturerid", false);

    $all_productid = func_query("SELECT productid FROM $sql_tbl[order_details] WHERE orderid='$orderid'");

    if (!empty($all_productid) && is_array($all_productid)) {

        $all_manufacturerid = [];
        foreach ($all_productid as $k => $v) {
            $manufacturerid = func_query_first_cell("SELECT manufacturerid FROM $sql_tbl[products] WHERE productid=$v[productid]");

            if (!in_array($manufacturerid, $all_manufacturerid)) {
                $all_manufacturerid[] = $manufacturerid;
            }
        }

        $all_manufacturerid_found = [];
        if (!empty($return) && is_array($return)) {
            foreach ($return as $m_id => $group) {
                $all_manufacturerid_found[] = $m_id;
            }
        }

        $count_all_manufacturerid       = count($all_manufacturerid);
        $cound_all_manufacturerid_found = count($all_manufacturerid_found);

        if ($count_all_manufacturerid != $cound_all_manufacturerid_found && !empty($all_manufacturerid)) {
            foreach ($all_manufacturerid as $k => $m_id) {
                if (!in_array($m_id, $all_manufacturerid_found)) {
                    db_query("INSERT INTO $sql_tbl[order_groups] (orderid, manufacturerid) VALUES ('$orderid', '$m_id')");
                }
            }

            $return = func_query_hash("SELECT og.*, m.manufacturer as group_name, m.code, s.shipping AS shippingid_name, m.submit_to_operator FROM $sql_tbl[order_groups] AS og LEFT JOIN $sql_tbl[manufacturers] AS m ON m.manufacturerid=og.manufacturerid LEFT JOIN $sql_tbl[shipping] AS s ON s.shippingid=og.shippingid WHERE og.orderid='$orderid'", "manufacturerid", false);
        }
    }

    if (!empty($return)) {
        foreach ($return as $m_id => $group)
        {
            $return[$m_id]["tracking"] = unserialize($group["tracking"]);

            global $xcart_dir;
            $return[$m_id]['oOrderGroup'] = new \Xcart\OrderGroup(['manufacturerid' => $m_id, 'orderid' => $orderid]);

            if (!empty($return[$m_id]["tracking"]) && is_array($return[$m_id]["tracking"])) {
                foreach ($return[$m_id]["tracking"] as $kt => $vt) {
                    if (!empty($vt["ship_date"]) && strpos($vt["ship_date"], "/") !== false) {
                        $current_ship_date_arr                       = explode("/", $vt["ship_date"]);
                        $current_ship_date_time                      = mktime(0, 0, 0, $current_ship_date_arr[0], $current_ship_date_arr[1], $current_ship_date_arr[2]);
                        $return[$m_id]["tracking"][$kt]["ship_date"] = date("F j, Y", $current_ship_date_time);
                    }
                }
            }

            $return[$m_id]["total"]              = $return[$m_id]["shipping_cost"] = $return[$m_id]["actual_shipping_cost"] = [];
            $return[$m_id]["extra"]              = unserialize($group["extra"]);
            $return[$m_id]["accounting"]         = func_make_accounting('', '', $group);
            $return[$m_id]['accounting']         = func_set_filled_option($return[$m_id]['accounting']);
            $return[$m_id]['acc_payment_method'] = func_query_first_cell('SELECT payment_method'
                                                                         . ' FROM ' . $sql_tbl['payment_methods']
                                                                         . ' WHERE paymentid = "' . $group['acc_paymentid'] . '"');

            if (!empty($group['manufacturer_data'])) {
                $return[$m_id]['manufacturer_data'] = unserialize($group['manufacturer_data']);

                if (
                    isset($return[$m_id]['manufacturer_data']['m_state'])
                    && !empty($return[$m_id]['manufacturer_data']['m_state'])
                    && isset($return[$m_id]['manufacturer_data']['m_country'])
                    && !empty($return[$m_id]['manufacturer_data']['m_country'])
                ) {
                    $return[$m_id]['manufacturer_data']['m_state']   = func_get_state($return[$m_id]['manufacturer_data']['m_state'], $return[$m_id]['manufacturer_data']['m_country']);
                    $return[$m_id]['manufacturer_data']['m_country'] = func_get_country($return[$m_id]['manufacturer_data']['m_country']);
                }
            }

            $return[$m_id]["products"] = [];
            foreach ($price_details_names as $pn)
            {
                $return[$m_id]["total"][$pn]                = $group["total_$pn"];
                $return[$m_id]["shipping_cost"][$pn]        = $group["shipping_$pn"];
                $return[$m_id]["actual_shipping_cost"][$pn] = $group["actual_shipping_$pn"];
                unset($return[$m_id]["total_$pn"], $return[$m_id]["shipping_$pn"]);
            }

            $invoices = func_query_hash("SELECT * FROM $sql_tbl[order_group_invoices] WHERE orderid='$orderid' AND manufacturerid='$m_id'", "invoice_number", false);
            if (empty($invoices)) {
                $invoices = "";
            }
            else {
                foreach ($invoices as $k_i => $v_i) {
                    $i_products                 = func_query_hash("SELECT * FROM $sql_tbl[order_group_invoices_products] WHERE orderid='$orderid' AND manufacturerid='$m_id' AND invoice_number='$k_i'", "itemid", false);
                    $invoices[$k_i]["products"] = $i_products;
                    $invoices[$k_i]["invoice_details"] = OrderGroupInvoiceProductModel::objects()->filter(['orderid' => $orderid, 'manufacturerid' => $m_id, 'invoice_number' => $k_i])->all();
                }
            }
            $return[$m_id]["invoices"] = $invoices;

            $memos = func_query_hash("SELECT * FROM $sql_tbl[order_group_memos] WHERE orderid='$orderid' AND manufacturerid='$m_id'", "memo_number", false);
            if (empty($memos)) {
                $memos = "";
            }

            $return[$m_id]["memos"] = $memos;
        }
    }

    return $return;
}

function func_get_group_totals($group_products, $group_shipping)
{
    global $price_details_names;

    $result = [];

    foreach ($price_details_names as $dn) {
        $result[$dn] = 0;
    }

    foreach ($group_products as $product) {
        $result['net'] += $product['price'] * $product['amount'];
        if (!empty($product['extra_data']['taxes']['GST']['tax_value'])) {
            $result['gst'] += price_format($product['extra_data']['taxes']['GST']['tax_value']);
        }
        if (!empty($product['extra_data']['taxes']['HST']['tax_value'])) {
            $result['gst'] += price_format($product['extra_data']['taxes']['HST']['tax_value']);
        } # GST/HST sum
        if (!empty($product['extra_data']['taxes']['PST']['tax_value'])) {
            $result['pst'] += price_format($product['extra_data']['taxes']['PST']['tax_value']);
        }
        $result['gross'] += $product['display_subtotal'];
    }

    foreach ($price_details_names as $dn) {
        $result[$dn] += $group_shipping[$dn];
    }

    return $result;
}

#
# This function creates array with order data
#
function func_select_order($orderid)
{
    global $sql_tbl, $config, $current_area, $active_modules, $shop_language, $price_details_names;

    $o_date = "date+'" . $config["Appearance"]["timezone_offset"] . "' as date";
    $order  = func_query_first("select *, $o_date from $sql_tbl[orders] where $sql_tbl[orders].orderid='$orderid'");

    if (empty($order)) {
        return false;
    }

    $order['order_status'] = [
        'CB' => $order['cb_status'],
        'DC' => $order['dc_status'],
        'BD' => $order['bd_status'],
        'VP' => $order['vn_status'],
    ];

    $order['attention_tags'] = func_query("SELECT $sql_tbl[orders_additional_tags].status_id, $sql_tbl[attention_tags_values].status, $sql_tbl[attention_tags_values].description FROM $sql_tbl[orders_additional_tags] LEFT JOIN $sql_tbl[attention_tags_values] ON $sql_tbl[attention_tags_values].status_id=$sql_tbl[orders_additional_tags].status_id WHERE $sql_tbl[orders_additional_tags].orderid='$orderid'");

    $product_question_status_code          = func_query_first_cell("SELECT status FROM $sql_tbl[product_question] WHERE id='$order[product_question_status_id]'");
    $order['product_question_status_code'] = $product_question_status_code;

    $order['titleid']   = func_detect_title($order['title']);
    $order['b_titleid'] = func_detect_title($order['b_title']);
    $order['s_titleid'] = func_detect_title($order['s_title']);

    if ($current_area == 'C')
    {
        $order['title']   = func_get_title($order['titleid']);
        $order['b_title'] = func_get_title($order['b_titleid']);
        $order['s_title'] = func_get_title($order['s_titleid']);
        $tmp              = func_get_languages_alt("payment_method_" . $order['paymentid'], $shop_language);

        if (!empty($tmp))
        {
            $order['payment_method_orig'] = $order['payment_method'];
            $order['payment_method']      = $tmp;
        }
    }

    $order["discounted_subtotal"] = $order["subtotal"] - $order["discount"] - $order["coupon_discount"];

    if ($order["giftcert_ids"])
    {
        $order["applied_giftcerts"] = preg_split("/\*/", $order["giftcert_ids"]);

        if ($order["applied_giftcerts"]) {
            $tmp = [];

            foreach ($order["applied_giftcerts"] as $k => $v)
            {
                if (empty($v)) {
                    continue;
                }

                list($arr["giftcert_id"], $arr["giftcert_cost"]) = explode(":", $v);
                $tmp[] = $arr;
            }

            $order["applied_giftcerts"] = $tmp;
        }
    }

    $old_groups                     = $order["shipping_groups"];
    $order["shipping_groups"]       = func_get_shipping_groups($orderid);
    $order['empty_shipping_groups'] = true;
    foreach ($order["shipping_groups"] as $group) {
        if (!empty($group['tracking'])) {
            $order['empty_shipping_groups'] = false;
            break;
        }
    }

    if (empty($order["shipping_groups"]) && !empty($old_groups))
    {
        # Old style groups
        $order['tracking'] = unserialize($order['tracking']);

        $shipping = func_query_first("select shipping from $sql_tbl[shipping] where shippingid='" . $order["shippingid"] . "'");

        $order["shipping"]    = $shipping["shipping"];
        $order["shippingids"] = unserialize($order["shippingid"]);
        if (!empty($order["shippingids"])) {
            foreach ($order["shippingids"] as $m_id => $s_id) {
                if (is_array($s_id) && array_key_exists('shippingid', $s_id)) # convert old format to new
                {
                    $s_id = $s_id['shippingid'];
                }
                $shippings[$m_id] = func_query_first_cell("select shipping from $sql_tbl[shipping] where shippingid='$s_id'");
            }
        }
        $order['shipping'] = $shippings;
        $order["shipping_groups"] = unserialize($old_groups);
        if (!empty($order["shipping_groups"])) {
            foreach ($order["shipping_groups"] as $m_id => $group) {
                $order["shipping_groups"][$m_id]['products'] = [];
                $order["shipping_groups"][$m_id]['shipping'] = $order['shipping'][$m_id];
                $order["shipping_groups"][$m_id]['code'] = func_query_first_cell("SELECT code FROM $sql_tbl[manufacturers] WHERE manufacturerid='$m_id'");
            }
        }
    }
    unset($old_groups);

    $order_details_crypt_type = func_get_crypt_type($order["details"]);
    if ($order_details_crypt_type != 'C' || func_get_crypt_key("C") !== false) {
        $order["details"] = text_decrypt($order["details"]);
        if (is_null($order["details"])) {
            $order["details"]           = func_get_langvar_by_name("err_data_corrupted");
            $order['details_corrupted'] = true;
            x_log_flag("log_decrypt_errors", "DECRYPT", "Could not decrypt order details for the order " . $orderid, true);
        }
        else {
            $order["details"] = stripslashes($order["details"]);
        }
    }
    else {
        $order["details"]           = func_get_langvar_by_name("txt_this_data_encrypted");
        $order['details_encrypted'] = true;
    }

    $order["notes"] = stripslashes($order["notes"]);

    $order["extra"] = @unserialize($order["extra"]);

    if (!empty($order["b_company"]) || !empty($order["s_company"])) {
        $order["extra"]                        = ["additional_fields" => []];
        $order["extra"]["additional_fields"][] = ['fieldid' => 1, 'section' => 'B', 'value' => $order['b_company'], 'title' => 'Company'];
        $order["extra"]["additional_fields"][] = ['fieldid' => 2, 'section' => 'S', 'value' => $order['s_company'], 'title' => 'Company'];
    }
    if (!empty($order['tax_info_display_taxed_order_totals'])) {
        $order["extra"]['tax_info']['display_taxed_order_totals'] = $order['tax_info_display_taxed_order_totals'];
    }
    if (!empty($order['tax_info_display_cart_products_tax_rates'])) {
        $order["extra"]['tax_info']['display_cart_products_tax_rates'] = $order['tax_info_display_cart_products_tax_rates'];
    }

    if (floatval($order['tax_info_taxed_subtotal']) > 0) {
        $order["extra"]['tax_info']['taxed_subtotal'] = $order['tax_info_taxed_subtotal'];
    }
    if (floatval($order['tax_info_taxed_discounted_subtotal']) > 0) {
        $order["extra"]['tax_info']['taxed_discounted_subtotal'] = $order['tax_info_taxed_discounted_subtotal'];
    }
    if (floatval($order['tax_info_taxed_shipping']) > 0) {
        $order["extra"]['tax_info']['taxed_shipping'] = $order['tax_info_taxed_shipping'];
    }

    foreach ($price_details_names as $pn) {
        if (floatval($order['shipping_total_' . $pn]) > 0) {
            $order["extra"]['shipping_total'][$pn] = $order['shipping_total_' . $pn];
        }
        if (floatval($order['product_total_' . $pn]) > 0) {
            $order["extra"]['product_total'][$pn] = $order['product_total_' . $pn];
        }
        if (floatval($order['total_' . $pn]) > 0) {
            $order["extra"]['total'][$pn] = $order['total_' . $pn];
        }
    }

    $extras = func_query("SELECT khash, value FROM $sql_tbl[order_extras] WHERE orderid = '$orderid'");
    if (!empty($extras)) {
        foreach ($extras as $v) {
            $order["extra"][$v["khash"]] = $v["value"];
        }
    }

    if ($current_area != "C" && !empty($active_modules["Stop_List"])) {
        if (func_ip_exist_slist($order["extra"]["ip"])) {
            $order["blocked"] = "Y";
        }
    }

    if ($order["taxes_applied"]) {
        $order["applied_taxes"] = unserialize($order["taxes_applied"]);
    }

    if (preg_match("/NetBanx Reference: ([\w\d]+)/iSs", $order["details"], $preg)) {
        $order['netbanx_reference'] = $preg[1];
    }

    #
    # Assign the display_* vars for displaying in the invoice
    #
    if (@$order["extra"]["tax_info"]["display_taxed_order_totals"] == "Y" && !empty($order["extra"]["tax_info"]["taxed_subtotal"])) {
        $order["display_subtotal"] = $order["extra"]["tax_info"]["taxed_subtotal"];
    }
    else {
        $order["display_subtotal"] = $order["subtotal"];
    }

    if (@$order["extra"]["tax_info"]["display_taxed_order_totals"] == "Y" && !empty($order["extra"]["tax_info"]["taxed_discounted_subtotal"])) {
        $order["display_discounted_subtotal"] = $order["extra"]["tax_info"]["taxed_discounted_subtotal"];
    }
    else {
        $order["display_discounted_subtotal"] = $order["discounted_subtotal"];
    }

    if (@$order["extra"]["tax_info"]["display_taxed_order_totals"] == "Y" && !empty($order["extra"]["tax_info"]["taxed_shipping"])) {
        $order["display_shipping_cost"] = $order["extra"]["tax_info"]["taxed_shipping"];
    }
    else {
        $order["display_shipping_cost"] = $order["shipping_cost"];
    }

    $order["shipping_costs"] = unserialize($order["shipping_costs"]);

    list($order["b_address"], $order["b_address_2"]) = explode("\n", $order["b_address"]);
    $order["b_statename"]   = func_get_state($order["b_state"], $order["b_country"]);
    $order["b_countryname"] = func_get_country($order["b_country"]);
    list($order["s_address"], $order["s_address_2"]) = explode("\n", $order["s_address"]);
    $order["s_statename"]   = func_get_state($order["s_state"], $order["s_country"]);
    $order["s_countryname"] = func_get_country($order["s_country"]);

    if ($config["General"]["use_counties"] == "Y") {
        $order["b_countyname"] = func_get_county($order["b_county"]);
        $order["s_countyname"] = func_get_county($order["s_county"]);
    }

    if ($order["paymentid"] == 2) {
        # Get PO data from order details text
        $tmp = explode("\n", $order["details"]);


        if ($tmp) {
            $po_fields = [
                "po_number" => "PO Number",
                "company_name" => "Company name",
                "name_of_purchaser" => "Name of purchaser",
                "position" => "Position",
                "po_fax" => "po fax",
                "accounts_payable_full_name" => "accounts payable full name",
                "accounts_payable_phone" => "accounts payable phone",
                "accounts_payable_fax" => "accounts payable fax",
                "accounts_payable_email" => "accounts payable email",
                "purchase_manager_phone" => "purchase manager phone",
                "purchase_manager_email" => "purchase manager email",
                "purchase_manager_phone_ext" => "purchase manager phone ext",
                "accounts_payable_phone_ext" => "accounts payable phone ext"
            ];

            $order["po_details"] = [];

            foreach ($tmp as $line)
            {
                if (empty($po_fields)) {
                    break;
                }
                foreach ($po_fields as $k => $po_text) {
                    if (($a = strpos($line, $po_text . ":")) !== false) {
                        $value                   = substr($line, $a + strlen($po_text) + 2);
                        $order["po_details"][$k] = $value;
                        unset($po_fields[$k]);
                        break;
                    }
                }
            }
        }
    }
    return $order;
}

#
# This function returns data about specified order ($orderid)
#
function func_order_data($orderid)
{
    global $sql_tbl, $config, $smarty, $active_modules, $current_area, $xcart_dir;
    global $xcart_catalogs, $shop_language;

    $join        = "";
    $gc_add_date = ", add_date+'" . $config["Appearance"]["timezone_offset"] . "' as add_date";
    $fields      = $gc_add_date;

    if (!empty($active_modules["Egoods"])) {
        $join .= " LEFT JOIN $sql_tbl[download_keys] ON $sql_tbl[order_details].itemid=$sql_tbl[download_keys].itemid AND $sql_tbl[download_keys].productid=$sql_tbl[order_details].productid";
        $fields .= ", $sql_tbl[download_keys].download_key, $sql_tbl[download_keys].expires";
    }

    if (!empty($active_modules['Multiple_Storefronts'])) {
        $fields .= ', c.storefrontid';
        $join .= ' LEFT JOIN ' . $sql_tbl['products_categories'] . ' as pc ON pc.productid='
                 . $sql_tbl['products'] . '.productid AND pc.main = "Y"'
                 . ' LEFT JOIN ' . $sql_tbl['categories'] . ' as c ON c.categoryid = pc.categoryid';
    }

    $products = func_query("SELECT $sql_tbl[order_details].itemid, $sql_tbl[products].*, $sql_tbl[order_details].*, IF($sql_tbl[products].productid IS NULL, 'Y', '') as is_deleted, IF($sql_tbl[order_details].product = '', $sql_tbl[products].product, $sql_tbl[order_details].product) as product $fields FROM $sql_tbl[order_details] LEFT JOIN $sql_tbl[products] ON $sql_tbl[order_details].productid = $sql_tbl[products].productid $join WHERE $sql_tbl[order_details].orderid='$orderid'");

    if (!is_array($products)) {
        $products = [];
    }

    #
    # If products are not present in products table, but they are present in
    # order_details, then create fake $products from order_details data
    #
    $is_returns = false;
    if (!empty($products) && !empty($active_modules['RMA'])) {
        foreach ($products as $k => $v) {
            $products[$k]['returns'] = func_query("SELECT * FROM $sql_tbl[returns] WHERE itemid = '$v[itemid]'");
            if (!empty($products[$k]['returns'])) {
                $is_returns = true;
                foreach (['A', 'R', 'C'] as $s) {
                    $products[$k]['returns_sum_' . $s] = func_query_first_cell("SELECT SUM(amount) FROM $sql_tbl[returns] WHERE itemid = '$v[itemid]' AND status = '$s'");
                }
            }
        }
    }

    $giftcerts = func_query("SELECT * $gc_add_date FROM $sql_tbl[giftcerts] WHERE orderid = '$orderid'");
    if (!empty($giftcerts) && $config["General"]["use_counties"] == "Y") {
        foreach ($giftcerts as $k => $v) {
            if (!empty($v['recipient_county'])) {
                $giftcerts[$k]['recipient_countyname'] = func_get_county($v['recipient_county']);
            }
        }
    }

    $order = func_select_order($orderid);
    if (!$order) {
        return false;
    }

    $order['is_returns'] = $is_returns;

    if ($current_area == "A" || ($current_area == "P" && !empty($active_modules['Simple_Mode']))) {
        if (strpos($order['details'], "{CardNumber}:") !== false && file_exists($xcart_dir . "/payment/cmpi.php")) {
            $order['is_cc_payment'] = "Y";
        }
    }

    if (func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[order_details], $sql_tbl[download_keys] WHERE $sql_tbl[order_details].orderid = '$orderid' AND $sql_tbl[order_details].itemid = $sql_tbl[download_keys].itemid")) {
        $order['is_egood'] = 'Y';
    }
    elseif (func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[order_details], $sql_tbl[products] WHERE $sql_tbl[order_details].orderid = '$orderid' AND $sql_tbl[order_details].productid = $sql_tbl[products].productid AND $sql_tbl[products].distribution != ''")) {
        $order['is_egood'] = 'E';
    }

    $userinfo = func_query_first("SELECT *, date+'" . $config["Appearance"]["timezone_offset"] . "' as date FROM $sql_tbl[orders] WHERE orderid = '$orderid'");
    if (isset($order["extra"]['additional_fields'])) {
        $userinfo['additional_fields'] = $order["extra"]['additional_fields'];
    }

    $userinfo['titleid']   = func_detect_title($userinfo['title']);
    $userinfo['b_titleid'] = func_detect_title($userinfo['b_title']);
    $userinfo['s_titleid'] = func_detect_title($userinfo['s_title']);
    if ($current_area == 'C') {
        $userinfo['title']   = func_get_title($userinfo['titleid']);
        $userinfo['b_title'] = func_get_title($userinfo['b_titleid']);
        $userinfo['s_title'] = func_get_title($userinfo['s_titleid']);
    }

    $userinfo = func_array_merge(func_userinfo($userinfo["login"], "C", false, false, ["C", "H"]), $userinfo);

    list($userinfo["b_address"], $userinfo["b_address_2"]) = preg_split("/[\n\r]+/", $userinfo["b_address"]);
    list($userinfo["s_address"], $userinfo["s_address_2"]) = preg_split("/[\n\r]+/", $userinfo["s_address"]);

    $userinfo["s_countryname"] = $userinfo["s_country_text"] = func_get_country($userinfo["s_country"]);
    $userinfo["s_statename"]   = $userinfo["s_state_text"] = func_get_state($userinfo["s_state"], $userinfo["s_country"]);
    $userinfo["b_statename"]   = func_get_state($userinfo["b_state"], $userinfo["b_country"]);
    $userinfo["b_countryname"] = func_get_country($userinfo["b_country"]);
    if ($config["General"]["use_counties"] == "Y") {
        $userinfo["b_countyname"] = func_get_county($userinfo["b_county"]);
        $userinfo["s_countyname"] = func_get_county($userinfo["s_county"]);
    }

    $phone_normalized = preg_replace("/[^0-9]/S", "", $userinfo["phone"]);
    if (strlen($phone_normalized) == "10") {
        $userinfo["phone_normalized"] = "+1" . $phone_normalized;
    }

    $request_availability_options = func_query("SELECT * FROM $sql_tbl[request_availability_options]");

    $tmp_cur_time_sec = time();
    $est_time_offset  = func_query_first_cell("SELECT est_time_offset FROM $sql_tbl[states] WHERE code='$userinfo[s_state]' AND country_code='$userinfo[s_country]'");
    $est_time_offset  = $est_time_offset * 60 * 60;
    $tmp_cur_time_sec -= $est_time_offset;
    $userinfo["customer_time"] = $tmp_cur_time_sec;
    $tmp_cur_time_date_format  = date("G.i", $tmp_cur_time_sec);
    $tmp_date_mm_dd_yyyy       = date("m/d/Y", $tmp_cur_time_sec);
    // $tmp_cur_time_sec += 2*24*60*60; // for checking
    $tmp_number_of_day_of_week = date("w", $tmp_cur_time_sec); // 0 (for Sunday) through 6 (for Saturday)
    // func_print_r($tmp_number_of_day_of_week, $tmp_cur_time_date_format); // for checking

    if ($tmp_cur_time_date_format >= "8.30" && $tmp_cur_time_date_format <= "16.30" && ($tmp_number_of_day_of_week != "0" && $tmp_number_of_day_of_week != "6")) {
        if (!empty($request_availability_options) && is_array($request_availability_options)) {
            foreach ($request_availability_options as $k_r => $v_r) {
                if ($v_r["date_mm_dd_yyyy"] == $tmp_date_mm_dd_yyyy && $v_r["active"] == "Y") {
                    $good_time_to_send_email_to_customer = "N";
                }
            }
        }

        if ($good_time_to_send_email_to_customer != "N") {
            $good_time_to_send_email_to_customer = "Y";
        }

        $userinfo["good_time_to_send_email_to_customer"] = $good_time_to_send_email_to_customer;
    }
    else {
        $userinfo["good_time_to_send_email_to_customer"] = "N";
    }

    if (!$products) {
        $products = [];
    }

    if (preg_match("/(free_ship|percent|absolute)(?:``)(.+)/S", $order["coupon"], $found)) {
        $order["coupon"]      = $found[2];
        $order["coupon_type"] = $found[1];
    }

    $order["extra"]["tax_info"]["product_tax_name"] = "";
    $_product_taxes                                 = [];


    foreach ($products as $k => $v)
    {
        $products[$k]["brand"]      = $v["brand"] = func_query_first_cell("SELECT brand FROM $sql_tbl[brands] WHERE brandid='$v[brandid]'");
        $category_info              = func_query_first("SELECT $sql_tbl[products_categories].categoryid, $sql_tbl[categories].category FROM $sql_tbl[products_categories]  LEFT JOIN $sql_tbl[categories] ON $sql_tbl[products_categories].categoryid=$sql_tbl[categories].categoryid WHERE $sql_tbl[products_categories].productid = '$v[productid]' and $sql_tbl[products_categories].main = 'Y' LIMIT 1");
        $products[$k]["categoryid"] = $v["categoryid"] = $category_info["categoryid"];
        $products[$k]["category"]   = $v["category"] = $category_info["category"];

        if (!empty($active_modules['Extra_Fields']) && $v['is_deleted'] != 'Y') {
            $v['extra_fields'] = func_query("SELECT $sql_tbl[extra_fields].*, $sql_tbl[extra_field_values].*, IF($sql_tbl[extra_fields_lng].field != '', $sql_tbl[extra_fields_lng].field, $sql_tbl[extra_fields].field) as field FROM $sql_tbl[extra_field_values], $sql_tbl[extra_fields] LEFT JOIN $sql_tbl[extra_fields_lng] ON $sql_tbl[extra_fields].fieldid = $sql_tbl[extra_fields_lng].fieldid AND $sql_tbl[extra_fields_lng].code = '$shop_language' WHERE $sql_tbl[extra_fields].fieldid = $sql_tbl[extra_field_values].fieldid AND $sql_tbl[extra_field_values].productid = '$v[productid]' AND $sql_tbl[extra_fields].active = 'Y' ORDER BY $sql_tbl[extra_fields].orderby");
        }

        if (!isset($v['storefrontid'])) {
            $v['storefrontid'] = 0;
        }

        $v['links'] = func_get_product_link_sf($v['productid'], $v['storefrontid']);

        $v['product_options_txt'] = $v['product_options'];
        if ($v["extra_data"]) {
            $v["extra_data"] = unserialize($v["extra_data"]);
            if (is_array(@$v["extra_data"]["display"])) {
                foreach ($v["extra_data"]["display"] as $i => $j) {
                    $v["display_" . $i] = $j;
                }
            }
            if (is_array($v["extra_data"]["taxes"])) {
                foreach ($v["extra_data"]["taxes"] as $i => $j) {
                    if ($j["tax_value"] > 0) {
                        $_product_taxes[$i] = $j["tax_display_name"];
                    }
                }
            }
        }

        $v["original_price"]     = $v["ordered_price"] = $v["price"];
        $v["price_deducted_tax"] = "Y";

        #
        # Get the original price (current price in the database)
        #
        if ($v['is_deleted'] != 'Y') {
            $v["original_price"] = func_query_first_cell("SELECT MIN($sql_tbl[pricing].price) FROM $sql_tbl[pricing] WHERE $sql_tbl[pricing].productid = '$v[productid]' AND $sql_tbl[pricing].membershipid IN ('$userinfo[membershipid]', 0) AND $sql_tbl[pricing].quantity <= '$v[amount]' AND $sql_tbl[pricing].variantid = 0");

            if (!empty($active_modules['Product_Options']) && isset($v['extra_data']['product_options'])) {
                list($variant, $product_options) = func_get_product_options_data($v['productid'], $v['extra_data']['product_options'], $userinfo['membershipid']);

                if ($product_options === false) {
                    unset($product_options);
                }
                else {
                    if (empty($variant['price'])) {
                        $variant['price'] = $v["original_price"];
                    }

                    $v["original_price"] = $variant['price'];
                    unset($variant['price']);
                    if ($product_options) {
                        foreach ($product_options as $o) {
                            if ($o['modifier_type'] == '%') {
                                $v["original_price"] += $v["original_price"] * $o['price_modifier'] / 100;
                            }
                            else {
                                $v["original_price"] += $o['price_modifier'];
                            }
                        }
                    }

                    $v['product_options'] = $product_options;

                    # Check current and saved product options set
                    if (!empty($v['product_options_txt'])) {
                        $flag_txt = true;

                        # Check saved product options
                        $count = 0;
                        foreach ($v['product_options'] as $opt) {
                            if (preg_match("/" . preg_quote($opt['class'], "/") . ": " . preg_quote($opt['option_name'], "/") . "/Sm", $v['product_options_txt'])) {
                                $count++;
                            }
                        }
                        if ($count != count($v['product_options'])) {
                            $flag_txt = false;
                        }

                        # Check current product options set
                        if ($flag_txt) {
                            $count = 0;
                            $tmp   = explode("\n", $v['product_options_txt']);
                            foreach ($tmp as $txt_row) {
                                if (!preg_match("/^([^:]+): (.*)$/S", trim($txt_row), $match)) {
                                    continue;
                                }

                                foreach ($v['product_options'] as $opt) {
                                    if ($match[1] == $opt['class'] && $match[2] == trim($opt['option_name'])) {
                                        $count++;
                                        break;
                                    }
                                }
                            }

                            if ($count != count($tmp)) {
                                $flag_txt = false;
                            }
                        }

                        # Force display saved product options set
                        # if saved and current product options sets wasn't equal
                        if (!$flag_txt) {
                            $v['force_product_options_txt'] = true;
                        }
                    }

                    if (!empty($variant)) {
                        $v = func_array_merge($v, $variant);
                    }
                }
            }
        }

        global $xcart_dir;
        $classProduct      = \Xcart\Product::model(['productid' => $v['productid']]);
        $mpn               = $classProduct->getMPN();
        $v["mpn"]          = $mpn;
        $v["oProduct"]     = $classProduct;
        $v["oOrderDetail"] = \Xcart\OrderDetail::model(['itemid' => $v['itemid']]);

        $v["orig_product_classes"] = func_get_product_classes($v["productid"]);

        if (!empty($v["eta_date_mm_dd_yyyy"])
            && $config["backorder_decision_request"]["do_not_offer_backorder_if_eta_more_than_days"] != ""
            && $config["backorder_decision_request"]["do_not_offer_backorder_if_eta_more_than_days"] > 0
            && empty($v["offer_backorder"])
        )
        {
            $diff_time = $v["eta_date_mm_dd_yyyy"] - time();

            if ($diff_time > 0) {

                $diff_time = $diff_time / (60 * 60 * 24);

                if ($diff_time < $config["backorder_decision_request"]["do_not_offer_backorder_if_eta_more_than_days"]) {
                    $v["offer_backorder"] = "Y";
                }
            }
        }


        $products[$k] = $v;

        $m_id = func_manufacturerid_for_group($v['shipping_freight'], $v['manufacturerid']);
        $order['shipping_groups'][$m_id]['products'][$v["itemid"]] = $v;
        $order['shipping_groups'][$m_id]['oOrderGroup']            = \Xcart\OrderGroup::model(['orderid' => $orderid, 'manufacturerid' => $m_id]);

        if (!empty($v['extra_data']['taxes']))
        {
            $order['shipping_groups'][$m_id]['taxes'] = $v['extra_data']['taxes'];
        }

        $manufacturer_feed_fields = func_query_hash("SELECT $sql_tbl[manufacturer_feed_fields].* FROM $sql_tbl[manufacturer_feed_fields] WHERE $sql_tbl[manufacturer_feed_fields].manufacturerid='$v[manufacturerid]'", "field_name", false);

        if (!empty($manufacturer_feed_fields) && is_array($manufacturer_feed_fields)) {
            foreach ($manufacturer_feed_fields as $km => $vm) {
                if (($vm["locked"] == 'Y' && $vm["admin_lock"] == 'Y') || ($vm["locked"] == 'N' && $vm["admin_lock"] == 'Y')) {
                    $manufacturer_feed_fields[$km]["disable"] = "Y";
                }
                else {
                    $manufacturer_feed_fields[$km]["disable"] = "N";
                }
            }
            $products[$k]["manufacturer_feed_fields"] = $manufacturer_feed_fields;
        }
    }

    if (!empty($order['shipping_groups']) && is_array($order['shipping_groups']))
    {
        foreach ($order['shipping_groups'] as $m_id => $v)
        {
            $order['shipping_groups'][$m_id]["all_distributor_info"]       = func_query_first("SELECT * FROM $sql_tbl[manufacturers] WHERE manufacturerid='$m_id'");
            $order['shipping_groups'][$m_id]["amazon_fulfillment_channel"] = $order["amazon_fulfillment_channel"];

            if (!empty($v["invoices"]) && is_array($v["invoices"])) {

                foreach ($v["invoices"] as $invoice_number => $invoice_info) {
                    if (!empty($invoice_info["reconciliation_id"])) {
                        $order['shipping_groups'][$m_id]["invoices"][$invoice_number]["full_reconciliation_info"]                   = func_query_first("SELECT * FROM $sql_tbl[reconciliations] WHERE id='$invoice_info[reconciliation_id]'");
                        $order['shipping_groups'][$m_id]["invoices"][$invoice_number]["full_reconciliation_info"]["amount_csv_abs"] = abs($order['shipping_groups'][$m_id]["invoices"][$invoice_number]["full_reconciliation_info"]["amount_csv"]);
                    }
                }
            }


            if (!empty($v["memos"]) && is_array($v["memos"])) {
                foreach ($v["memos"] as $memo_number => $memo_info) {
                    if (!empty($memo_info["reconciliation_id"])) {
                        $order['shipping_groups'][$m_id]["memos"][$memo_number]["full_reconciliation_info"]                   = func_query_first("SELECT * FROM $sql_tbl[reconciliations] WHERE id='$memo_info[reconciliation_id]'");
                        $order['shipping_groups'][$m_id]["memos"][$memo_number]["full_reconciliation_info"]["amount_csv_abs"] = abs($order['shipping_groups'][$m_id]["memos"][$memo_number]["full_reconciliation_info"]["amount_csv"]);
                    }
                }
            }

            if (empty($order['shipping_groups'][$m_id]["products"]) || !is_array($order['shipping_groups'][$m_id]["products"])) {
                $order['shipping_groups'][$m_id]["empty_products_list"] = "Y";
            }
        }
    }

    $products = func_translate_products($products, $shop_language);

    if (count($_product_taxes) == 1) {
        $order["extra"]["tax_info"]["product_tax_name"] = array_pop($_product_taxes);
    }

    if ($order["coupon_type"] == "free_ship") {
        $order["shipping_cost"] = $order["coupon_discount"];
        $order["discounted_subtotal"] += $order["coupon_discount"];
    }

    $order["discounted_subtotal"] = price_format($order["discounted_subtotal"]);

    if (!empty($active_modules['Google_Checkout'])) {
        include $xcart_dir . "/modules/Google_Checkout/get_order_data.php";
    }

    if (empty($order['shipping_groups']))
    {
        $__products = [];
        foreach ($products as $prd) {
            $__products[] = $prd['productid'];
        }
        $order['shipping_groups'] = [['products' => $__products]];
    }

    $order['has_backordered_status'] = func_has_backordered_status($order['shipping_groups']);
    $order['refund_groups']          = func_get_refund_groups($order['orderid'], $order["storefrontid"]);

    $oOrderStatus                              = \Xcart\OrderStatus::model(['code' => $order['order_status']['VP']]);
    $order['product_verification_status']      = $oOrderStatus->getField('name');
    $order['product_verification_status_code'] = $oOrderStatus->getField('code');
    $order['product_verification_statuses']    = \Xcart\OrderStatus::model()->findAll(\Xcart\SQLBuilder::getInstance()->addCondition("type='PV'"));

    $order["additional_fee"] = func_query("SELECT id, additional_fee_name, additional_fee_value FROM $sql_tbl[order_additional_fee] WHERE orderid='$order[orderid]' ORDER BY additional_fee_name");

    if (!empty($order["alt_items"]))
    {
        global $storefronts;
        $all_storefronts                       = $storefronts;
        $all_storefronts[0]["storefrontid"]    = 0;
        $all_storefronts[0]["domain"]          = "www.artistsupplysource.com";
        $all_storefronts[0]["storefront_name"] = func_query_first_cell("SELECT value FROM $sql_tbl[config] WHERE name='company_name'");

        $alt_items_arr = explode(",", $order["alt_items"]);

        $alt_products = [];
        foreach ($alt_items_arr as $k => $sku)
        {

            if ((strpos($sku, ":")) !== false) {
                $orderby_productcode = explode(":", $sku);
                $orderby             = trim($orderby_productcode[0]);
                $sku                 = $orderby_productcode[1];
            }
            else {
                $orderby = 0;
            }

            $productcode = trim($sku);
            $alt_product = func_query_first("SELECT $sql_tbl[products].productid, $sql_tbl[products].product, $sql_tbl[products_sf].sfid, $sql_tbl[pricing].price FROM $sql_tbl[products] LEFT JOIN $sql_tbl[pricing] ON $sql_tbl[pricing].productid = $sql_tbl[products].productid LEFT JOIN $sql_tbl[products_sf] ON $sql_tbl[products_sf].productid = $sql_tbl[products].productid INNER JOIN $sql_tbl[quick_prices] ON $sql_tbl[quick_prices].productid = $sql_tbl[products].productid AND $sql_tbl[quick_prices].membershipid = '0' WHERE $sql_tbl[products].productcode='$productcode' AND $sql_tbl[pricing].priceid = $sql_tbl[quick_prices].priceid AND $sql_tbl[products_sf].sfid='$order[storefrontid]' GROUP BY $sql_tbl[products].productid");

            if (!empty($alt_product)) {
                $alt_products[$k]                = $alt_product;
                $alt_products[$k]["orderby"]     = $orderby;
                $alt_products[$k]["productcode"] = $productcode;
                $alt_products[$k]["url"]         = "http://" . $all_storefronts[$alt_product["sfid"]]["domain"] . "/" . func_clean_url_get("P", $alt_product["productid"], false);
            }
        }

        usort($alt_products, 'func_sort_arr_by_orderby');

        $order["alt_products"] = $alt_products;
    }

    $order['oOrder'] = \Xcart\Order::model(['orderid' => $orderid]);

    return [
        "order"     => $order,
        "oOrder"    => $order['oOrder'],
        "products"  => $products,
        "userinfo"  => $userinfo,
        "giftcerts" => $giftcerts,
    ];
}

#
# This function increments product rating
#
function func_increment_rating($productid)
{
    global $sql_tbl;

    db_query("UPDATE $sql_tbl[products] SET rating=rating+1 WHERE productid='$productid'");
}

#
# Decrease number of products in stock and increase product rating
#
function func_decrease_quantity($products)
{
    if (!empty($products) && is_array($products)) {
        foreach ($products as $product) {
            func_increment_rating($product["productid"]);
        }
    }

    func_update_quantity($products, false);
}

#
# This function creates order entry in orders table
#
function func_place_order($payment_method, $order_status, $order_details, $customer_notes, $extra = [], $extras = [])
{
    global $cart, $userinfo, $discount_coupon, $mail_smarty, $config, $active_modules, $single_mode, $partner, $adv_campaignid, $partner_clickid;
    global $sql_tbl, $to_customer, $statuses;
    global $wlid, $_COOKIE;
    global $xcart_dir, $REMOTE_ADDR, $PROXY_IP, $CLIENT_IP, $add_to_cart_time;
    global $arb_account_used, $arb_account, $dhl_ext_country_store, $current_storefront, $active_modules;
    global $price_details_names;
    global $XCART_SESSION_NAME;
    global $$XCART_SESSION_NAME;

    global $is_mobile_checkout;

    if (empty($is_mobile_checkout)) {
        $is_mobile_checkout = "N";
    }

    $mes = "PLACE ORDER\n";
    $mes .= "STEP 1 " . date("H:i:s") . "\n";

    $mintime = 10;
    #
    # Lock place order process
    #
    func_lock("place_order");

    $mes .= "STEP 2 " . date("H:i:s") . "\n";

    $userinfo['title']   = func_get_title($userinfo['titleid'], $config['default_admin_language']);
    $userinfo['b_title'] = func_get_title($userinfo['b_titleid'], $config['default_admin_language']);
    $userinfo['s_title'] = func_get_title($userinfo['s_titleid'], $config['default_admin_language']);

    $check_order = func_query_first("SELECT orderid FROM $sql_tbl[orders] WHERE login='" . addslashes($userinfo["login"]) . "' AND '" . time() . "'-date<'$mintime'");
    if ($check_order) {
        func_unlock("place_order");

        return false;
    }

    $mes .= "STEP 3 " . date("H:i:s") . "\n";

    if (!in_array($order_status, ['I', 'Q', 'O', 'IO'])) {
        func_unlock("place_order");

        return false;
    }

    $userinfo["email"] = addslashes($userinfo["email"]);

    $orderids = [];

    #
    # REMOTE_ADDR and PROXY_IP
    #
    $extras['ip'] = $CLIENT_IP;

    $ip_info = $CLIENT_IP;

    $geo_litecity_location = func_get_geoip_locations($CLIENT_IP);
    if (!empty($geo_litecity_location)) {
        $ip_info .= " (" . $geo_litecity_location["country"] . ", " . $geo_litecity_location["region"] . ", " . $geo_litecity_location["city"] . ", " . $geo_litecity_location["postalCode"] . ")";
    }

    $extras['ip_info'] = $ip_info;
    $extras['proxy_ip'] = $PROXY_IP;

    if (!empty($cart['shipping_warning'])) {
        $extras['shipping_warning'] = $cart['shipping_warning'];
    }

    if ($add_to_cart_time > 0) {
        $extras['add_to_cart_time'] = time() - $add_to_cart_time;
    }

    if (!empty($_COOKIE['personal_client_id'])) {
        $extras['personal_client_id'] = $_COOKIE['personal_client_id'];
    }

    $mes .= "STEP 4 " . date("H:i:s") . "\n";

    #
    # Validate cart contents
    #
    if (!func_cart_is_valid($cart, $userinfo)) {
        # current state of cart is not valid and we cannot
        # re-calculate it now
        func_unlock("place_order");

        return false;
    }

    $mes .= "STEP 5 " . date("H:i:s") . "\n";

    $products = $cart['products'];

    $giftcert_discount = $cart["giftcert_discount"];
    if ($cart["applied_giftcerts"]) {
        foreach ($cart["applied_giftcerts"] as $k => $v) {
            $giftcert_str = join("*", [@$giftcert_str, "$v[giftcert_id]:$v[giftcert_cost]"]);
            db_query("UPDATE $sql_tbl[giftcerts] SET status='U' WHERE gcid='$v[giftcert_id]'");
        }
    }

    $mes .= "STEP 6 " . date("H:i:s") . "\n";

    $giftcert_id = @$cart["giftcert_id"];

    $extra = "";
    if (!empty($active_modules["Anti_Fraud"]) && defined("IS_AF_CHECK") && ($cart['total_cost'] > 0 || $config['Anti_Fraud']['check_zero_order'] == 'Y')) {
        include $xcart_dir . "/modules/Anti_Fraud/anti_fraud.php";
    }

    $mes .= "STEP 7 " . date("H:i:s") . "\n";

    #
    # Store Airborne account information into $order_details
    #
    x_session_register("arb_account_used");
    x_session_register("arb_account");
    if ($arb_account_used) {
        $_code = func_query_first_cell("SELECT code FROM $sql_tbl[shipping] WHERE shippingid='$cart[shippingid]'");
        if ($_code == "ARB") {
            $order_details = func_get_langvar_by_name("lbl_arb_account") . ": " . $arb_account . "\n" . $order_details;
        }
    }
    $extra['additional_fields'] = $userinfo['additional_fields'];

    if (!empty($dhl_ext_country_store) && !empty($cart['shippingid'])) {
        $is_dhl_shipping = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[shipping] WHERE shippingid = '$cart[shippingid]' AND code = 'ARB' AND destination = 'I'") > 0;
        if ($is_dhl_shipping) {
            $extra['dhl_ext_country'] = $dhl_ext_country_store;
        }
    }

    $mes .= "STEP 8 " . date("H:i:s") . "\n";

    $mes .= "STEP 9 " . date("H:i:s") . "\n";

    foreach ($cart["orders"] as $current_order)
    {
        $shipping_cost = 0;
        foreach ($current_order['display_shipping_costs'] as $s_cost) {
            $shipping_cost += $s_cost;
        }
        // Send order to Mailchimp if module enabled and mailchimp_analytics = Y
        global $mailchimp_campaignid;
        if (
            !empty($active_modules['Mailchimp_Subscription'])
            && $config['Mailchimp_Subscription']['mailchimp_analytics']
            && $mailchimp_campaignid
        ) {
            func_mailchimp_adv_campaign_commission($orderid, $mailchimp_campaignid);
        }

        $_extra             = $extra;
        $_extra["tax_info"] = [
            "display_taxed_order_totals"      => $config["Taxes"]["display_taxed_order_totals"],
            "display_cart_products_tax_rates" => $config["Taxes"]["display_cart_products_tax_rates"] == "Y",
            "taxed_subtotal"                  => $current_order["display_subtotal"],
            "taxed_discounted_subtotal"       => $current_order["display_discounted_subtotal"],
            "taxed_shipping"                  => $shipping_cost,
        ];

        if (!empty($active_modules["Special_Offers"])) {
            include $xcart_dir . "/modules/Special_Offers/place_order_extra.php";
        }

        if (!$single_mode) {
            $giftcert_discount = $current_order["giftcert_discount"];
            $giftcert_str      = "";
            if ($current_order["applied_giftcerts"]) {
                foreach ($current_order["applied_giftcerts"] as $k => $v) {
                    $giftcert_str = join("*", [$giftcert_str, "$v[giftcert_id]:$v[giftcert_cost]"]);
                }
            }
        }
        else {
            $current_order['payment_surcharge'] = $cart['payment_surcharge'];
        }

        $mes .= "STEP A " . date("H:i:s") . "\n";

        $taxes_applied = addslashes(serialize($current_order["whole_taxes"]));

        $discount_coupon = $current_order["coupon"];
        if (!empty($current_order["coupon"])) {
            $current_order["coupon"] = func_query_first_cell("SELECT coupon_type FROM $sql_tbl[discount_coupons] WHERE coupon='" . addslashes($current_order["coupon"]) . "'") . "``" . $current_order["coupon"];
        }

        $save_info = $userinfo;
        $userinfo["b_address"] .= "\n" . $userinfo["b_address_2"];
        $userinfo["s_address"] .= "\n" . $userinfo["s_address_2"];

        $mes .= "STEP B " . date("H:i:s") . "\n";

        if (!empty($active_modules['Multiple_Storefronts']) && isset($cart['source_sf']) && !empty($cart['source_sf'])) {
            // If there is a redirect to another domain on checkout
            $sf_info      = func_get_storefront_info($cart['source_sf'], 'ID', true);
            $order_prefix = $sf_info['prefix'];
        }

        $tmp_order_details_arr = explode("\n", $order_details);
        if (!empty($tmp_order_details_arr) && is_array($tmp_order_details_arr)) {
            foreach ($tmp_order_details_arr as $k => $v) {
                if ((strpos($v, "PO Number:")) !== false) {
                    $po_num_arr = explode("PO Number:", $v);
                    $po_num     = trim($po_num_arr[1]);
                }
            }
        }

        global $first_order_total_in_current_session;

        if (!isset($first_order_total_in_current_session)) {
            x_session_register('first_order_total_in_current_session');
        }

        if (empty($first_order_total_in_current_session)) {
            $first_order_total_in_current_session = $current_order['total_cost'];
            x_session_save('first_order_total_in_current_session');
        }

        #
        # Insert into orders
        #
        $insert_data = [
            'login'                                    => addslashes($userinfo['login']),
            'sessid'                                   => addslashes($$XCART_SESSION_NAME),
            'is_mobile_checkout'                       => $is_mobile_checkout,
            'cart_number'                              => $cart["cart_number"],
            'membershipid'                             => $userinfo['membershipid'],
            'membership'                               => addslashes($userinfo['membership']),
            'total'                                    => $current_order['total_cost'],
            'shipping_cost'                            => $shipping_cost,
            'total_shipping_charge_on_orig_po'         => ($order_status == "IO" && $geo_litecity_location["country"] == "US" ? $shipping_cost : '0'),
            'giftcert_discount'                        => $giftcert_discount,
            'giftcert_ids'                             => @$giftcert_str,
            'subtotal'                                 => $current_order['subtotal'],
            'tax'                                      => $current_order['tax_cost'],
            'taxes_applied'                            => $taxes_applied,
            'discount'                                 => $current_order['discount'],
            'coupon'                                   => addslashes(@$current_order['coupon']),
            'coupon_discount'                          => $current_order['coupon_discount'],
            'date'                                     => time(),
            'cb_status'                                => $order_status,
            'dc_status'                                => 'T',
            'bd_status'                                => 'W',
            'payment_method'                           => addslashes($payment_method),
            'paymentid'                                => $cart['paymentid'],
            'payment_surcharge'                        => $current_order['payment_surcharge'],
            'flag'                                     => 'N',
            'details'                                  => addslashes(text_crypt($order_details)),
            'po_number'                                => addslashes($po_num),
            'customer_notes'                           => $customer_notes,
            'clickid'                                  => $partner_clickid,
            'language'                                 => $userinfo['language'],
            'order_prefix'                             => isset($order_prefix) ? $order_prefix : $config['General']['opt_order_prefix'],
            'tax_info_display_taxed_order_totals'      => $_extra["tax_info"]['display_taxed_order_totals'],
            'tax_info_display_cart_products_tax_rates' => $_extra["tax_info"]['display_cart_products_tax_rates'],
            'tax_info_taxed_subtotal'                  => $_extra["tax_info"]['taxed_subtotal'],
            'tax_info_taxed_discounted_subtotal'       => $_extra["tax_info"]['taxed_discounted_subtotal'],
            'tax_info_taxed_shipping'                  => $_extra["tax_info"]['taxed_shipping'],
        ];

        if (!empty($active_modules['Multiple_Storefronts'])) {
            if (isset($cart['source_sf']) && !empty($cart['source_sf'])) {
                $insert_data['storefrontid'] = $cart['source_sf'];
            }
            else {
                $insert_data['storefrontid'] = $current_storefront;
            }
        }

        if (!empty($extra['additional_fields'])) {
            foreach ($extra['additional_fields'] as $aAddFiled) {
                if ($aAddFiled['title'] == 'Company') {
                    $sFiledCompany = strtolower($aAddFiled['section']) . '_company';
                    if (!empty($sFiledCompany)) {
                        $insert_data[$sFiledCompany] = $aAddFiled['value'];
                    }
                }
            }
        }

        if ($insert_data['storefrontid'] > 0) {
            $site_domain = func_query_first_cell("SELECT domain FROM $sql_tbl[storefronts] WHERE storefrontid='$insert_data[storefrontid]'");
        }
        else {
            $site_domain = "www.artistsupplysource.com";
        }
        $mail_smarty->assign('site_domain', $site_domain);


        # copy userinfo
        $_fields = ['title', 'firstname', 'lastname', 'phone', 'phone_ext', 'fax', 'email', 'url', 'company', 'tax_number', 'tax_exempt'];

        $mes .= "STEP C " . date("H:i:s") . "\n";
        foreach ($_fields as $k) {
            if (!isset($userinfo[$k])) {
                continue;
            }

            $insert_data[$k] = addslashes($userinfo[$k]);
        }

        $_fields = ['title', 'firstname', 'lastname', 'address', 'city', 'county', 'state', 'country', 'zipcode'];
        foreach (['b_', 's_'] as $p) {
            foreach ($_fields as $k) {
                $f = $p . $k;
                if (isset($userinfo[$f])) {
                    $insert_data[$f] = addslashes($userinfo[$f]);
                }
            }
        }

        $mes .= "STEP D " . date("H:i:s") . "\n";

        $non_us_confirmation = "";
        if ($insert_data["s_country"] == "CA") {
            $non_us_confirmation = $cart['confirmation_of_responsibility'];
        }

        $insert_data['non_us_confirmation'] = $non_us_confirmation;

        if ($insert_data["b_country"] == "CA")
        {
            $new_b_zipcode        = str_replace(" ", "", $insert_data["b_zipcode"]);
            $strlen_new_b_zipcode = strlen($new_b_zipcode);

            $currect_b_zipcode = "";
            for ($i = 0; $i < $strlen_new_b_zipcode; $i++) {
                $currect_b_zipcode .= $new_b_zipcode{$i};
                if ($i == 2) {
                    $currect_b_zipcode .= " ";
                }
            }

            if (!empty($currect_b_zipcode)) {
                $insert_data["b_zipcode"] = $currect_b_zipcode;
            }
        }

        if ($insert_data["s_country"] == "CA")
        {
            $new_s_zipcode        = str_replace(" ", "", $insert_data["s_zipcode"]);
            $strlen_new_s_zipcode = strlen($new_s_zipcode);
            $currect_s_zipcode    = "";

            for ($i = 0; $i < $strlen_new_s_zipcode; $i++) {
                $currect_s_zipcode .= $new_s_zipcode{$i};
                if ($i == 2) {
                    $currect_s_zipcode .= " ";
                }
            }

            if (!empty($currect_s_zipcode)) {
                $insert_data["s_zipcode"] = $currect_s_zipcode;
            }
        }

        $orderid = func_array2insert('orders', $insert_data);

        global $purchase_order_selected;
        x_session_register('purchase_order_selected');

        if (!empty($purchase_order_selected) && is_numeric($purchase_order_selected))
        {
            global $login;

            $oPoPipeline = \Xcart\POPipeline::model(['po_id' => $purchase_order_selected]);
            if ($oPoPipeline->getPOId()) {
                $oPoPipeline->setOrderToPO($orderid);
            }
            else {
                $oOrder = \Xcart\Order::model(['orderid' => $orderid]);
                try {
                    $oPoPipeline->uploadPurchaseOrder(addslashes($po_num), $oOrder->getField('storefrontid'), 'website');
                }
                catch (Exception $ex) {
                    \Xcart\Logs::_log('purchase_orders', $oPoPipeline->getPOId(), \Xcart\Logs::LOG_TYPE_CLIENT, sprintf(\Xcart\POPipeline::PO_HAS_BEEN_UPLOADED, $oPoPipeline->getOrderNumber() . " (" . $oPoPipeline->getOrderOriginalFileName() . ")"));
                }
            }

            $purchase_order_selected = null;
            x_session_unregister('purchase_order_selected');
            x_session_save($purchase_order_selected);
        }
        else {
            if (!empty($_FILES['purchase_order_file']) && $_FILES['purchase_order_file']['error'] == UPLOAD_ERR_OK)
            {
                $oPoPipeline = \Xcart\POPipeline::model();
                $oOrder      = \Xcart\Order::model(['orderid' => $orderid]);
                try {
                    $oPoPipeline = $oPoPipeline->uploadPurchaseOrder(addslashes($po_num), $oOrder->getField('storefrontid'), 'website');
                    $oPoPipeline->setOrderToPO($orderid);
                    $oPoPipeline->_save();
                    $oOrder->updateField('orig_po', $oOrder->getOrderStoreFront()->getStoreFrontURL().$oPoPipeline->getOrderFileLink());
                }
                catch (Exception $ex) {
                    \Xcart\Logs::_log('purchase_orders', $oPoPipeline->getPOId(), \Xcart\Logs::LOG_TYPE_CLIENT, sprintf(\Xcart\POPipeline::PO_HAS_BEEN_UPLOADED, $ex->getMessage()));
                }
            }
        }

        if ($config["Appearance"]["Enable_surf_stats"] == "Y") {
            SurfingHelper::logSurfPath([
                'resource_type' => SurfPathModel::GOAL_TYPE_ORDER,
                'resource_id' => $orderid
            ]);
        }

        $log = "";
        if (!empty($customer_notes)) {
            $log = "<B>Customer notes:</B><br /> " . $customer_notes . "<br /><br />";
        }

        if (!empty($order_details)) {
            $order_details_br = str_replace("\n", "<br />", $order_details);
            $log .= "<B>Order details:</B> <br />" . $order_details_br;
        }


        unset($insert_data);

        if (!empty($extras) && is_array($extras)) {
            foreach ($extras as $k => $v) {
                if (strlen($v) > 0) {
                    db_query("INSERT INTO $sql_tbl[order_extras] (orderid, khash, value) VALUES ('$orderid', '" . addslashes($k) . "', '" . addslashes($v) . "')");
                }
            }
        }

        $mes .= "STEP E " . date("H:i:s") . "\n";

        $userinfo = $save_info;

        $orderids[] = $orderid;
        $order      = func_select_order($orderid);

        $mes .= "STEP F " . date("H:i:s") . "\n";

        #
        # Insert into order details
        #
        if (!empty($products) && is_array($products)) {

            if (!empty($current_order['shipping_groups'])) {
                foreach ($current_order['shipping_groups'] as $mid => $v) {
                    $current_order['shipping_groups'][$mid]['products'] = [];
                }
            }

            foreach ($products as $pk => $product) {
                if (($single_mode) || ($product["provider"] == $current_order["provider"])) {
                    $product["price"]                                     = price_format($product["price"]);
                    $product["extra_data"]["product_options"]             = $product["options"];
                    $product["extra_data"]["taxes"]                       = $product["taxes"];
                    $product["extra_data"]["display"]["price"]            = price_format($product["display_price"]);
                    $product["extra_data"]["display"]["discounted_price"] = price_format($product["display_discounted_price"]);
                    $product["extra_data"]["display"]["subtotal"]         = price_format($product["display_subtotal"]);
                    if (empty($product["product_orig"])) {
                        $product["product_orig"] = $product["product"];
                    }

                    if (!empty($active_modules['Product_Options'])) {
                        $product["product_options"] = func_serialize_options($product["options"]);
                    }
                    $original_provider = '';

                    $original_provider = func_query_first_cell("SELECT provider FROM $sql_tbl[products] WHERE productid='" . $product['productid'] . "'");

                    $insert_data = [
                        'orderid'           => $orderid,
                        'productid'         => $product['productid'],
                        'item_cost_to_us'   => $product["cost_to_us"],
                        'product'           => addslashes($product['product_orig']),
                        'product_options'   => addslashes($product['product_options']),
                        'amount'            => $product['amount'],
                        'price'             => $product['price'],
                        'provider'          => addslashes($product["provider"]),
                        'extra_data'        => addslashes(serialize($product["extra_data"])),
                        'original_provider' => addslashes($original_provider),
                        'productcode'       => addslashes($product['productcode']),
                    ];

                    $products[$pk]['itemid'] = func_array2insert('order_details', $insert_data);
                    unset($insert_data);

                    #
                    # Insert into subscription_customers table (for subscription products)
                    #
                    if (!empty($active_modules["Subscriptions"])) {
                        include $xcart_dir . "/modules/Subscriptions/subscriptions_cust.php";
                    }

                    #
                    # Check if this product is in Wish list
                    #
                    if (!empty($active_modules["Wishlist"])) {
                        include $xcart_dir . "/modules/Wishlist/place_order.php";
                    }

                    if (!empty($active_modules["Recommended_Products"])) {
                        $rec_counter = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[stats_customers_products] WHERE productid='$product[productid]' AND login='" . addslashes($userinfo["login"]) . "'");
                    }
                }
                $current_order['shipping_groups'][func_manufacturerid_for_group($product['shipping_freight'], $product['manufacturerid'])]['products'][] = $product;

                $oProduct = \Xcart\Product::model(['productid' => (int)$product['productid']]);
                $oProduct->createHTMLShot($orderid);
            }

            $mes .= "STEP H " . date("H:i:s") . "\n";
        }

        if (!empty($current_order['shipping_groups'])) {
            # Reset order detailed totals
            $shippingLogMessage = null;
            $_extra['total'] = $_extra['product_total'] = $_extra['shipping_total'] = ['net' => 0, 'gst' => 0, 'pst' => 0, 'gross' => 0];
            if (!empty($config['Shipping']['new_shipping_calculation']) && $config['Shipping']['new_shipping_calculation'] == 'Y') {
                $shippingLogMessage = "<br/><b>Shipping cost:</b> <br/>";
            }
            foreach ($current_order['shipping_groups'] as $mid => $v) {
                $insert_data                   = [];
                $insert_data['orderid']        = $orderid;
                $insert_data['manufacturerid'] = $mid;
                $insert_data['shippingid']     = @$cart['shippingids'][$mid];

                $cart_groups_delivery_mid = @$cart['groups_delivery'][$mid];

                if ($cart_groups_delivery_mid == "_USE_MY_UPS_FEDEX_ACCOUNT_") {
                    $cart_groups_delivery_mid = @$cart['ship_by_shipping_method'][$mid] . " (charge to " . @$cart['use_my_account'][$mid] . " account # " . @$cart['use_my_account_number'][$mid] . ")";
                }
                elseif ($cart_groups_delivery_mid == "_USE_MY_TRUCKING_ACCOUNT_") {
                    $cart_groups_delivery_mid = @$cart['t_ship_by_shipping_method'][$mid] . " (charge to trucking account # " . @$cart['t_use_my_account_number'][$mid] . ")";
                }
                elseif ($cart_groups_delivery_mid == "_SHIP_BY_FASTEST_METHOD_") {
                    $cart_groups_delivery_mid = "The fastest possible shipping method";
                }

                $insert_data['shipping'] = $cart_groups_delivery_mid;
                $group_shipping          = [];
                $group_shipping['net']   = @$current_order['shipping_costs'][$mid];
                $group_shipping['gross'] = @$current_order['display_shipping_costs'][$mid];
                $group_shipping['gst']   = @$current_order['shipping_taxes'][$mid]['gst'];
                $group_shipping['pst']   = @$current_order['shipping_taxes'][$mid]['pst'];
                $group_total             = func_get_group_totals($v['products'], $group_shipping);
                foreach ($group_total as $totk => $totv) {
                    $_extra['total'][$totk] += $totv;
                    $_extra['shipping_total'][$totk] += $group_shipping[$totk];
                    $_extra['product_total'][$totk] += $totv - $group_shipping[$totk];
                }
                foreach ($price_details_names as $dn) {
                    $insert_data["shipping_$dn"] = $group_shipping[$dn];
                    $insert_data["total_$dn"]    = $group_total[$dn];
                }

                $insert_data['cb_status'] = $order_status;
                $insert_data['dc_status'] = 'T';
                $insert_data['bd_status'] = 'W';

                // Get manufacturer data
                $oManufacturer = \Xcart\Manufacturer::model(['manufacturerid' => $mid]);
                $manufact_data = $oManufacturer->getFields(['m_city', 'm_state', 'm_country']);

                if (!empty($manufact_data) && is_array($manufact_data)) {
                    $insert_data['manufacturer_data'] = serialize($manufact_data);
                }

                if (empty($insert_data['shipping'])) {
                    $insert_data['shipping'] = "UPS SERVER FAILED TO DELIVER SHIPPING CHARGE";
                }

                func_array2insert('order_groups', $insert_data);
                if (!empty($config['Shipping']['new_shipping_calculation']) && $config['Shipping']['new_shipping_calculation'] == 'Y') {
                    $total_shipping_cost = price_format($cart['all_shippings'][$mid][$cart['shippingids'][$mid]]['rate']);
                    $shippingLogMessage .= "<b>{$oManufacturer->getManufacturerCode()} ({$total_shipping_cost})</b><br/>";
                    if (!empty($cart['all_shippings'][$mid][$cart['shippingids'][$mid]]['added_shipping']) && is_array($cart['all_shippings'][$mid][$cart['shippingids'][$mid]]['added_shipping'])) {
                        foreach ($cart['all_shippings'][$mid][$cart['shippingids'][$mid]]['added_shipping'] as $aAddedShippingRate) {
                            $subMapCharge = '';
                            $oShippingAdded = \Xcart\Shipping::model(['shippingid' => $aAddedShippingRate['shippingid']]);
                            $addedCharge = price_format($aAddedShippingRate['shipping_charge']);
                            $mapCharge = floatval($aAddedShippingRate['shipping_extra_margin_value']);
                            if ($mapCharge > 0) {
                                $smapCharge = price_format($mapCharge);
                                $subMapCharge = " (-{$smapCharge})";
                            }
                            $shippingLogMessage .= str_repeat("&nbsp;", 4) . "{$oShippingAdded->getShippingCarrier()->getName()} - {$oShippingAdded->getName()} ({$addedCharge}{$subMapCharge}) <br/>";
                            if (!empty($aAddedShippingRate['products'])) {
                                foreach ($aAddedShippingRate['products'] as $sProductSKU) {
                                    $shippingLogMessage .= str_repeat("&nbsp;", 8) . "$sProductSKU <br/>";
                                }
                            }
                            $total_shipping_cost -= $addedCharge;
                        }
                    }
                    $oShippingAdded = \Xcart\Shipping::model(['shippingid' => $cart['all_shippings'][$mid][$cart['shippingids'][$mid]]['shippingid']]);
                    $mapCharge = floatval($cart['all_shippings'][$mid][$cart['shippingids'][$mid]]['shipping_extra_margin_value']);
                    $subMapCharge = '';
                    if ($mapCharge > 0) {
                        $smapCharge = price_format($mapCharge);
                        $subMapCharge = " (-{$smapCharge})";
                    }
                    $shippingLogMessage .= str_repeat("&nbsp;", 4) . "{$oShippingAdded->getShippingCarrier()->getName()} - {$oShippingAdded->getName()} ({$total_shipping_cost}{$subMapCharge})<br/>";
                    if (!empty($cart['all_shippings'][$mid][$cart['shippingids'][$mid]]['products']) && is_array($cart['all_shippings'][$mid][$cart['shippingids'][$mid]]['products'])) {
                        foreach ($cart['all_shippings'][$mid][$cart['shippingids'][$mid]]['products'] as $sProductSKU) {
                            $shippingLogMessage .= str_repeat("&nbsp;", 8) . "$sProductSKU <br/>";
                        }
                    }
                }
            }
            if (!empty($shippingLogMessage)) {
                $log .= $shippingLogMessage;
            }
            $log .= "<br /><B>REMOTE_ADDR:</B> " . $ip_info;
            \Xcart\Logs::_log('orders', $orderid, \Xcart\Logs::LOG_TYPE_CLIENT, $log, $userinfo['login']);

            $insert_data = [];

            foreach ($price_details_names as $dn) {
                $insert_data["shipping_total_$dn"] = $_extra['shipping_total'][$dn];
                $insert_data["product_total_$dn"]  = $_extra['product_total'][$dn];
                $insert_data["total_$dn"]          = $_extra['total'][$dn];
            }

            # Update order detailed totals

            func_array2update("orders", $insert_data, "orderid='$orderid'");
            unset($group_shipping);
            unset($group_total);
            unset($insert_data);
        }
        $oOrder = \Xcart\Order::model(['orderid' => $orderid]);
        $oOrder->updateVerificationStatus();

        $oSurfPath = SurfPathModel::objects()
            ->filter(['resource_type' => SurfPathModel::GOAL_TYPE_REFERER,
                'meta_id' =>  SurfMetaModel::getInstance()->id])
            ->order(['-id'])
            ->limit(1)->get();
        if ($oSurfPath) {
            $oOrder->updateField('referer_id', $oSurfPath->resource_id);
        }

        if (!empty($active_modules['XAffiliate'])) {
            #
            # Partner commission
            #
            if (!empty($partner)) {
                include $xcart_dir . "/include/partner_commission.php";
            }

            #
            # Save link order -> advertising campaign
            #
            if ($adv_campaignid) {
                include $xcart_dir . "/include/adv_campaign_commission.php";
            }
        }

        $mes .= "STEP I " . date("H:i:s") . "\n";

        if ((($single_mode) || (empty($current_order["provider"]))) && (!empty($cart["giftcerts"]))) {
            foreach ($cart["giftcerts"] as $gk => $giftcert) {
                $gcid = substr(strtoupper(md5(uniqid(rand()))), 0, 16);

                #
                # status == Pending!
                #
                $insert_data = [
                    'gcid'                => $gcid,
                    'orderid'             => $orderid,
                    'purchaser'           => addslashes($giftcert['purchaser']),
                    'recipient'           => addslashes($giftcert['recipient']),
                    'send_via'            => $giftcert['send_via'],
                    'recipient_email'     => @$giftcert['recipient_email'],
                    'recipient_firstname' => addslashes(@$giftcert['recipient_firstname']),
                    'recipient_lastname'  => addslashes(@$giftcert['recipient_lastname']),
                    'recipient_address'   => addslashes(@$giftcert['recipient_address']),
                    'recipient_city'      => addslashes(@$giftcert['recipient_city']),
                    'recipient_county'    => @$giftcert['recipient_county'],
                    'recipient_state'     => @$giftcert['recipient_state'],
                    'recipient_country'   => @$giftcert['recipient_country'],
                    'recipient_zipcode'   => @$giftcert['recipient_zipcode'],
                    'recipient_phone'     => @$giftcert['recipient_phone'],
                    'recipient_phone_ext' => @$giftcert['recipient_phone_ext'],
                    'message'             => addslashes($giftcert['message']),
                    'amount'              => $giftcert['amount'],
                    'debit'               => $giftcert['amount'],
                    'status'              => 'P',
                    'add_date'            => time(),
                ];

                if ($giftcert['send_via'] == 'P') {
                    $insert_data['tpl_file'] = $giftcert['tpl_file'];
                }

                func_array2insert('giftcerts', $insert_data);
                unset($insert_data);

                $cart["giftcerts"][$gk]['gcid'] = $gcid;

                #
                # Check if this giftcertificate is in Wish list
                #
                if (!empty($active_modules["Wishlist"])) {
                    include $xcart_dir . "/modules/Wishlist/place_order.php";
                }
            }
        }

        $mes .= "STEP J " . date("H:i:s") . "\n";

        #
        # Mark discount coupons used
        #
        if ($discount_coupon) {
            $_per_user = func_query_first_cell("SELECT per_user FROM $sql_tbl[discount_coupons] WHERE coupon='$discount_coupon' LIMIT 1");
            if ($_per_user == "Y") {
                $_need_to_update = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[discount_coupons_login] WHERE coupon='$discount_coupon' AND login='" . addslashes($userinfo["login"]) . "' LIMIT 1");
                if ($_need_to_update > 0) {
                    db_query("UPDATE $sql_tbl[discount_coupons_login] SET times_used = times_used+1 WHERE coupon = '" . addslashes($discount_coupon) . "' AND login = '" . addslashes($userinfo["login"]) . "'");
                }
                else {
                    db_query("INSERT INTO $sql_tbl[discount_coupons_login] (coupon, login, times_used) VALUES ('" . addslashes($discount_coupon) . "', '" . addslashes($userinfo["login"]) . "', '1')");
                }
            }
            else {
                db_query("UPDATE $sql_tbl[discount_coupons] SET times_used = times_used+1 WHERE coupon = '" . addslashes($discount_coupon) . "'");
                db_query("UPDATE $sql_tbl[discount_coupons] SET status='U' WHERE coupon='" . addslashes($discount_coupon) . "' AND times_used=times");
            }
            $discount_coupon = "";
        }

        $mes .= "STEP K " . date("H:i:s") . "\n";

        #
        # Mail template processing
        #

        if (is_array($sf_info) && !empty($sf_info)) {
            $mail_smarty->assign('sf_info', $sf_info);
        }

        if (empty($statuses)) {
            $statuses = func_query_hash('SELECT code, name, type FROM ' . $sql_tbl['order_statuses']
                                        . ' ORDER BY orderby', ['type', 'code'], false, true);
        }

        $admin_notify    = ((($order_status == 'Q' || $order_status == 'O' || $order_status == 'IO')
                             && $config['Email_Note']['enable_order_notif'] == 'Y')
                            || ($order_status == 'I' && $config['Email_Note']['enable_init_order_notif'] == 'Y'));
        $customer_notify = in_array($order_status, ['Q', 'I', 'O', 'IO']);
        $customer_notify = ($customer_notify && !(defined('GOOGLE_CHECKOUT_CALLBACK') && $config['Google_Checkout']['gcheckout_disable_customer_notif'] == 'Y'));

        $order_data = func_order_data($orderid);
        $mail_smarty->assign("products", $order_data["products"]);
        $mail_smarty->assign("giftcerts", $order_data["giftcerts"]);
        $mail_smarty->assign("order", $order_data["order"]);
        $mail_smarty->assign("userinfo", $order_data["userinfo"]);
        $mail_smarty->assign('statuses', $statuses);
        $mail_smarty->assign('oOrder', $oOrder);

        $prefix = ($order_status == "I" ? "init_" : "");

        $mes .= "STEP L " . date("H:i:s") . "\n";

        $aorder_notification = func_get_order_notification($order_status, $order_data);
        if (!empty($aorder_notification))
        {
            global $attach_pdf_invoice, $statuses;

            foreach ($aorder_notification as $oOrderNotification)
            {
                if ($oOrderNotification->isEnabled()) {
                    $order_notification = $oOrderNotification->getFields();

                    if ($order_notification['enabled'] == 'Y') {

                        $mail_smarty->assign('order_notification', $order_notification);

                        if ($customer_notify) {
                            #
                            # Notify customer by email
                            #
                            $to_customer = ($userinfo['language'] ? $userinfo['language'] : $config['default_customer_language']);
                            $mail_smarty->assign("products", func_translate_products($order_data["products"], $to_customer));
                            $mail_smarty->assign('type', 'C');
                            $attach_pdf_invoice = $oOrderNotification->getField('customer_attach_pdf_invoice');
                            $oMail = \Xcart\App\Main\Xcart::app()->mail;
                            $oMail->to = $userinfo['email'];
                            $oMail->from = $config['Company']['orders_department'];
                            $oMail->reply_to = null;
                            $oMail->subject_template = 'mail/order_notification_subj.tpl';
                            $oMail->body_template = 'mail/order_notification.tpl';
                            $oMail->sendEmail();

                            //func_send_mail($userinfo['email'], 'mail/order_notification_subj.tpl', 'mail/order_notification.tpl', $config['Company']['orders_department'], false);
                        }

                        $mes .= "STEP M " . date("H:i:s") . "\n";

                        if (!empty($order_data["order"]['payment_method_orig'])) {
                            $order_data["order"]['payment_method'] = $order_data["order"]['payment_method_orig'];
                            $mail_smarty->assign("order", $order_data["order"]);
                        }

                        $mail_smarty->assign("products", $order_data["products"]);
                        if ($admin_notify)
                        {
                            #
                            # Notify orders department by email
                            #
                            $mail_smarty->assign('type', 'A');
                            $mail_smarty->assign("show_order_details", "Y");
                            $mes .= "STEP N " . date("H:i:s") . "\n";
                            $mail_smarty->assign('type', 'A');

                            $to       = $config['Company']['orders_department'];
                            $from     = $userinfo["firstname"] . "<" . $config['Company']['orders_department'] . ">";
                            $reply_to = $userinfo["firstname"] . "<" . $userinfo['email'] . ">";

                            $attach_pdf_invoice = $oOrderNotification->getField('admin_attach_pdf_invoice');

                            $oMail = \Xcart\App\Main\Xcart::app()->mail;
                            $oMail->to = $to;
                            $oMail->reply_to = $reply_to;
                            $oMail->from = $from;
                            $oMail->subject_template = 'mail/order_notification_subj.tpl';
                            $oMail->body_template = 'mail/order_notification.tpl';
                            $oMail->addHeader(['X-Xcart-Label' => 'order-status-init']);
                            $oMail->sendEmail();

                            $oMail = \Xcart\App\Main\Xcart::app()->mail;
                            $oMail->to = "igor@s3stores.com";
                            $oMail->from = "orders@s3stores.com";
                            $oMail->reply_to = $reply_to;
                            $oMail->subject_template = 'mail/order_notification_subj.tpl';
                            $oMail->body_template = 'mail/order_notification.tpl';
                            $oMail->sendEmail();

                            //func_send_mail($to, 'mail/order_notification_subj.tpl', 'mail/order_notification.tpl', $from, true, true, false, false, $reply_to);
                            //func_send_mail("igor@s3stores.com", 'mail/order_notification_subj.tpl', 'mail/order_notification.tpl', "orders@s3stores.com", false);

                            $mes .= "STEP O " . date("H:i:s") . "\n";
                            $mail_smarty->assign("show_order_details", "N");

                            #
                            # Notify provider (or providers) by email
                            #
                            $mail_smarty->assign('type', 'P');
                            if ((!$single_mode) && ($current_order["provider"]) && $config["Email_Note"]["send_notifications_to_provider"] == "Y") {
                                $pr_result  = func_query_first("SELECT email, language FROM $sql_tbl[customers] WHERE login='$current_order[provider]'");
                                $prov_email = $pr_result ["email"];
                                if ($prov_email != $config["Company"]["orders_department"]) {
                                    $to_customer = $pr_result['language'];
                                    if (empty($to_customer)) {
                                        $to_customer = $config['default_admin_language'];
                                    }

                                    $mes .= "STEP P " . date("H:i:s") . "\n";

                                    func_send_mail($prov_email, "mail/" . $prefix . "order_notification_subj.tpl", "mail/order_notification.tpl", $userinfo["email"], false);
                                    $mes .= "STEP R " . date("H:i:s") . "\n";
                                }
                            }
                            elseif ($config["Email_Note"]["send_notifications_to_provider"] == "Y" && !empty($products) && is_array($products)) {
                                $providers = [];
                                foreach ($products as $product) {
                                    $pr_result = func_query_first("select email, language from $sql_tbl[customers] where login='$product[provider]'");
                                    if ($pr_result["email"]) {
                                        $providers[$product['provider']] = $pr_result;
                                    }
                                }

                                if ($providers) {
                                    foreach ($providers as $prov_data) {
                                        if ($prov_data['email'] == $config["Company"]["orders_department"]) {
                                            continue;
                                        }

                                        $to_customer = $prov_data['language'];
                                        if (empty($to_customer)) {
                                            $to_customer = $config['default_admin_language'];
                                        }
                                        $mes .= "STEP S " . date("H:i:s") . "\n";
                                        func_send_mail($prov_data['email'], "mail/" . $prefix . "order_notification_subj.tpl", "mail/order_notification.tpl", $userinfo["email"], false);
                                        $mes .= "STEP T " . date("H:i:s") . "\n";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($active_modules['Survey']) && defined("AREA_TYPE") && constant("AREA_TYPE") == 'C') {
            func_check_surveys_events("OPL", $order_data);
        }

        //func_check_and_send_request_availability_email($orderid);
    }

    $mes .= "STEP U " . date("H:i:s") . "\n";

    #
    # Send notifications to orders department and providers when product amount in stock is low
    #
    if ($config["General"]["unlimited_products"] != "Y") {
        foreach ($order_data["products"] as $product) {
            if (!empty($product["distribution"]) && $active_modules["Egoods"]) {
                continue;
            }

            if ($product['product_type'] == 'C' && !empty($active_modules['Product_Configurator'])) {
                continue;
            }

            if ($active_modules['Product_Options'] && $product['extra_data']['product_options']) {
                $avail_now = func_get_options_amount($product['extra_data']['product_options'], $product['productid']);
            }
            else {
                $avail_now = func_query_first_cell("SELECT avail FROM $sql_tbl[products] WHERE productid='" . $product["productid"] . "'");
            }

            if ($product['low_avail_limit'] >= $avail_now && $config['Email_Note']['eml_lowlimit_warning'] == 'Y') {
                #
                # Mail template processing
                #
                $product['avail'] = $avail_now;
                $mail_smarty->assign("product", $product);

                func_send_mail($config["Company"]["orders_department"], "mail/lowlimit_warning_notification_subj.tpl", "mail/lowlimit_warning_notification_admin.tpl", $config["Company"]["orders_department"], true);

                $pr_result = func_query_first("SELECT email, language FROM $sql_tbl[customers] WHERE login='" . $product["provider"] . "'");
                if ((!$single_mode) && ($pr_result["email"] != $config["Company"]["orders_department"]) && $config['Email_Note']['eml_lowlimit_warning_provider'] == 'Y') {
                    $to_customer = $pr_result['language'];
                    if (empty($to_customer)) {
                        $to_customer = $config['default_admin_language'];
                    }

                    func_send_mail($pr_result["email"], "mail/lowlimit_warning_notification_subj.tpl", "mail/lowlimit_warning_notification_admin.tpl", $config["Company"]["orders_department"], false);
                }
            }
        }
    }

    $mes .= "STEP W " . date("H:i:s") . "\n";

    #
    # Release previously created lock
    #
    func_unlock("place_order");

    $mes .= "STEP V " . date("H:i:s") . "\n";

    x_log_add("order_time", $mes, true);


    return $orderids;
}

function func_check_and_send_request_availability_email($orderid, $sent_by = '')
{
    global $mail_smarty, $config, $sql_tbl;

    $mnfs = func_get_order_manufacturers($orderid);

    $mail_smarty->assign('cidev_hide_invoice', "Y");

    $allowed_cb_statuses = ["P", "Q", "O", "AP"];
    $allowed_dc_statuses = ["T"];

    if (!empty($mnfs) && is_array($mnfs)) {
        foreach ($mnfs as $m_id => $mv) {

            if (
                !empty($mv["cb_status"])
                && in_array($mv["cb_status"], $allowed_cb_statuses)
                && in_array($mv["dc_status"], $allowed_dc_statuses)
                && $mv["d_availability_must_be_checked"] == "Y"
                && $mv["good_time_to_send_email_to_distributor"] == "Y"
            ) {
                $to = $mv["d_send_to_email_14"];
                $from = $config['Company']['orders_department'];
                $mnf_body = func_eol2br(stripslashes($mv["d_message_body_14"]));
                $mail_smarty->assign("message_body", $mnf_body);
                $mail_smarty->assign('d_email_subject_14', $mv["d_email_subject_14"]);

                $order_notes = "";
                $current_dc_status = func_query_first_cell_param(/** @lang MySQL */
                    "SELECT dc_status FROM xcart_order_groups WHERE orderid = :orderid AND manufacturerid = :m_id", ['orderid' => $orderid, 'm_id' => $m_id]);
                $current_dc_status_value = func_query_first_cell_param(/** @lang MySQL */
                    "SELECT name FROM xcart_order_statuses WHERE code = :current_dc_status", ['current_dc_status' => $current_dc_status]);

                if ($current_dc_status != "K") {
                    $code = $mv["code"];
                    $new_value = func_query_first_cell_param(/** @lang MySQL */
                        "SELECT name FROM xcart_order_statuses WHERE code=:code", ['code' => 'K']);
                    $order_notes = "<B>" . $code . ":</B> dc_status: " . $current_dc_status_value . " -> " . $new_value . "<br />";

                    $current_notify_sent = func_query_first_cell_param(/** @lang MySQL */
                        "SELECT notify_sent FROM xcart_order_groups WHERE orderid = :orderid AND manufacturerid=:m_id", ['orderid' => $orderid, 'm_id' => $m_id]);
                    if ($current_notify_sent != "Y") {
                        $order_notes .= "<B>" . $code . ":</B> notify_sent: " . $current_notify_sent . " -> Y <br />";
                    }
                }

                if (!empty($mv["add_ca_status_id"])) {
                    $is_such_additional_tag_status = func_query_first_cell_param(/** @lang MySQL */
                        "SELECT status_id FROM xcart_orders_additional_tags WHERE orderid=:orderid AND status_id=:status_id", ['orderid' => $orderid, 'status_id' => $mv['add_ca_status_id']]);

                    if (empty($is_such_additional_tag_status)) {
                        Modules\Order\Helpers\OrderTagEventHelper::orderTagEvent($mv['add_ca_status_id'], $orderid);
                    }
                }

                db_query_param(/** @lang MySQL */
                    "UPDATE xcart_order_groups SET notify_sent = 'Y', dc_status='K' WHERE orderid = :orderid AND manufacturerid = :m_id", ['orderid' => $orderid, 'm_id' => $m_id]);
                $order_notes .= date('l jS \of F Y h:i:s A') . ": Request availability email was sent automatically to '" . $mv["manufacturer"] . "' distributor";
                if ($sent_by == 'CRON') {
                    $order_notes .= ", by CRON";
                } else {
                    $order_notes .= ", when order was placed by customer. ";
                }

                func_log_order($orderid, 'S', $order_notes);

                $oMail = \Xcart\App\Main\Xcart::app()->mail;
                $oMail->to = $to;
                $oMail->from = $from;
                $oMail->reply_to = null;
                $oMail->subject_template = 'mail/order_notification_subj.tpl';
                $oMail->body_template = 'mail/order_notification_mnf.tpl';
                $oMail->addHeader(['X-Xcart-Label' => 'order-communication']);
                $oMail->sendEmail();
                //func_send_mail($to, "mail/order_notification_subj.tpl", "mail/order_notification_mnf.tpl", $from, false);
            }
        }
    }
}

function func_get_order_manufacturers($orderid)
{
    global $sql_tbl, $config, $userfirstname, $userfullname;

    $order_data = func_order_data($orderid);

    $request_availability_options = func_query("SELECT * FROM $sql_tbl[request_availability_options]");

    $order     = $order_data["order"];
    $userinfo  = $order_data["userinfo"];
    $products  = $order_data["products"];
    $giftcerts = $order_data["giftcerts"];

    $firstname       = trim($userinfo["firstname"]);
    $c_firstname_arr = explode(" ", $firstname);
    $c_firstname     = array_pop($c_firstname_arr);

    if (!empty($order['shipping_groups']))
    {
        $mnfs = array_keys($order['shipping_groups']);

        if (!empty($mnfs))
        {
            $mnfs = func_query_hash('SELECT * FROM ' . $sql_tbl["manufacturers"] . ' WHERE manufacturerid IN ("' . implode('","', $mnfs) . '")', 'manufacturerid', false);

            $cidev_ship_to = $order["s_city"] . ", " . $order["s_state"] . "  " . $order["s_zipcode"];
            if ($order["s_country"] != "US") {
                $cidev_ship_to .= ", " . $order["s_countryname"];
            }
            $cidev_ship_to_full = $order["s_address"] . ", " . $cidev_ship_to;

            foreach ($mnfs as $m_id => $mv)
            {
                $signature = func_get_signature($mnfs[$m_id]["d_main_sf"], false, $order);

                $mnfs[$m_id]['notify_sent'] = $order['shipping_groups'][$m_id]['notify_sent'];

                $mnfs[$m_id]['cb_status'] = $order['shipping_groups'][$m_id]['cb_status'];
                $mnfs[$m_id]['dc_status'] = $order['shipping_groups'][$m_id]['dc_status'];
                $mnfs[$m_id]['bd_status'] = $order['shipping_groups'][$m_id]['bd_status'];

                if (!empty($products) && is_array($products)) {

                    $total_product_cost_to_us = 0;

                    $cidev_items_table = "";
                    $cidev_items_table .= '<table width="500px" border="1" cellpadding="5" cellspacing="0" bordercolor="#414236" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #000000; line-height: 18px;">';
                    $cidev_items_table .= '<tr><td width="150px" style="text-align: left; font-weight: bold;">Item number</td><td width="250px" style="text-align: left; font-weight: bold;">Item name</td><td style="text-align: right; font-weight: bold;">Quantity</td></tr>';

                    $cidev_instock_items_table = '<table width="500px" border="1" cellpadding="5" cellspacing="0" bordercolor="#414236" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #000000; line-height: 18px;">';
                    $cidev_instock_items_table .= '<tr><td width="150px" style="text-align: left; font-weight: bold;">Item number</td><td width="250px" style="text-align: left; font-weight: bold;">Item name</td><td style="text-align: right; font-weight: bold;">Quantity in stock</td></tr>';

                    $cidev_outofstock_items_table = '<table width="500px" border="1" cellpadding="5" cellspacing="0" bordercolor="#414236" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #000000; line-height: 18px;">';
                    $cidev_outofstock_items_table .= '<tr><td width="150px" style="text-align: left; font-weight: bold;">Item number</td><td width="250px" style="text-align: left; font-weight: bold;">Item name</td><td style="text-align: right; font-weight: bold;">"Out of stock" quantity</td></tr>';

                    $order_products = '<br /><table width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td valign="top"><img alt="" src="https://www.artistsupplysource.com/skin1_kolin/images/S3-Stores-Logo-M.png"></td><td width="100%" valign="top"><table width="100%" cellspacing="0" cellpadding="2"><tr><td width="30">&nbsp;</td><td valign="top"><h1 style="margin-bottom: 0px; margin-top: 0px; text-transform: uppercase;">PURCHASE ORDER</h1><b>Order date:</b> ' . date("j-M-Y", $order["date"]) . '<br /><b>Order #:</b> ' . $order["order_prefix"] . $order["orderid"] . '<br /></td><td valign="bottom" align="right"><b>S3 Stores, Inc.</b><br />27 Joseph St.,<br>Chatham, Ontario<br />N7L 3G4, Canada<br />Toll Free: ' . $config["Company"]["company_phone"] . '<br />Tel: ' . $config["Company"]["company_phone_2"] . '<br />Fax: ' . $config["Company"]["company_fax"] . '<br>Email: orders@s3stores.com</td></tr></table></td></tr></table><hr style="width:100%;margin: 0px 0 5px 0; border: 0 none; border-bottom: 1px solid #999999;">';

                    $order_products .= '<table width="45%" cellspacing="0" cellpadding="0" border="0"><tr><td nowrap="nowrap"> <b>Full Name:</b> </td> <td>' . $userinfo["firstname"] . '</td></tr><tr><td><b>Phone:</b></td><td>' . $userinfo["phone"] . (!empty($userinfo["phone_ext"]) ? " <B>ext</B> $userinfo[phone_ext] " : "") . '</td></tr><tr><td colspan="2"><br /><b>Shipping Address</b><hr style="width:100%;margin: 0px; border: 0 none; border-bottom: 1px solid #999999;"></td></tr><tr><td><b>Full Name:</b></td><td>' . $userinfo["s_firstname"] . (!empty($order["po_number"]) ? ' / PO# ' . $order["po_number"] : '') . '</td></tr><tr><td><b>Company:</b></td><td>' . $userinfo["additional_fields"]["1"]["value"] . '</td></tr><tr><td><b>Address:</b></td><td>' . $userinfo["s_address"] .
                                       '</td></tr>' . (!empty($userinfo["s_address_2"]) ? '<tr><td><b>Address (line 2):</b></td><td>' . $userinfo["s_address_2"] . '</td></tr>' : '') . '<tr><td><b>City:</b> </td><td>' . $userinfo["s_city"] . '</td></tr><tr><td><b>State/Province:</b> </td><td>' . $userinfo["s_statename"] . '</td></tr><tr><td><b>Country:</b></td><td>' . $userinfo["s_countryname"] . '</td></tr><tr><td><b>Zip/Postal code:</b></td><td>' . $userinfo["s_zipcode"] . '</td></tr></table>';

                    if ($mv["add_cost_to_us_column_to_dispatch_message"] == "Y") {
                        $order_products .= '<table style="margin-top: 5px;" width="100%" cellspacing="0" cellpadding="3" border="1"><tr><th width="60" bgcolor="#cccccc" align="center">SKU</th><th width="*" bgcolor="#cccccc" align="center">Product</th><th width="90" nowrap="nowrap" bgcolor="#cccccc" align="center">\'Cost to us\' per item</th><th width="50" nowrap="nowrap" bgcolor="#cccccc" align="center">Qty</th></tr>';
                    }
                    else {
                        $order_products .= '<table style="margin-top: 5px;" width="100%" cellspacing="0" cellpadding="3" border="1"><tr><th width="60" bgcolor="#cccccc" align="center">SKU</th><th width="*" bgcolor="#cccccc" align="center">Product</th><th width="50" nowrap="nowrap" bgcolor="#cccccc" align="center">Qty</th></tr>';
                    }

                    $order_products_counter = 0;

                    foreach ($products as $k => $v)
                    {
                        if ($v["manufacturerid"] == $m_id) {

                            $selected_product_options = "";
                            if (!empty($v["product_options"]) && is_array($v["product_options"])) {

                                foreach ($v["product_options"] as $kk => $vv) {
                                    $selected_product_options .= "<br />" . $vv["classtext"] . " " . $vv["option_name"];
                                }
                            }

                            $tmp_sku = $v['oProduct']->getMPN();

                            $cidev_items_table .= '<tr><td width="150px" style="text-align: left;">' . $tmp_sku . '</td><td width="250px" style="text-align: left;"><a href="' . $v["links"]["customer"] . '">' . $v["product"] . '</a>' . $selected_product_options . '</td><td style="text-align: right;">' . $v["amount"] . '</td></tr>';

                            $instock_items = $v["amount"] - $v["back"];
                            $cidev_instock_items_table .= '<tr><td width="150px" style="text-align: left;">' . $tmp_sku . '</td><td width="250px" style="text-align: left;"><a href="' . $v["links"]["customer"] . '">' . $v["product"] . '</a>' . $selected_product_options . '</td><td style="text-align: right;">' . $instock_items . '</td></tr>';

                            $cidev_outofstock_items_table .= '<tr><td width="150px" style="text-align: left;">' . $tmp_sku . '</td><td width="250px" style="text-align: left;"><a href="' . $v["links"]["customer"] . '">' . $v["product"] . '</a>' . $selected_product_options . '</td><td style="text-align: right;">' . $v["back"] . '</td></tr>';

                            $order_products_amount = $v["amount"];

                            if (!empty($order["refund_groups"][$m_id]["products"][$v["itemid"]]["ref_qty"])) {
                                $tmp_ref_qty = $order["refund_groups"][$m_id]["products"][$v["itemid"]]["ref_qty"];
                                $order_products_amount -= $tmp_ref_qty;
                            }

                            if ($order_products_amount > 0) {
                                $order_products_counter++;

                                if ($mv["add_cost_to_us_column_to_dispatch_message"] == "Y") {
                                    $order_products .= '<tr><td align="center">' . $tmp_sku . '</td><td><font style="FONT-SIZE: 11px"><a href="' . $v["links"]["customer"] . '">' . $v["product"] . '</a>' . $selected_product_options . '</font></td><td align="center">US$' . $v["cost_to_us"] . '</td><td align="center">' . $order_products_amount . '</td></tr>';
                                }
                                else {
                                    $order_products .= '<tr><td align="center">' . $tmp_sku . '</td><td><font style="FONT-SIZE: 11px"><a href="' . $v["links"]["customer"] . '">' . $v["product"] . '</a>' . $selected_product_options . '</font></td><td align="center">' . $order_products_amount . '</td></tr>';
                                }
                            }
                        }
                    }
                    $cidev_items_table .= "</table>";
                    $cidev_instock_items_table .= "</table>";
                    $cidev_outofstock_items_table .= "</table>";

                    if ($order_products_counter > 0) {
                        $order_products .= "</table>";
                    }
                    else {
                        $order_products = "";
                    }
                }

                /** @var \Xcart\OrderGroup $oOrderGroup */
                $oOrderGroup = Xcart\OrderGroup::objects()->filter(['orderid' => $orderid, 'manufacturerid' => $m_id])->get();
                if ($oOrderGroup) {
                    $total_product_cost_to_us = $oOrderGroup->getTotalCostToUs();
                }
                $mnfs[$m_id]['total_product_cost_to_us'] = $total_product_cost_to_us;

                $secure_check        = $orderid . $m_id;
                $secure_check        = text_crypt($secure_check);
                $cidev_url_variables = "s=$secure_check&o=$orderid&m=$m_id";

                $mnfs[$m_id]['__items_table__']  = $cidev_items_table;
                $mnfs[$m_id]['__shipto_table__'] = $cidev_ship_to;
                $mnfs[$m_id]['__shipto_full_table__'] = $cidev_ship_to_full;

                $d_message_body_14 = $mv['d_message_body_14'];
                $d_message_body_14 = str_replace("\r\n", "<br />", $d_message_body_14);
                $d_message_body_14 = str_replace("{{items}}", $cidev_items_table, $d_message_body_14);
                $d_message_body_14 = str_replace("{{shipto}}", $cidev_ship_to, $d_message_body_14);
                $d_message_body_14 = str_replace("{{shipto_full_address}}", $cidev_ship_to_full, $d_message_body_14);
                $d_message_body_14 = str_replace("{{shipping_method}}", $order["shipping_groups"][$m_id]["shipping"], $d_message_body_14);
                $d_message_body_14 = str_replace("{{orderid}}", $order["order_prefix"] . $orderid, $d_message_body_14);
                $d_message_body_14 = str_replace("{{signature}}", $signature, $d_message_body_14);
                $d_message_body_14 = str_replace("{{userfirstname}}", $userfirstname, $d_message_body_14);
                $d_message_body_14 = str_replace("{{userfullname}}", $userfullname, $d_message_body_14);
                $cidev_page_url1 = "http://www.s3stores.com/stock_availability.php";

                $webpagebutton                    = "<a href='$cidev_page_url1?$cidev_url_variables'><img src='http://www.artistsupplysource.com/skin1_kolin/images/webpage_button.png' alt='Please click here to send us product availability information' /></a>";
                $d_message_body_14                = str_replace("{{webpagebutton}}", $webpagebutton, $d_message_body_14);
                $mnfs[$m_id]['d_message_body_14'] = $d_message_body_14;

                $d_email_subject_14 = $mv['d_email_subject_14'];
                $d_email_subject_14 = str_replace("{{items}}", $cidev_items_table, $d_email_subject_14);
                $d_email_subject_14 = str_replace("{{shipto}}", $cidev_ship_to, $d_email_subject_14);
                $d_email_subject_14 = str_replace("{{shipto_full_address}}", $cidev_ship_to_full, $d_email_subject_14);
                $d_email_subject_14 = str_replace("{{orderid}}", $order["order_prefix"] . $orderid, $d_email_subject_14);
                $d_email_subject_14 = str_replace("{{signature}}", $signature, $d_email_subject_14);
                $d_email_subject_14 = str_replace("{{userfirstname}}", $userfirstname, $d_email_subject_14);
                $d_email_subject_14 = str_replace("{{userfullname}}", $userfullname, $d_email_subject_14);

                $mnfs[$m_id]['d_email_subject_14'] = $d_email_subject_14;

                $cidev_page_url2 = "http://www.s3stores.com/index.php?pageid=42";
                $cidev_mess_body = "<a href='$cidev_page_url2&$cidev_url_variables'><img src='http://www.artistsupplysource.com/skin1_kolin/images/received_img.png' alt='Please click to confirm that you received this order'/></a>";
                $mess_body       = $mv['mess_body'];
                $mess_body       = str_replace("{{received}}", $cidev_mess_body, $mess_body);
                $mess_body       = str_replace("{{orderid}}", $order["order_prefix"] . $orderid, $mess_body);

                $oShipping          = \Xcart\Shipping::model(['shippingid' => $order["shipping_groups"][$m_id]["shippingid"]]);
                $orig_shipping_name = $oShipping->getName();

                $d_shipping_options_arr = [];

                if ($orig_shipping_name == "_USE_MY_UPS_FEDEX_ACCOUNT_") {
                    $d_shipping_options_arr[] = $order["shipping_groups"][$m_id]["shipping"];
                    $d_shipping_options_arr[] = "the least expensive shipping method";
                }
                elseif ($orig_shipping_name == "_USE_MY_TRUCKING_ACCOUNT_") {
                    $d_shipping_options_arr[] = $order["shipping_groups"][$m_id]["shipping"];
                    $d_shipping_options_arr[] = "the least expensive shipping method";
                }
                elseif (!empty($order["shipping_groups"][$m_id]["real_shipping_method"])
                        && $oShipping->getName() != $order["shipping_groups"][$m_id]["real_shipping_method"]
                ) {
                    $d_shipping_options_arr[] = "the least expensive shipping method";
                    $d_shipping_options_arr[] = $order["shipping_groups"][$m_id]["real_shipping_method"];
                    $d_shipping_options_arr[] = $oShipping->getName();
                }
                else {
                    $d_shipping_options_arr[] = "the least expensive shipping method";
                    $d_shipping_options_arr[] = $oShipping->getName();
                }

                if (!empty($mv["d_shipping_options"])) {
                    $d_shipping_options_arr_add = explode(",", $mv["d_shipping_options"]);
                    if (!empty($d_shipping_options_arr_add) && is_array($d_shipping_options_arr_add)) {
                        foreach ($d_shipping_options_arr_add as $k_s => $v_s) {
                            $additional_shipping_method = trim($v_s);
                            if (!empty($additional_shipping_method)) {
                                $d_shipping_options_arr[] = $additional_shipping_method;
                            }
                        }
                    }
                }

                $mnfs[$m_id]["d_shipping_options_arr"] = $d_shipping_options_arr;
                unset($d_shipping_options_arr);

                $order_products .= '<hr style="width:100%; margin: 5px 0 -5px 0; border: 0 none; border-bottom: 1px solid #999999;">S3 Stores, Inc.<br />Phone: ' . $config["Company"]["company_phone"] . '<br />Fax: ' . $config["Company"]["company_fax"] . '<br />URL: <a href="http://www.s3stores.com">www.s3stores.com</a>';
                $mess_body .= '<br />' . $order_products;

                $mess_body = str_replace("{{signature}}", $signature, $mess_body);
                $mess_body = str_replace("{{userfirstname}}", $userfirstname, $mess_body);
                $mess_body = str_replace("{{userfullname}}", $userfullname, $mess_body);

                $mnfs[$m_id]['mess_body'] = $mess_body;

                $mnfs[$m_id]['d_subject_line_8'] = str_replace("{{orderid}}", $order["order_prefix"] . $orderid, $mv["d_subject_line_8"]);
                $mnfs[$m_id]['d_subject_line_8'] = str_replace("{{signature}}", $signature, $mnfs[$m_id]['d_subject_line_8']);
                $mnfs[$m_id]['d_subject_line_8'] = str_replace("{{userfirstname}}", $userfirstname, $mnfs[$m_id]['d_subject_line_8']);
                $mnfs[$m_id]['d_subject_line_8'] = str_replace("{{userfullname}}", $userfullname, $mnfs[$m_id]['d_subject_line_8']);

                $mnfs[$m_id]['d_order_entry_operator_subject_line_8'] = str_replace("{{orderid}}", $order["order_prefix"] . $orderid, $mv["d_order_entry_operator_subject_line_8"]);
                $mnfs[$m_id]['d_order_entry_operator_subject_line_8'] = str_replace("{{signature}}", $signature, $mnfs[$m_id]['d_order_entry_operator_subject_line_8']);
                $mnfs[$m_id]['d_order_entry_operator_subject_line_8'] = str_replace("{{userfirstname}}", $userfirstname, $mnfs[$m_id]['d_order_entry_operator_subject_line_8']);
                $mnfs[$m_id]['d_order_entry_operator_subject_line_8'] = str_replace("{{userfullname}}", $userfullname, $mnfs[$m_id]['d_order_entry_operator_subject_line_8']);

                $mnfs[$m_id]['d_instructions_to_order_entry_operator'] = str_replace("{{orderid}}", $order["order_prefix"] . $orderid, $mv["d_instructions_to_order_entry_operator"]);

                $mnfs[$m_id]['d_instructions_to_order_entry_operator'] = str_replace("{{signature}}", $signature, $mnfs[$m_id]['d_instructions_to_order_entry_operator']);
                $mnfs[$m_id]['d_instructions_to_order_entry_operator'] = str_replace("{{userfirstname}}", $userfirstname, $mnfs[$m_id]['d_instructions_to_order_entry_operator']);
                $mnfs[$m_id]['d_instructions_to_order_entry_operator'] = str_replace("{{userfullname}}", $userfullname, $mnfs[$m_id]['d_instructions_to_order_entry_operator']);

                $grand_total                = $order['shipping_groups'][$m_id]["total"]["gross"];
                $mnfs[$m_id]["grand_total"] = $grand_total;

                $actual_shipping_cost                = $order['shipping_groups'][$m_id]["actual_shipping_cost"]["gross"];
                $mnfs[$m_id]["actual_shipping_cost"] = $actual_shipping_cost;

                $estimated_profit = (1 - $config["Additional_shipping_charge"]["credit_card_processing_fees"] / 100) * $grand_total - $config["Additional_shipping_charge"]["per_transaction"] - $total_product_cost_to_us - $actual_shipping_cost;
                $estimated_profit = price_format($estimated_profit);

                $mnfs[$m_id]["estimated_profit"] = $estimated_profit;
                if ($estimated_profit < 0) {
                    $mnfs[$m_id]["estimated_profit_abs"] = abs($estimated_profit);
                }

                if ($grand_total > 0) {
                    $estimated_profit_margin = $estimated_profit / ((1 - $config["Additional_shipping_charge"]["credit_card_processing_fees"] / 100) * $grand_total);
                }
                $estimated_profit_margin                = price_format($estimated_profit_margin);
                $mnfs[$m_id]["estimated_profit_margin"] = $estimated_profit_margin;

                $estimated_profit_margin_percent = $estimated_profit_margin * 100;
                $estimated_profit_margin_percent = intval($estimated_profit_margin_percent);

                $mnfs[$m_id]["estimated_profit_margin_percent"] = $estimated_profit_margin_percent;
                if ($estimated_profit_margin_percent < 0) {
                    $mnfs[$m_id]["estimated_profit_margin_percent_abs"] = abs($estimated_profit_margin_percent);
                }

                if ($order["shipping_groups"][$m_id]["shipping_value_selectbox"] == "required_shipping_charge") {
                    $required_shipping_charge = $order["shipping_groups"][$m_id]["actual_shipping_net"];
                }
                else {
                    $required_shipping_charge = $actual_shipping_cost * $config["Additional_shipping_charge"]["required_shipping_charge_k"];
                }

                $required_shipping_charge                = price_format($required_shipping_charge);
                $mnfs[$m_id]["required_shipping_charge"] = $required_shipping_charge;

                $additional_shipping_charge                = $required_shipping_charge - $order['shipping_groups'][$m_id]["shipping_cost"]["gross"];
                $additional_shipping_charge                = price_format($additional_shipping_charge);
                $mnfs[$m_id]["additional_shipping_charge"] = $additional_shipping_charge;

                $estimated_profit_after_additional_payment                = $estimated_profit + (1 - $config["Additional_shipping_charge"]["credit_card_processing_fees"] / 100) * $additional_shipping_charge - $config["Additional_shipping_charge"]["per_transaction"];
                $estimated_profit_after_additional_payment                = price_format($estimated_profit_after_additional_payment);
                $mnfs[$m_id]["estimated_profit_after_additional_payment"] = $estimated_profit_after_additional_payment;

                if ($estimated_profit_after_additional_payment < 0) {
                    $mnfs[$m_id]["estimated_profit_after_additional_payment_abs"] = abs($estimated_profit_after_additional_payment);
                }

                if (
                    ($grand_total + $additional_shipping_charge) > 0
                    && (1 - $config["Additional_shipping_charge"]["credit_card_processing_fees"] / 100) > 0
                ) {
                    $estimated_profit_margin_after_additional_payment = $estimated_profit_after_additional_payment / ((1 - $config["Additional_shipping_charge"]["credit_card_processing_fees"] / 100) * ($grand_total + $additional_shipping_charge));
                }

                $estimated_profit_margin_after_additional_payment                = price_format($estimated_profit_margin_after_additional_payment);
                $mnfs[$m_id]["estimated_profit_margin_after_additional_payment"] = $estimated_profit_margin_after_additional_payment;

                if ($estimated_profit_margin_after_additional_payment < 0) {
                    $mnfs[$m_id]["estimated_profit_margin_after_additional_payment_abs"] = abs($estimated_profit_margin_after_additional_payment);
                }

                $estimated_profit_margin_after_additional_payment_percent = $estimated_profit_margin_after_additional_payment * 100;
                $estimated_profit_margin_after_additional_payment_percent                = intval($estimated_profit_margin_after_additional_payment_percent);
                $mnfs[$m_id]["estimated_profit_margin_after_additional_payment_percent"] = $estimated_profit_margin_after_additional_payment_percent;
                if ($estimated_profit_margin_after_additional_payment < 0) {
                    $mnfs[$m_id]["estimated_profit_margin_after_additional_payment_percent_abs"] = abs($estimated_profit_margin_after_additional_payment_percent);
                }

                if (
                    empty($order_data["order"]["shipping_groups"][$m_id]["additional_shipping_status"])
                    && (
                        ($additional_shipping_charge < $config["Additional_shipping_charge"]["waive_additional_shipping_charge"])
                        || ($estimated_profit > $config["Additional_shipping_charge"]["estimated_profit"] && $estimated_profit_margin_percent > $config["Additional_shipping_charge"]["estimated_profit_margin"])
                    )
                ) {

                    db_query("UPDATE $sql_tbl[order_groups] SET additional_shipping_status='W' WHERE orderid='$orderid' AND manufacturerid='$m_id'");
                    $mnfs["reload_page"] = "Y";
                }

                if ($additional_shipping_charge > 0) {

                    if (!empty($order["po_details"]) && is_array($order["po_details"])) {
                        $additional_shipping_charge_message = $config["Additional_shipping_charge"]["po_message_body"];
                    }
                    else {
                        $additional_shipping_charge_message = $config["Additional_shipping_charge"]["message_body"];
                    }

                    $additional_shipping_charge_message = str_replace("{{orderid}}", $order["order_prefix"] . $orderid, $additional_shipping_charge_message);
                    $additional_shipping_charge_message = str_replace("{{required}}", "$" . price_format($required_shipping_charge), $additional_shipping_charge_message);
                    $additional_shipping_charge_message = str_replace("{{additional}}", "$" . price_format($additional_shipping_charge), $additional_shipping_charge_message);
                    $additional_shipping_charge_message = str_replace("{{fullname}}", $userinfo["firstname"], $additional_shipping_charge_message);
                    $additional_shipping_charge_message = str_replace("{{po_number}}", $order["po_number"], $additional_shipping_charge_message);
                    $additional_shipping_charge_message = str_replace("{{signature}}", $signature, $additional_shipping_charge_message);
                    $additional_shipping_charge_message = str_replace("{{userfirstname}}", $userfirstname, $additional_shipping_charge_message);
                    $additional_shipping_charge_message = str_replace("{{userfullname}}", $userfullname, $additional_shipping_charge_message);

                    $mnfs[$m_id]["additional_shipping_charge_message"] = $additional_shipping_charge_message;

                    if (!empty($order["po_details"]) && is_array($order["po_details"])) {
                        $additional_shipping_charge_subject_line = $config["Additional_shipping_charge"]["po_subject_line"];
                    }
                    else {
                        $additional_shipping_charge_subject_line = $config["Additional_shipping_charge"]["subject_line"];
                    }

                    $additional_shipping_charge_subject_line                = str_replace("{{orderid}}", $order["order_prefix"] . $orderid, $additional_shipping_charge_subject_line);
                    $additional_shipping_charge_subject_line                = str_replace("{{required}}", "$" . price_format($required_shipping_charge), $additional_shipping_charge_subject_line);
                    $additional_shipping_charge_subject_line                = str_replace("{{additional}}", "$" . price_format($additional_shipping_charge), $additional_shipping_charge_subject_line);
                    $additional_shipping_charge_subject_line                = str_replace("{{fullname}}", $userinfo["firstname"], $additional_shipping_charge_subject_line);
                    $additional_shipping_charge_subject_line                = str_replace("{{po_number}}", $order["po_number"], $additional_shipping_charge_subject_line);
                    $mnfs[$m_id]["additional_shipping_charge_subject_line"] = $additional_shipping_charge_subject_line;
                }

                $mnfs[$m_id]["d_website_search_for_sku_url"]         = str_replace("{{mpn}}", "---mpn---", $mnfs[$m_id]["d_website_search_for_sku_url"]);
                $mnfs[$m_id]["d_link_to_order_distributors_website"] = str_replace("{{orderid}}", $order["order_prefix"] . $orderid, $mnfs[$m_id]["d_link_to_order_distributors_website"]);

                $compose_email_to_distributor = "";
                if (!empty($mv["d_send_to_email_for_templates"])) {
                    $compose_email_to_distributor = $mv["d_send_to_email_for_templates"];
                }
                else {
                    if ($mv["submit_to_operator"] == "by_email_or_and_fax" && !empty($mv["email"])) {
                        $compose_email_to_distributor = $mv["email"];
                    }
                }
                $mnfs[$m_id]["compose_email_to_distributor"] = $compose_email_to_distributor;


                $tmp_cur_time_sec                  = time();
                $d_server_min_distributor_time_sec = $mv["d_server_min_distributor_time"] * 60 * 60;
                $tmp_cur_time_sec -= $d_server_min_distributor_time_sec;
                $mnfs[$m_id]["distributor_time"] = $tmp_cur_time_sec;
                $tmp_cur_time_date_format        = date("G.i", $tmp_cur_time_sec);
                $tmp_date_mm_dd_yyyy             = date("m/d/Y", $tmp_cur_time_sec);
                $tmp_number_of_day_of_week = date("w", $tmp_cur_time_sec); // 0 (for Sunday) through 6 (for Saturday)

                if ($tmp_cur_time_date_format >= "8.30" && $tmp_cur_time_date_format <= "16.30" && ($tmp_number_of_day_of_week != "0" && $tmp_number_of_day_of_week != "6")) {

                    if (!empty($request_availability_options) && is_array($request_availability_options)) {
                        foreach ($request_availability_options as $k_r => $v_r) {
                            if ($v_r["date_mm_dd_yyyy"] == $tmp_date_mm_dd_yyyy && $v_r["active"] == "Y") {
                                $good_time_to_send_email_to_distributor = "N";
                            }
                        }
                    }

                    if ($good_time_to_send_email_to_distributor != "N") {
                        $good_time_to_send_email_to_distributor = "Y";
                    }

                    $mnfs[$m_id]["good_time_to_send_email_to_distributor"] = $good_time_to_send_email_to_distributor;
                }
                else {
                    $mnfs[$m_id]["good_time_to_send_email_to_distributor"] = "N";
                }

                $mnfs[$m_id]["distributor_phone"] = func_query_first_cell("SELECT phone FROM $sql_tbl[distributor_contacts] WHERE manufacturerid='$m_id' AND phone!='' ORDER BY distributor_field_code asc LIMIT 1");

                $phone_normalized = preg_replace("/[^0-9]/S", "", $mnfs[$m_id]["distributor_phone"]);

                if (strlen($phone_normalized) == "10") {
                    $mnfs[$m_id]["distributor_phone_phone_normalized"] = "+1" . $phone_normalized;
                }
            }
        }
    }

    return $mnfs;
}

#
# This function change order status in orders table
#
function func_change_order_status($orderids, $status, $advinfo = "", $manufacturerid = '')
{
    global $config, $mail_smarty, $active_modules, $current_area;
    global $sql_tbl;
    global $session_failed_transaction;

    $allowed_order_status = func_query_column('SELECT code FROM ' . $sql_tbl['order_statuses']);

    if (!in_array($status, $allowed_order_status)) {
        return;
    }

    $status_type = func_query_first_cell('SELECT type FROM ' . $sql_tbl['order_statuses'] . ' WHERE code = "' . $status . '"');
    if (empty($status_type)) {
        return;
    }

    $status_column = strtolower($status_type) . '_status';

    if (!is_array($orderids)) $orderids = [$orderids];

    foreach ($orderids as $orderid) {
        $order_data = func_order_data($orderid);

        if (empty($order_data)) {
            continue;
        }

        if (defined('ORDERS_LIST_UPDATE') && !empty($order_data['goid'])) {
            continue;
        }

        $order = $order_data["order"];

        if (!empty($active_modules['Multiple_Storefronts']) && !empty($order['storefrontid'])) {
            $sf_info = func_get_storefront_info($order['storefrontid'], 'ID', true);
            if (is_array($sf_info) && !empty($sf_info)) {
                $mail_smarty->assign('sf_info', $sf_info);
            }
        }

        if ($advinfo) {
            $log         = "";
            $new_details = $order["details"] . "\n--- Advanced info ---\n" . $advinfo;

            $current_details = func_query_first_cell("SELECT details FROM $sql_tbl[orders] WHERE orderid='$orderid'");
            $current_details = text_decrypt($current_details);

            if ($current_details != $new_details) {
                $log = str_replace("\n", "<br />", $new_details);
                func_log_order($orderid, 'C', $log, $order["login"]);
            }
        }

        if ($advinfo) {
            $info = addslashes(text_crypt($order["details"] . "\n--- Advanced info ---\n" . $advinfo));
        }

        if ($status_column != "po_status" && $status_column != "ru_status") {
            db_query('UPDATE ' . $sql_tbl['orders'] . ' SET ' . $status_column . ' = "' . $status . '"'
                     . (($advinfo) ? ', details="' . $info . '"' : '')
                     . ' WHERE orderid = "' . $orderid . '"');
        }

        if (!empty($manufacturerid))
        {
            $addition_column = "";

            if ($status_column == "dc_status" && ($status == "C" || "L")) {

                $tmp_db_vals = func_query_first("SELECT dc_status, dc_received_by_distributor_time, dc_dispatched_time FROM $sql_tbl[order_groups] WHERE orderid='$orderid' AND manufacturerid='$manufacturerid'");

                if ($status == "C" && $tmp_db_vals["dc_status"] != "C" && empty($tmp_db_vals["dc_dispatched_time"])) {
                    $addition_column = ", dc_dispatched_time='" . time() . "'";
                }
                elseif ($status == "L" && $tmp_db_vals["dc_status"] != "L" && empty($tmp_db_vals["dc_received_by_distributor_time"])) {
                    $addition_column = ", dc_received_by_distributor_time='" . time() . "'";
                }
            }

            db_query("UPDATE $sql_tbl[order_groups] SET $status_column='$status' $addition_column WHERE orderid='$orderid' AND manufacturerid='$manufacturerid'");
        }
        else {
            db_query("UPDATE $sql_tbl[order_groups] SET $status_column='$status' WHERE orderid='$orderid'");
        }

        $send_notification = false;

        if (
            ($status == 'P' && $order['order_status'][$status_type] != 'P')
            || ($status == 'R' && $order['order_status'][$status_type] != 'R')
            || ($status == 'H' && $order['order_status'][$status_type] != 'H')
        ) {
            $flag = true;

            if (
                in_array($order['order_status'][$status_type], ['I', 'N', 'Q'])
                && !empty($active_modules['Anti_Fraud'])
                && $config['Anti_Fraud']['anti_fraud_license']
                && ($current_area != 'A' && $current_area != 'P')
                && !empty($order['extra']['Anti_Fraud'])
            ) {
                $total_trust_score = $order['extra']['Anti_Fraud']['total_trust_score'];
                $available_request = $order['extra']['Anti_Fraud']['available_request'];
                $used_request      = $order['extra']['Anti_Fraud']['used_request'];

                if ($total_trust_score > $config['Anti_Fraud']['anti_fraud_limit'] || ($available_request <= $used_request && $available_request > 0)) {
                    $flag = false;
                    db_query("UPDATE $sql_tbl[orders] SET $status_column = 'Q' WHERE orderid = '$orderid'");
                    $send_notification = true;
                }
            }

            if ($flag) {
                func_process_order($orderid);
            }
        }
        elseif (($status == 'D' && $order['order_status'][$status_type] != 'D'
                 || $status == 'A' && $order['order_status'][$status_type] != 'A')
                && $order['order_status'][$status_type] != 'F'
        ) {
            func_decline_order($orderid, $status);
        }
        elseif ($status == 'F' && $order['order_status'][$status_type] != 'F'
                && $order['order_status'][$status_type] != 'D'
                && $order['order_status'][$status_type] != 'A'
        ) {
            func_decline_order($orderid, $status);
            if ($current_area == 'C') {
                $session_failed_transaction++;
            }
        }
        elseif ($status == 'C' && $order['order_status'][$status_type] != 'C') {
            func_complete_order($orderid);
        }
        elseif ($status == 'N' && $order['order_status'][$status_type] == 'I') {
            func_not_paid_order($orderid);
        }
        elseif ($status == 'S' && ($order['order_status'][$status_type] != 'S' || defined('FORCE_SHIP_ORDER'))) {
            # Order has been shipped
            $send_notification      = 'S';
            $tracking_links         = func_query_hash("SELECT * FROM $sql_tbl[tracking_links]", 'linkid', false);
            $tracking_links_carrier = func_query_hash("SELECT * FROM $sql_tbl[tracking_links_carrier] ORDER BY orderby", 'carrier_id', false);
            $mail_smarty->assign("tracking_links", $tracking_links);
            $mail_smarty->assign("tracking_links_carrier", $tracking_links_carrier);
            $mail_smarty->assign("customer", $order_data["userinfo"]);
            $order_data['order']['order_status'][$status_type] = 'S';

        }
        elseif (
            ($status == 'Q' && $order['order_status'][$status_type] == 'I' && $current_area != 'A' && $current_area != 'P')
            || ($status == 'E' && $order['order_status'][$status_type] != 'E')
            || ($status == 'AP' && $order['order_status'][$status_type] != 'AP')
        ) {
            $send_notification = true;
        }

        #
        # Decrease quantity in stock when "declined" or "failed" order is became "completed", "processed" or "queued"
        #
        if (
            $status != $order['order_status'][$status_type]
            && strpos('ADF', $order['order_status']['CB']) !== false
            && strpos('SCPQIR', $status) !== false
        ) {
            func_update_quantity($order_data['products'], false);
        }

        if ($send_notification && (!SKIP_NOTIFICATION || !defined('SKIP_NOTIFICATION'))) {
            func_send_order_status_notification($orderid, $status);
        }
    }

    $op_message = "Login: $login\nIP: $REMOTE_ADDR\nOperation: change $status_column of orders (" . implode(',', $orderids) . ") to '$status'\n----";
    x_log_flag('log_orders_change_status', 'ORDERS', $op_message, true);
}

#
# This function performs activities nedded when order is processed
#
function func_process_order($orderids)
{
    global $config, $mail_smarty, $active_modules;
    global $sql_tbl, $partner, $to_customer;
    global $single_mode;
    global $xcart_dir;
    global $current_area, $statuses;

    if (empty($orderids)) {
        return false;
    }

    if (!is_array($orderids)) {
        $orderids = [$orderids];
    }

    if (!isset($statuses) || empty($statuses)) {
        $statuses = func_query_hash('SELECT code, name, type FROM ' . $sql_tbl['order_statuses']
                                    . ' ORDER BY orderby', ['type', 'code'], false, true);
    }

    foreach ($orderids as $orderid) {

        if (empty($orderid)) {
            continue;
        }

        $order_data = func_order_data($orderid);
        if (empty($order_data)) {
            continue;
        }

        $order     = $order_data["order"];
        $userinfo  = $order_data["userinfo"];
        $products  = $order_data["products"];
        $giftcerts = $order_data["giftcerts"];

        $mail_smarty->assign("customer", $userinfo);
        $mail_smarty->assign("products", $products);
        $mail_smarty->assign("giftcerts", $giftcerts);
        $mail_smarty->assign("order", $order);
        $mail_smarty->assign('statuses', $statuses);

        #
        # Order processing routine
        # Send gift certificates
        #
        if ($order["applied_giftcerts"]) {
            #
            # Search for enabled to applying GC
            #
            $flag = true;
            foreach ($order["applied_giftcerts"] as $k => $v) {
                $res = func_query_first("SELECT gcid FROM $sql_tbl[giftcerts] WHERE gcid='$v[giftcert_id]' AND debit>='$v[giftcert_cost]'");
                if (!$res["gcid"]) {
                    $flag = false;
                    break;
                }
            }

            #
            # Decrease debit for applied GC
            #
            if (!$flag) {
                return false;
            }

            foreach ($order["applied_giftcerts"] as $k => $v) {
                db_query("UPDATE $sql_tbl[giftcerts] SET debit=debit-'$v[giftcert_cost]' WHERE gcid='$v[giftcert_id]'");
                db_query("UPDATE $sql_tbl[giftcerts] SET status='A' WHERE debit>0 AND gcid='$v[giftcert_id]'");
                db_query("UPDATE $sql_tbl[giftcerts] SET status='U' WHERE debit<=0 AND gcid='$v[giftcert_id]'");
            }
        }

        if ($giftcerts) {
            foreach ($giftcerts as $giftcert) {
                db_query("update $sql_tbl[giftcerts] set status='A' where gcid='$giftcert[gcid]'");
                if ($giftcert["send_via"] == "E") {
                    func_send_gc($userinfo["email"], $giftcert, $userinfo['login']);
                }
            }
        }

        #
        # Send mail notifications
        #
        $aorder_notification = func_get_order_notification('P', $order_data);
        if (!empty($aorder_notification)) {
            foreach ($aorder_notification as $oOrderNotification) {
                if ($oOrderNotification->isEnabled()) {
                    $order_notification = $oOrderNotification->getFields();
                    if ($order_notification['enabled'] == 'Y' && (!SKIP_NOTIFICATION || !defined('SKIP_NOTIFICATION'))) {

                        $mail_smarty->assign('order_notification', $order_notification);

                        if ($config['Email_Note']['eml_order_p_notif_provider'] == 'Y') {
                            $providers = func_query("select provider from $sql_tbl[order_details] where $sql_tbl[order_details].orderid='$orderid' group by provider");

                            if (is_array($providers)) {
                                foreach ($providers as $provider) {
                                    $email_pro = func_query_first_cell("SELECT email FROM $sql_tbl[customers] WHERE login='$provider[provider]'");
                                    if (!empty($email_pro) && $email_pro != $config["Company"]["orders_department"]) {
                                        $to_customer = func_query_first_cell("SELECT language FROM $sql_tbl[customers] WHERE login='$provider[provider]'");
                                        if (empty($to_customer)) {
                                            $to_customer = $config['default_admin_language'];
                                        }

                                        $attach_pdf_invoice = $order_notification["admin_attach_pdf_invoice"];
                                        $mail_smarty->assign('attach_pdf_invoice', $attach_pdf_invoice);

                                        func_send_mail($email_pro, "mail/order_notification_subj.tpl", "mail/order_notification.tpl", $config["Company"]["orders_department"], false);
                                    }
                                }
                            }
                        }

                        $to_customer = func_query_first_cell("SELECT language FROM $sql_tbl[customers] WHERE login='$userinfo[login]'");
                        if (empty($to_customer)) {
                            $to_customer = $config['default_customer_language'];
                        }

                        if ($order_notification['enabled'] == 'Y') {

                            $mail_smarty->assign('type', 'C');

                            $mail_smarty->assign("products", func_translate_products($products, $to_customer));
                            $_userinfo           = $userinfo;
                            $userinfo['title']   = func_get_title($userinfo['titleid'], $to_customer);
                            $userinfo['b_title'] = func_get_title($userinfo['b_titleid'], $to_customer);
                            $userinfo['s_title'] = func_get_title($userinfo['s_titleid'], $to_customer);
                            $mail_smarty->assign("customer", $userinfo);

                            $attach_pdf_invoice = $order_notification["customer_attach_pdf_invoice"];
                            $mail_smarty->assign('attach_pdf_invoice', $attach_pdf_invoice);
                            func_send_mail($userinfo['email'], 'mail/order_notification_subj.tpl', 'mail/order_notification.tpl', $config['Company']['orders_department'], false, false, false, false, "", "N", $orderid);

                            $attach_pdf_invoice = $order_notification["admin_attach_pdf_invoice"];
                            $mail_smarty->assign('attach_pdf_invoice', $attach_pdf_invoice);
                            $mail_smarty->assign('type', 'A');
                            $to_customer = $config['default_admin_language'];

                            $to       = $config['Company']['orders_department'];
                            $from     = $userinfo["firstname"] . "<" . $config['Company']['orders_department'] . ">";
                            $reply_to = $userinfo["firstname"] . "<" . $userinfo['email'] . ">";

                            $oMail = \Xcart\App\Main\Xcart::app()->mail;
                            $oMail->to = $to;
                            $oMail->reply_to = $reply_to;
                            $oMail->from = $from;
                            $oMail->subject_template = 'mail/order_notification_subj.tpl';
                            $oMail->body_template = 'mail/order_notification.tpl';
                            $oMail->addHeader(['X-Xcart-Label' => 'order-status-changed']);
                            $oMail->sendEmail();
                            //func_send_mail($to, 'mail/order_notification_subj.tpl', 'mail/order_notification.tpl', $from, true, true, false, false, $reply_to);

                            $userinfo = $_userinfo;
                            unset($_userinfo);
                        }
                    }
                }
            }
        }

        $mail_smarty->assign("show_order_details", "");

        #
        # Send E-goods download keys
        #
        if (!empty($active_modules["Egoods"])) {
            include $xcart_dir . "/modules/Egoods/send_keys.php";
        }

        #
        # Update statistics for sold products
        #
        if ($active_modules["Advanced_Statistics"]) {
            include $xcart_dir . "/modules/Advanced_Statistics/prod_sold.php";
        }

        if (!empty($active_modules["SnS_connector"])) {
            global $_COOKIE;

            $_old                          = $_COOKIE;
            $_COOKIE['personal_client_id'] = $order['extra']['personal_client_id'];
            func_generate_sns_action("Order", $orderid);
            $_COOKIE = $_old;
        }

        if (!empty($active_modules['Survey']) && !empty($userinfo)) {
            func_check_surveys_events("OPP", $order_data, $userinfo['login']);
            func_check_surveys_events("OPB", $order_data, $userinfo['login']);
        }
    }
}

#
# This function performs activities nedded when order is complete
#
function func_complete_order($orderid)
{
    global $config, $mail_smarty, $active_modules;
    global $sql_tbl, $to_customer;
    global $xcart_dir, $statuses;

    $order_data = func_order_data($orderid);
    if (empty($order_data)) {
        return false;
    }

    $order     = $order_data["order"];
    $userinfo  = $order_data["userinfo"];
    $products  = $order_data["products"];
    $giftcerts = $order_data["giftcerts"];

    $mail_smarty->assign("products", $products);
    $mail_smarty->assign("giftcerts", $giftcerts);
    $mail_smarty->assign("order", $order);

    if (!empty($active_modules["Special_Offers"])) {
        include $xcart_dir . "/modules/Special_Offers/complete_order.php";
    }

    #
    # Send mail notifications
    #
    $aorder_notification = func_get_order_notification('C', $order_data);
    if (!empty($aorder_notification)) {
        foreach ($aorder_notification as $oOrderNotification) {
            if ($oOrderNotification->isEnabled()) {
                $order_notification = $oOrderNotification->getFields();
                if ($order_notification['enabled'] == 'Y' && (!SKIP_NOTIFICATION || !defined('SKIP_NOTIFICATION'))) {
                    $to_customer = func_query_first_cell("SELECT language FROM $sql_tbl[customers] WHERE login='$userinfo[login]'");
                    if (empty($to_customer)) {
                        $to_customer = $config['default_customer_language'];
                    }
                    $userinfo['title']   = func_get_title($userinfo['titleid'], $to_customer);
                    $userinfo['b_title'] = func_get_title($userinfo['b_titleid'], $to_customer);
                    $userinfo['s_title'] = func_get_title($userinfo['s_titleid'], $to_customer);
                    $mail_smarty->assign("customer", $userinfo);
                    $mail_smarty->assign("products", func_translate_products($products, $to_customer));
                    $mail_smarty->assign('statuses', $statuses);
                    $mail_smarty->assign('order_notification', $order_notification);
                    $mail_smarty->assign('type', "C");
                    $mail_smarty->assign('d_email_subject_14', "");
                    $mail_smarty->assign('attach_pdf_invoice', $order_notification["customer_attach_pdf_invoice"]);

                    func_send_mail($userinfo['email'], 'mail/order_notification_subj.tpl', 'mail/order_notification.tpl', $config['Company']['orders_department'], false, false, false, false, "", "N", $orderid);
                }
            }
        }
    }

    if (!empty($active_modules['Survey']) && !empty($userinfo)) {
        func_check_surveys_events("OPC", $order_data, $userinfo['login']);
        func_check_surveys_events("OPB", $order_data, $userinfo['login']);
    }
}

function func_not_paid_order($orderid)
{
    global $config, $mail_smarty, $active_modules;
    global $sql_tbl, $to_customer;
    global $xcart_dir, $statuses;

    $order_data = func_order_data($orderid);
    if (empty($order_data)) {
        return false;
    }

    $order     = $order_data["order"];
    $userinfo  = $order_data["userinfo"];
    $products  = $order_data["products"];
    $giftcerts = $order_data["giftcerts"];

    $mail_smarty->assign("products", $products);
    $mail_smarty->assign("giftcerts", $giftcerts);
    $mail_smarty->assign("order", $order);

    #
    # Send mail notifications
    #
    $aorder_notification = func_get_order_notification('N', $order_data);
    if (!empty($aorder_notification)) {
        foreach ($aorder_notification as $oOrderNotification) {
            if ($oOrderNotification->isEnabled()) {
                $order_notification = $oOrderNotification->getFields();

                if ($order_notification['enabled'] == 'Y' && (!SKIP_NOTIFICATION || !defined('SKIP_NOTIFICATION'))) {
                    $to_customer = func_query_first_cell("SELECT language FROM $sql_tbl[customers] WHERE login='$userinfo[login]'");
                    if (empty($to_customer)) {
                        $to_customer = $config['default_customer_language'];
                    }
                    $mail_smarty->assign('customer', $userinfo);
                    $mail_smarty->assign('products', func_translate_products($products, $to_customer));
                    $mail_smarty->assign('statuses', $statuses);
                    $mail_smarty->assign('order_notification', $order_notification);

                    $attach_pdf_invoice = $order_notification["customer_attach_pdf_invoice"];
                    $mail_smarty->assign('attach_pdf_invoice', $attach_pdf_invoice);

                    func_send_mail($userinfo['email'], 'mail/order_notification_subj.tpl', 'mail/order_notification.tpl', $config['Company']['orders_department'], false, false, false, false, "", "N", $orderid);
                }
            }
        }
    }
}

#
# This function performs activities nedded when order is declined
# status may be assign (D)ecline or (F)ail
# (D)ecline order sent mail to customer, (F)ail - not
#
function func_decline_order($orderids, $status = "D")
{
    global $config, $mail_smarty;
    global $sql_tbl, $to_customer, $statuses;

    if (!in_array($status, ['D', 'F', 'A'])) {
        return;
    }

    if (!is_array($orderids)) $orderids = [$orderids];

    foreach ($orderids as $orderid)
    {
        #
        # Order decline routine
        #
        $order_data = func_order_data($orderid);
        if (empty($order_data)) {
            continue;
        }

        $order     = $order_data["order"];
        $userinfo  = $order_data["userinfo"];
        $products  = $order_data["products"];
        $giftcerts = $order_data["giftcerts"];

        # Send mail notifications
        if ($status == 'D' || $status == 'A') {
            $mail_smarty->assign("customer", $userinfo);
            $mail_smarty->assign("products", $products);
            $mail_smarty->assign("giftcerts", $giftcerts);
            $mail_smarty->assign("order", $order);

            $aorder_notification = func_get_order_notification($status, $order_data);
            if (!empty($aorder_notification)) {
                foreach ($aorder_notification as $oOrderNotification) {
                    if ($oOrderNotification->isEnabled()) {
                        $order_notification = $oOrderNotification->getFields();

                        if ($order_notification['enabled'] == 'Y' && (!SKIP_NOTIFICATION || !defined('SKIP_NOTIFICATION'))) {
                            $to_customer = func_query_first_cell("SELECT language FROM $sql_tbl[customers] WHERE login='$userinfo[login]'");
                            if (empty($to_customer)) {
                                $to_customer = $config['default_customer_language'];
                            }

                            $mail_smarty->assign("products", func_translate_products($products, $to_customer));
                            $userinfo['title']   = func_get_title($userinfo['titleid'], $to_customer);
                            $userinfo['b_title'] = func_get_title($userinfo['b_titleid'], $to_customer);
                            $userinfo['s_title'] = func_get_title($userinfo['s_titleid'], $to_customer);
                            $mail_smarty->assign("customer", $userinfo);
                            $mail_smarty->assign('statuses', $statuses);
                            $mail_smarty->assign('order_notification', $order_notification);

                            $attach_pdf_invoice = $order_notification["customer_attach_pdf_invoice"];
                            $mail_smarty->assign('attach_pdf_invoice', $attach_pdf_invoice);

                            func_send_mail($userinfo['email'], 'mail/order_notification_subj.tpl', 'mail/order_notification.tpl', $config['Company']['orders_department'], false, false, false, false, "", "N", $orderid);
                        }
                    }
                }
            }
        }

        #
        # Discount restoring
        #
        $discount_coupon = $order["coupon"];
        if ($discount_coupon) {
            $_per_user = func_query_first_cell("SELECT per_user FROM $sql_tbl[discount_coupons] WHERE coupon='$discount_coupon' LIMIT 1");
            if ($_per_user == "Y") {
                db_query("UPDATE $sql_tbl[discount_coupons_login] SET times_used=IF(times_used>0, times_used-1, 0) WHERE coupon='$discount_coupon' AND login='" . $userinfo["login"] . "'");
            }
            else {
                db_query("UPDATE $sql_tbl[discount_coupons] SET status='A' WHERE coupon='$discount_coupon' and times_used=times");
                db_query("UPDATE $sql_tbl[discount_coupons] SET times_used=times_used-1 WHERE coupon='$discount_coupon'");
            }
            $discount_coupon = "";
        }

        #
        # Increase debit for declined GC
        #
        if ($order["applied_giftcerts"]) {
            foreach ($order["applied_giftcerts"] as $k => $v) {
                if (
                    $order['order_status']['CB'] == 'P'
                    || $order['order_status']['DC'] == 'C'
                    || $order['order_status']['CB'] == 'R'
                    || $order['order_status']['CB'] == 'H'
                ) {
                    db_query("UPDATE $sql_tbl[giftcerts] SET debit=debit+'$v[giftcert_cost]' WHERE gcid='$v[giftcert_id]'");
                }

                db_query("UPDATE $sql_tbl[giftcerts] SET status='A' WHERE debit>0 AND gcid='$v[giftcert_id]'");
            }
        }

        # Set GC's status to 'D'
        if ($giftcerts) {
            foreach ($giftcerts as $giftcert) {
                db_query("UPDATE $sql_tbl[giftcerts] SET status='D' WHERE gcid='$giftcert[gcid]'");
            }
        }

        if ($config["General"]["unlimited_products"] != "Y") {
            func_update_quantity($products);
        }
    }

    if (!empty($active_modules["SnS_connector"])) {
        global $_COOKIE;

        $_old                          = $_COOKIE;
        $_COOKIE['personal_client_id'] = $order['extra']['personal_client_id'];
        func_generate_sns_action("Order", $orderid);
        $_COOKIE = $_old;
    }
}

#
# This function sends GC emails (called from func_place_order
# and provider/order.php"
#
function func_send_gc($from_email, $giftcert, $from_login = '')
{
    global $mail_smarty, $config, $to_customer, $sql_tbl;

    $giftcert["purchaser_email"] = $from_email;
    $mail_smarty->assign("giftcert", $giftcert);

    #
    # Send notifs to $orders_department & purchaser
    #
    $html_add = "";
    if ($config['Email']['html_mail'] == "Y") {
        $html_add = "html/";
    }

    if (@$config['Gift_Certificates']['eml_giftcert_notif_purchaser'] == 'Y' && (@$config['Gift_Certificates']['eml_giftcert_notif_admin'] != 'Y' || $config["Company"]["orders_department"] != $from_email)) {
        if (!empty($from_login)) {
            $to_customer = func_query_first_cell("SELECT language FROM $sql_tbl[customers] WHERE login = '$from_login'");
            if (empty($to_customer)) {
                $to_customer = $config['default_customer_language'];
            }
        }

        func_send_mail($from_email, "mail/giftcert_notification_subj.tpl", "mail/" . $html_add . "giftcert_notification.tpl", $config["Company"]["orders_department"], false);
    }

    if (@$config['Gift_Certificates']['eml_giftcert_notif_admin'] == 'Y') {
        func_send_mail($config["Company"]["orders_department"], "mail/giftcert_notification_subj.tpl", "mail/" . $html_add . "giftcert_notification.tpl", $from_email, true);
    }

    #
    # Send GC to recipient
    #
    $to_customer = '';
    func_send_mail($giftcert["recipient_email"], "mail/giftcert_subj.tpl", "mail/" . $html_add . "giftcert.tpl", $from_email, false);
}

#
# Move products back to the inventory
#
function func_update_quantity($products, $increase = true)
{
    global $config, $sql_tbl, $active_modules;

    $symbol = ($increase ? "+" : "-");
    if ($config["General"]["unlimited_products"] != "Y" && is_array($products)) {
        $ids = [];
        foreach ($products as $product) {
            if ($product['product_type'] == 'C' && !empty($active_modules['Product_Configurator'])) {
                continue;
            }

            $variantid = "";
            if (!empty($active_modules['Product_Options']) && (!empty($product['extra_data']['product_options']) || !empty($product['options']))) {
                $options   = (!empty($product['extra_data']['product_options']) ? $product['extra_data']['product_options'] : $product['options']);
                $variantid = func_get_variantid($options);
            }

            if (!empty($variantid)) {
                db_query("UPDATE $sql_tbl[variants] SET avail=avail$symbol'$product[amount]' WHERE variantid = '$variantid'");
            }
            else {
                $egoods_cond = $active_modules["Egoods"] ? " AND distribution=''" : "";

                $products_quantity_behavior = func_query_first_cell("SELECT products_quantity_behavior FROM $sql_tbl[manufacturers] WHERE manufacturerid='$product[manufacturerid]'");

                if ($products_quantity_behavior == "R") {
                    db_query("UPDATE $sql_tbl[products] SET r_avail=r_avail$symbol'$product[amount]' WHERE productid='$product[productid]'" . $egoods_cond);
                }
                elseif ($products_quantity_behavior == "D") {
                }
                else {
                    db_query("UPDATE $sql_tbl[products] SET avail=avail$symbol'$product[amount]' WHERE productid='$product[productid]'" . $egoods_cond);
                }
            }

            $ids[$product['productid']] = true;
        }

        if (!empty($ids)) {
            func_build_quick_flags(array_keys($ids));
            func_build_quick_prices(array_keys($ids));
        }
    }
}

#
# This function removes orders and related info from the database
# $orders can be: 1) orderid; 2) orders array with orderid keys
function func_delete_order($orders, $update_quantity = true)
{
    global $sql_tbl, $xcart_dir;

    $_orders = [];

    if (is_array($orders)) {
        foreach ($orders as $order) {
            if (!empty($order["orderid"])) {
                $_orders[] = $order["orderid"];
            }
        }
    }
    elseif (is_numeric($orders)) {
        $_orders[] = $orders;
    }

    x_log_flag('log_orders_delete', 'ORDERS', "Login: $login\nIP: $REMOTE_ADDR\nOperation: delete orders (" . implode(',', $_orders) . ")", true);
    #
    # Update quantity of products
    #
    if ($update_quantity) {
        foreach ($_orders as $orderid) {
            $order_data = func_order_data($orderid);
            if (empty($order_data)) {
                continue;
            }

            if (strpos('IQ', $order_data['order']['order_status']['CB']) !== false) {
                func_update_quantity($order_data["products"]);
            }
        }
    }

    #
    # Delete orders from the database
    #
    $xaff = (func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[modules] WHERE module_name='XAffiliate'") > 0);
    $xrma = (func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[modules] WHERE module_name='RMA'") > 0);
    if ($xaff && !isset($sql_tbl['partner_payment'])) {
        @include_once $xcart_dir . "/modules/XAffiliate/config.php";
        if (!isset($sql_tbl['partner_payment'])) {
            $xaff = false;
        }
    }

    if ($xrma && !isset($sql_tbl['returns'])) {
        @include_once $xcart_dir . "/modules/RMA/config.php";
    }

    db_query("LOCK TABLES $sql_tbl[orders] WRITE, $sql_tbl[order_details] WRITE, $sql_tbl[order_groups] WRITE,"
             . " $sql_tbl[order_extras] WRITE, $sql_tbl[giftcerts] WRITE, $sql_tbl[subscription_customers] WRITE,"
             . " $sql_tbl[refund_groups] WRITE, $sql_tbl[refunded_products] WRITE"
             . ((@$xaff) ? ", $sql_tbl[partner_payment] WRITE, $sql_tbl[partner_product_commissions] WRITE, $sql_tbl[partner_adv_orders] WRITE" : '')
             . ((@$xrma) ? ", $sql_tbl[returns] WRITE" : ''));

    foreach ($_orders as $orderid) {
        $itemids = func_query("SELECT itemid FROM $sql_tbl[order_details] WHERE orderid='$orderid'");
        if (!empty($itemids)) {
            foreach ($itemids as $k => $v) {
                $itemids[$k] = $v['itemid'];
            }
        }

        db_query("DELETE FROM $sql_tbl[orders] WHERE orderid='$orderid'");
        db_query("DELETE FROM $sql_tbl[order_details] WHERE orderid='$orderid'");
        db_query("DELETE FROM $sql_tbl[order_groups] WHERE orderid='$orderid'");
        db_query("DELETE FROM $sql_tbl[order_extras] WHERE orderid='$orderid'");
        db_query("DELETE FROM $sql_tbl[refund_groups] WHERE orderid='$orderid'");
        db_query("DELETE FROM $sql_tbl[refunded_products] WHERE orderid='$orderid'");
        db_query("DELETE FROM $sql_tbl[giftcerts] WHERE orderid='$orderid'");
        if (@$xaff) {
            db_query("DELETE FROM $sql_tbl[partner_payment] WHERE orderid='$orderid'");
            db_query("DELETE FROM $sql_tbl[partner_product_commissions] WHERE orderid='$orderid'");
            db_query("DELETE FROM $sql_tbl[partner_adv_orders] WHERE orderid='$orderid'");
        }

        if (@$xrma && !empty($itemids)) {
            db_query("DELETE FROM $sql_tbl[returns] WHERE itemid IN ('" . implode("','", $itemids) . "')");
        }

        db_query("DELETE FROM $sql_tbl[subscription_customers] WHERE orderid='$orderid'");
    }

    #
    # Check if no orders in the database
    #
    $total_orders = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[orders]");
    if ($total_orders == 0) {
        #
        # Clear Order ID counter (auto increment field in the xcart_orders table)
        #
        db_query("DELETE FROM $sql_tbl[orders]");
        db_query("DELETE FROM $sql_tbl[order_details]");
        db_query("DELETE FROM $sql_tbl[order_groups]");
        db_query("DELETE FROM $sql_tbl[refund_groups]");
        db_query("DELETE FROM $sql_tbl[refunded_products]");

        if (@$xaff) {
            db_query("DELETE FROM $sql_tbl[partner_payment]");
        }

        db_query("DELETE FROM $sql_tbl[subscription_customers]");
    }

    db_query("UNLOCK TABLES");
}

function func_check_merchant_password($config_force = false)
{
    global $merchant_password, $current_area, $active_modules, $config;

    return ($merchant_password && ($current_area == 'A' || ($current_area == 'P' && $active_modules["Simple_Mode"])) && ($config['Security']['blowfish_enabled'] == 'Y' || $config_force));
}

#
# This function recrypts data with the Blowfish method.
#
function func_data_recrypt()
{
    global $sql_tbl;

    if (!func_check_merchant_password()) {
        return false;
    }

    $orders = db_query("SELECT orderid, details FROM $sql_tbl[orders] WHERE details NOT LIKE 'C%' AND details != ''");

    if (!$orders) {
        return true;
    }

    func_display_service_header("lbl_reencrypting_mkey");
    while ($order = db_fetch_array($orders)) {
        $details = text_decrypt($order['details']);
        $details = (is_string($details)) ? addslashes(func_crypt_order_details($details)) : "";

        func_array2update("orders", ["details" => $details], "orderid = '$order[orderid]'");
        func_flush(". ");
    }

    db_free_result($orders);

    return true;
}

#
# This function decrypts data Blowfish method -> Standart method.
#
function func_data_decrypt()
{
    global $sql_tbl;

    if (!func_check_merchant_password(true)) {
        return false;
    }

    $orders = db_query("SELECT orderid, details FROM $sql_tbl[orders] WHERE details LIKE 'C%'");
    if (!$orders) {
        return true;
    }

    func_display_service_header("lbl_reencrypting_skey");
    while ($order = db_fetch_array($orders)) {
        $details = text_decrypt($order['details']);
        $details = is_string($details) ? addslashes(text_crypt($details)) : "";

        func_array2update("orders", ["details" => $details], "orderid = '$order[orderid]'");
        func_flush(". ");
    }

    db_free_result($orders);

    return true;
}

#
# This function recrypts Blowfish-crypted data with new password
# where:
#	old_password - old Merchant password
function func_change_mpassword_recrypt($old_password)
{
    global $sql_tbl, $merchant_password;

    if (empty($old_password) || !func_check_merchant_password()) {
        return false;
    }

    $orders = db_query("SELECT orderid, details FROM $sql_tbl[orders] WHERE details != ''");
    if (!$orders) {
        return true;
    }

    $_merchant_password = $merchant_password;
    func_display_service_header("lbl_reencrypting_new_mkey");
    while ($order = db_fetch_array($orders)) {
        $merchant_password = $old_password;
        $details           = text_decrypt($order['details']);
        $merchant_password = $_merchant_password;
        $details           = is_string($details) ? addslashes(func_crypt_order_details($details)) : "";

        func_array2update("orders", ["details" => $details], "orderid = '$order[orderid]'");

        func_flush(". ");
    }

    db_free_result($orders);

    $merchant_password = $_merchant_password;

    return true;
}

#
# Encryption of the 'details' field of the orders table
#
function func_crypt_order_details($data)
{
    if (func_check_merchant_password()) {
        return text_crypt($data, "C");
    }

    return text_crypt($data);
}

#
# This function create file lock in temporaly directory
# It will return file descriptor, or false.
#
function func_lock($lockname, $ttl = 15, $cycle_limit = 0)
{
    global $file_temp_dir, $_lock_hash;

    if (empty($lockname)) {
        return false;
    }

    if (!empty($_lock_hash[$lockname])) {
        return $_lock_hash[$lockname];
    }

    $fname = $file_temp_dir . DIRECTORY_SEPARATOR . $lockname;

    # Generate current id
    $id                    = md5(uniqid(rand(0, substr(floor(func_microtime() * 1000), 3)), true));
    $_lock_hash[$lockname] = $id;

    $file_id = false;
    $limit   = $cycle_limit;
    while (($limit-- > 0 || $cycle_limit <= 0)) {
        if (!file_exists($fname)) {

            # Write locking data
            $fp = fopen($fname, "w");
            if ($fp) {
                fwrite($fp, $id . time());
                fclose($fp);
            }
        }

        $fp = fopen($fname, "r");
        if (!$fp) {
            return false;
        }

        $tmp = fread($fp, 43);
        fclose($fp);

        $file_id   = substr($tmp, 0, 32);
        $file_time = substr($tmp, 32);

        if ($file_id == $id) {
            break;
        }

        if ($ttl > 0 && time() > $file_time + $ttl) {
            @unlink($fname);
            continue;
        }

        sleep(1);
    }

    return $file_id == $id ? $id : false;
}

#
# This function releases file lock which is previously created by func_lock
#
function func_unlock($lockname)
{
    global $file_temp_dir, $_lock_hash;

    if (empty($lockname)) {
        return false;
    }

    if (empty($_lock_hash[$lockname])) {
        return false;
    }

    $fname = $file_temp_dir . DIRECTORY_SEPARATOR . $lockname;
    if (!file_exists($fname)) {
        return false;
    }

    $fp = fopen($fname, "r");
    if (!$fp) {
        return false;
    }

    $tmp = fread($fp, 43);
    fclose($fp);

    $file_id   = substr($tmp, 0, 32);
    $file_time = substr($tmp, 32);

    if ($file_id == $_lock_hash[$lockname]) {
        @unlink($fname);
    }

    func_unset($_lock_hash, $lockname);

    return true;
}

#
# Translate products names to local product names
#
function func_translate_products($products, $code)
{
    global $sql_tbl;

    if (!is_array($products) || empty($products) || empty($code)) {
        return $products;
    }

    $hash = [];
    foreach ($products as $k => $p) {
        $hash[$p['productid']][] = $k;
    }

    if (empty($hash)) {
        return $products;
    }

    foreach ($hash as $pid => $keys) {
        $local = func_query_first("SELECT product, descr, fulldescr FROM $sql_tbl[products_lng] WHERE productid = '$pid' AND code = '$code'");
        if (empty($local) || !is_array($local)) {
            continue;
        }

        foreach ($keys as $k) {
            $products[$k] = func_array_merge($products[$k], preg_grep("/\S/S", $local));
        }
    }

    return $products;
}

#
# This function defines internal fields for storing sensitive information in order details
#
function func_order_details_fields($all = false)
{
    global $store_cc, $store_ch, $store_cvv2;
    static $all_fields = [
        "CC"     => [
            "card_name"       => "{CardOwner}",
            "card_type"       => "{CardType}",
            "card_number"     => "{CardNumber}",
            "card_valid_from" => "{ValidFrom}",
            "card_expire"     => "{ExpDate}",
            "card_issue_no"   => "{IssueNumber}",
        ],
        "CC_EXT" => [
            "card_cvv2" => "CVV2",
        ],
        "CH"     => [
            # ACH
            "check_name"         => "{AccountOwner}",
            "check_ban"          => "{BankAccount}",
            "check_brn"          => "{BankNumber}",
            "check_number"       => "{FractionNumber}",
            # Direct Debit
            "debit_name"         => "{AccountOwner}",
            "debit_bank_account" => "{BankAccount}",
            "debit_bank_number"  => "{BankNumber}",
            "debit_bank_name"    => "{BankName}",
        ],
    ];

    $keys = [];
    if ($store_cc || $all) {
        $keys[] = "CC";
        if ($store_cvv2 || $all) $keys[] = "CC_EXT";
    }

    if ($store_ch || $all) $keys[] = "CH";

    $rval = [];
    foreach ($keys as $key) {
        $rval = func_array_merge($rval, $all_fields[$key]);
    }

    return $rval;
}

#
# Convert {CardName} => value of lbl_payment_CardName language variable
#
function func_order_details_fields_as_labels($force = false)
{
    $rval = [];
    foreach (func_order_details_fields(true) as $field) {
        if (preg_match('!^\{(.*)\}$!S', $field, $sublabel)) {
            $rval[$field] = func_get_langvar_by_name('lbl_payment_' . $sublabel[1], null, false, $force);
        }
    }

    return $rval;
}

#
# Remove sensitive information from order details
#
function func_order_remove_ccinfo($order_details, $save_4_digits)
{
    static $find_re = [
        1 => ['/^\{(?:CardOwner|CardType|ExpDate)\}:.*$/mS', '/^CVV2:.*$/mS'],
        0 => ['/^\{(?:CardOwner|CardType|CardNumber|ExpDate)\}:.*$/mS', '/^CVV2:.*$/mS'],
    ];

    $save_4_digits = (int)((bool)$save_4_digits); # can use only 0 & 1

    $order_details = preg_replace($find_re[$save_4_digits], "", $order_details);

    if ($save_4_digits) {
        if (preg_match_all("/^(\{CardNumber\}:)(.*)$/mS", $order_details, $all_matches)) {
            foreach ($all_matches[2] as $matchn => $cardnum) {
                $cardnum       = trim($cardnum);
                $order_details = str_replace(
                    $all_matches[0][$matchn],
                    $all_matches[1][$matchn] . " " . str_repeat("*", strlen($cardnum) - 4) . substr($cardnum, -4),
                    $order_details);
            }
        }
    }

    return $order_details;
}

#
# Replace all occurences of {Label} by corresponding language variable
#
function func_order_details_translate($order_details, $force = false)
{
    static $labels = [];
    global $shop_language;

    if (empty($labels[$shop_language])) {
        $labels[$shop_language] = func_order_details_fields_as_labels($force);
    }

    $order_details = str_replace(
        array_keys($labels[$shop_language]),
        array_values($labels[$shop_language]),
        $order_details);

    return $order_details;
}

#
# Send order status notification
#
function func_send_order_status_notification($orderid, $status, $force_send_email = false)
{
    global $sql_tbl, $mail_smarty, $config, $statuses, $attach_pdf_invoice, $xcart_dir;

    $order_data = func_order_data($orderid);

    $aorder_notification = func_get_order_notification($status, $order_data);
    $oOrder              = new Xcart\Order(['orderid' => $orderid]);
    $mail_smarty->assign('oOrder', $oOrder);
    if (!empty($aorder_notification)) {
        foreach ($aorder_notification as $oOrderNotification) {
            if ($oOrderNotification->isEnabled()) {
                $order_notification = $oOrderNotification->getFields();

                $mail_smarty->assign('order_notification', $order_notification);

                $tracking_links         = func_query_hash('SELECT * FROM ' . $sql_tbl['tracking_links'], 'linkid', false);
                $tracking_links_carrier = func_query_hash("SELECT * FROM $sql_tbl[tracking_links_carrier] ORDER BY orderby", 'carrier_id', false);
                if (!empty($tracking_links)) {
                    $mail_smarty->assign('tracking_links', $tracking_links);
                    $mail_smarty->assign('tracking_links_carrier', $tracking_links_carrier);
                }

                # Send notification to customer
                $to_customer = (($order_data['userinfo']['language']) ? $order_data['userinfo']['language'] : $config['default_customer_language']);
                $mail_smarty->assign('products', func_translate_products($order_data['products'], $to_customer));
                $mail_smarty->assign('giftcerts', $order_data['giftcerts']);
                $mail_smarty->assign('order', $order_data['order']);
                $mail_smarty->assign('userinfo', $order_data['userinfo']);
                if (empty($statuses)) {
                    $statuses = func_query_hash('SELECT code, name, type FROM ' . $sql_tbl['order_statuses']
                                                . ' ORDER BY orderby', ['type', 'code'], false, true);
                }
                $mail_smarty->assign('statuses', $statuses);

                $attach_pdf_invoice = $order_notification["customer_attach_pdf_invoice"];
                $mail_smarty->assign('attach_pdf_invoice', $attach_pdf_invoice);
                $mail_smarty->assign('cidev_hide_invoice', $order_notification["customer_hide_invoice"]);

                $mail_smarty->assign('type', 'C');
                func_send_mail($order_data['userinfo']['email'], 'mail/order_notification_subj.tpl', 'mail/order_notification.tpl', $config['Company']['orders_department'], false, false, false, false, "", "N", $orderid);
                $mail_smarty->assign('type', 'A');

                $attach_pdf_invoice = $order_notification["admin_attach_pdf_invoice"];
                $mail_smarty->assign('attach_pdf_invoice', $attach_pdf_invoice);
                $mail_smarty->assign('cidev_hide_invoice', $order_notification["admin_hide_invoice"]);

                $to       = $config['Company']['orders_department'];
                $from     = $order_data['userinfo']['firstname'] . "<" . $config['Company']['orders_department'] . ">";
                $reply_to = $order_data['userinfo']['firstname'] . "<" . $order_data['userinfo']['email'] . ">";

                if ($_POST["send_email"] == "Y" || $force_send_email) {
                    $oMail = \Xcart\App\Main\Xcart::app()->mail;
                    $oMail->to = $to;
                    $oMail->from = $from;
                    $oMail->reply_to = $reply_to;
                    $oMail->subject_template = 'mail/order_notification_subj.tpl';
                    $oMail->body_template = 'mail/order_notification.tpl';
                    $oMail->addHeader(['X-Xcart-Label' => 'order-status-changed']);
                    $oMail->sendEmail();
                }
                //func_send_mail($to, 'mail/order_notification_subj.tpl', 'mail/order_notification.tpl', $from, false, false, false, false, $reply_to);
            }
        }
    }
    if ($status == $config['retail_trust_order_status'] && count($oOrder->getOrderDetailsWithRetailTrust()) > 0) {
        $oMail = new Xcart\OrderStatusNotification();
        $oMail->setOrder($oOrder)->setBody($config['Retail_Trust']['retail_trust_message'])->replaceBody();
        $oMail->setSubject($config['Retail_Trust']['retail_trust_subject'])->replaceSubject();
        $mail_smarty->assign('body', $oMail->getEmailBody());
        $mail_smarty->assign('subject', $oMail->getSubject());
        $mail_smarty->assign('type', 'C');
        func_send_mail($order_data['userinfo']['email'], "mail/compose_message_subj.tpl", "mail/compose_message.tpl", $config['Company']['orders_department'], false, false, false, false, "", "N", $orderid, true, true);
        $mail_smarty->assign('type', 'A');
        $to       = $config['Company']['orders_department'];
        $from     = $order_data['userinfo']['firstname'] . "<" . $config['Company']['orders_department'] . ">";
        $reply_to = $order_data['userinfo']['firstname'] . "<" . $order_data['userinfo']['email'] . ">";
        $oMail->setSubject($config['Retail_Trust']['retail_trust_bcc_subject'])->replaceSubject();
        $mail_smarty->assign('subject', $oMail->getSubject());
        func_send_mail($to, "mail/compose_message_subj.tpl", "mail/compose_message.tpl", $from, false, false, false, false, $reply_to, 'N', false, true, true);
    }
}

function func_get_order_status_type($status)
{
    global $sql_tbl;

    $status_type = func_query_first_cell('SELECT type FROM ' . $sql_tbl['order_statuses'] . ' WHERE code = "' . $status . '"');

    return (empty($status_type)) ? 'CB' : $status_type;
}

#
# Changes the status of the order group (only from A and P areas).
# Checks if other order groups have the same status.
# If yes or this is the only order group then changes the status of the whole order.
#

function func_change_order_group_status($orderid, $mid, $status)
{
    global $config, $sql_tbl;

    if (empty($mid)) {
        return;
    }

    $allowed_order_status = func_query_column('SELECT code FROM ' . $sql_tbl['order_statuses']);

    if (!in_array($status, $allowed_order_status)) {
        return;
    }

    $status_type = func_query_first_cell('SELECT type FROM ' . $sql_tbl['order_statuses'] . ' WHERE code = "' . $status . '"');
    if (empty($status_type)) {
        return;
    }

    $status_column = strtolower($status_type) . '_status';

    $order_groups = func_get_shipping_groups($orderid);

    if (isset($order_groups[$mid])) {
        $order_group = $order_groups[$mid];
        unset($order_groups);
    }

    if (!empty($order_group)) {

        $current_status = func_query_first_cell("SELECT $status_column FROM $sql_tbl[order_groups] WHERE orderid='$orderid' AND manufacturerid='$mid'");
        if ($current_status != $status) {
            $current_status_value = func_query_first_cell("SELECT name FROM $sql_tbl[order_statuses] WHERE code='$current_status'");
            $new_status_value     = func_query_first_cell("SELECT name FROM $sql_tbl[order_statuses] WHERE code='$status'");
            $code                 = func_query_first_cell("SELECT code FROM $sql_tbl[manufacturers] WHERE manufacturerid='$mid'");
            $log                  = "<B>" . $code . ":</B> " . $status_column . ": " . $current_status_value . " -> " . $new_status_value;
            global $login;
            func_log_order($orderid, 'X', $log, $login);
        }

        db_query('UPDATE ' . $sql_tbl['order_groups'] . ' SET ' . $status_column . '="' . $status . '"'
                 . ' WHERE orderid="' . $orderid . '" AND manufacturerid="' . $mid . '"');

        if (
            (($status == 'D' && $order_group[$status_column] != 'F')
             || ($status == 'F' && $order_group[$status_column] != 'D')
             || ($status == 'N' && $order_group[$status_column] == 'I')
             || !in_array($status, ['D', 'F', 'N']))
            && $order_group[$status_column] != $status
            && (!defined('SKIP_NOTIFICATION') || !SKIP_NOTIFICATION)
        ) {
            func_send_order_status_notification($orderid, $status);
        }
    }
    // Check other groups. If all groups have the same status - change the status of the order

    $og_statuses = func_query_column('SELECT ' . $status_column . ' FROM ' . $sql_tbl['order_groups']
                                     . ' WHERE orderid = "' . $orderid . '"');

    if (is_array($og_statuses)) {

        $change_order_status = true;

        foreach ($og_statuses as $ogs) {
            if ($ogs != $status) {
                $change_order_status = false;
            }
        }

        if ($change_order_status) {
            define('SKIP_NOTIFICATION', true);
            func_change_order_status($orderid, $status);
        }
    }
}

function func_set_filled_option($accounting)
{
    if (is_array($accounting)) {
        foreach ($accounting as $key => $ac) {
            $ac['gst']   = intval($ac['gst']);
            $ac['pst']   = intval($ac['pst']);
            $ac['gross'] = intval($ac['gross']);
            if (
                !empty($ac['gst'])
                || !empty($ac['pst'])
                || !empty($ac['gross'])
            ) {
                $accounting[$key]['filled'] = 'Y';

                // If filled == 'Y' for "COST TO US" column than filled = 'Y' for "SHIPPING" column

                if ($key == ACC_COST_TO_US) {
                    $accounting[ACC_SHIPPING]['filled'] = 'Y';
                }
            }
        }
    }

    return $accounting;
}

function func_get_order_notification($status, $order_data = "")
{
    $oOrder              = null;
    $aOrderNotifications = Xcart\OrderStatusNotification::getOrderStatusNotificationsByCode($status);

    if (!empty($aOrderNotifications))
    {
        if (!empty($order_data) && is_array($order_data)) {
            $oOrder = new Xcart\Order(['orderid' => $order_data['order']['orderid']]);
        }
        foreach ($aOrderNotifications as &$oOrderNotification) {
            $oOrderNotification->setOrder($oOrder)->replaceBody();
            $oOrderNotification->replaceSubject();
        }
    }

    return $aOrderNotifications;
}

function func_has_backordered_status(&$groups)
{
    $has_b_status = false;

    if (is_array($groups)) {
        foreach ($groups as $g => $group) {
            if (in_array($group['dc_status'], ['B', 'G', 'S'])) {
                foreach ($group['products'] as $key => $product) {
                    $groups[$g]['products'][$key]['ship'] = $product['amount'] - $product['back'];
                }
                if ($group['dc_status'] != 'S') {
                    $has_b_status = true;
                }
            }
        }
    }

    return $has_b_status;
}

function func_calculate_fee($order_price, $refund_price)
{
    $order_price  = func_convert_number($order_price);
    $refund_price = func_convert_number($refund_price);

    if ($order_price == 0) {
        return false;
    }

    $fee = round((1 - $refund_price / $order_price) * 100);

    return $fee;
}

function func_get_refund_groups($orderid, $sfid)
{
    global $sql_tbl;

    if (empty($orderid)) {
        return [];
    }

    $groups = func_query_hash('SELECT * FROM ' . $sql_tbl['refund_groups']
                              . ' WHERE orderid = "' . $orderid . '"', 'manufacturerid', false, false);

    if (!empty($groups))
    {
        $group_ids = array_keys($groups);

        $fields = [
            'r.orderid', 'r.manufacturerid', 'r.productid', 'r.ref_price', 'r.ref_qty', 'r.extra_data',
            'r.productid AS pid', 'r.manufacturerid AS mid', 'r.itemid',
        ];


        $products = func_query_hash('SELECT ' . implode(', ', $fields) . ' FROM ' . $sql_tbl['refunded_products'] . ' AS r'
                                    . ' WHERE r.manufacturerid IN ("' . implode('", "', $group_ids) . '")'
                                    . ' AND r.orderid = "' . $orderid . '"', ['mid', 'itemid'], false, false);

        if (!empty($products) && is_array($products))
        {
            foreach ($products as $mid => $v)
            {
                foreach ($v as $pid => $product)
                {
                    $product['storefrontid']             = $sfid;
                    $products[$mid][$pid]['itemid']      = $pid;
                    $products[$mid][$pid]['links']       = func_get_product_link_sf($product['productid'], $product['storefrontid']);
                    $products[$mid][$pid]['extra_data']  = unserialize($product['extra_data']);
                    $products[$mid][$pid]['product']     = $products[$mid][$pid]['extra_data']['product'];
                    $products[$mid][$pid]['productcode'] = $products[$mid][$pid]['extra_data']['productcode'];
                    $products[$mid][$pid]['price']       = $products[$mid][$pid]['extra_data']['price'];
                    $products[$mid][$pid]['fee']         = func_calculate_fee($products[$mid][$pid]['price'], $product['ref_price']);
                }
            }
        }

        foreach ($groups as $mid => $group)
        {
            $groups[$mid]['products']   = $products[$mid];
            $groups[$mid]['tracking']   = unserialize($group['tracking']);
            $groups[$mid]['accounting'] = func_make_accounting('', '', $group);
            $groups[$mid]['extra_data'] = unserialize($group['extra_data']);
        }

        return $groups;
    }

    return [];
}

function func_get_filter($fid)
{
    global $sql_tbl;

    $filter = func_query_first("SELECT * FROM $sql_tbl[filter_presets] WHERE fid='$fid'");

    if (empty($filter)) {
        return false;
    }

    if (!empty($filter["preset_position"])) {
        $preset_position_arr = explode(",", $filter["preset_position"]);
        $row                 = $preset_position_arr[0];
        $column              = $preset_position_arr[1];
    }

    $filter['row']    = $row;
    $filter['column'] = $column;

    $filter['distributors']              = func_query_column("SELECT manufacturerid FROM $sql_tbl[filter_preset_distributors] WHERE fid='$fid'");
    $filter['product_question_statuses'] = func_query_column("SELECT pq_status FROM $sql_tbl[filter_preset_product_question_statuses] WHERE fid='$fid'");
    $filter['storefront_ids']            = func_query_column("SELECT storefrontid FROM $sql_tbl[filter_preset_storefronts] WHERE fid='$fid'");
    $filter['fraud_statuses']            = func_query_column("SELECT fraud_status FROM $sql_tbl[filter_preset_fraud_statuses] WHERE fid='$fid'");
    $filter['po_statuses']               = func_query_column("SELECT status FROM $sql_tbl[filter_preset_po_statuses] WHERE fid='$fid'");
    $attention_tags_values               = func_query_column("SELECT status_id FROM $sql_tbl[filter_preset_attention_tag_statuses] WHERE fid='$fid'");

    if (!empty($attention_tags_values)) {
        $filter['attention_tags_values'] = func_query_column("SELECT status_id FROM $sql_tbl[filter_preset_attention_tag_statuses] WHERE fid='$fid'");
    }

    $country_codes_tmp = func_query("SELECT country_code FROM $sql_tbl[filter_preset_ship_to_country] WHERE fid=$fid");
    $country_codes     = [];

    if (!empty($country_codes_tmp)) {
        foreach ($country_codes_tmp as $v) {
            $country_codes[] = $v["country_code"];
        }
    }

    $filter['ship_to_countries'] = $country_codes;
    $statuses = func_query_hash("SELECT ps.status, s.type FROM $sql_tbl[filter_preset_statuses] ps LEFT JOIN $sql_tbl[order_statuses] s ON ps.status=s.code WHERE fid='$fid'", 'type', true, true);
    $filter = array_merge($filter, $statuses);

    return $filter;
}

function func_instock_and_outofstock_items_table($products, $type_of_message = '')
{

    $cidev_instock_items_table = '<table width="500px" border="1" cellpadding="5" cellspacing="0" bordercolor="#414236" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #000000; line-height: 18px;">';
    $cidev_instock_items_table .= '<tr><td width="150px" style="text-align: left; font-weight: bold;">Item number</td><td width="250px" style="text-align: left; font-weight: bold;">Item name</td><td style="text-align: right; font-weight: bold;" nowrap="nowrap">Quantity in stock</td></tr>';

    $cidev_outofstock_items_table = '<table width="500px" border="1" cellpadding="5" cellspacing="0" bordercolor="#414236" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #000000; line-height: 18px;">';
    $cidev_outofstock_items_table .= '<tr><td width="150px" style="text-align: left; font-weight: bold;">Item number</td><td width="250px" style="text-align: left; font-weight: bold;">Item name</td><td style="text-align: right; font-weight: bold;" nowrap="nowrap">Quantity out of stock</td><td style="text-align: right; font-weight: bold;" nowrap="nowrap" width="150px">ETA date</td></tr>';

    $cidev_outofstock_items_eta_table = '<table width="500px" border="1" cellpadding="5" cellspacing="0" bordercolor="#414236" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #000000; line-height: 18px;">';
    $cidev_outofstock_items_eta_table .= '<tr><td width="150px" style="text-align: left; font-weight: bold;">Item number</td><td width="250px" style="text-align: left; font-weight: bold;">Item name</td><td style="text-align: right; font-weight: bold;" nowrap="nowrap">Quantity required</td><td style="text-align: right; font-weight: bold;" nowrap="nowrap" width="150px">ETA date to ship all items</td></tr>';

    $cidev_discontinued_items_table = '<table width="500px" border="1" cellpadding="5" cellspacing="0" bordercolor="#414236" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #000000; line-height: 18px;">';
    $cidev_discontinued_items_table .= '<tr><td width="150px" style="text-align: left; font-weight: bold;">Item number</td><td width="250px" style="text-align: left; font-weight: bold;">Item name</td><td style="text-align: right; font-weight: bold;" nowrap="nowrap">Quantity discontinued</td><td style="text-align: right; font-weight: bold;" nowrap="nowrap" width="150px">ETA date</td></tr>';

    $count_eta_unknown   = 0;
    $count_eta_with_date = 0;
    $count_instock_items = 0;

    $count_out_of_stock_items                               = 0;
    $count_out_of_stock_products_with_eta_unknown           = 0;
    $count_out_of_stock_products_with_eta_with_date         = 0;
    $count_discontinued_products_with_empty_offer_backorder = 0;
    $count_outofstock_products_with_offer_backorder_Y       = 0;

    $is_instock_items      = "";
    $is_back               = "";
    $is_discontinued_items = false;

    $outofstock_products_info   = [];
    $discontinued_products_info = [];
    $counter_arr_info           = 0;

    if (!empty($products) && is_array($products))
    {
        foreach ($products as $k => $v)
        {
            $selected_product_options = "";
            if (!empty($v["product_options"]) && is_array($v["product_options"])) {
                foreach ($v["product_options"] as $kk => $vv) {
                    $selected_product_options .= "<br />" . $vv["option_name"];
                }
            }

            global $xcart_dir;
            $tmp_sku       = \Xcart\Product::model(['productid' => $v['productid']])->getMPN();
            $instock_items = $v["amount"] - $v["back"];

            $current_product_instock = false;

            if ($instock_items > 0) {

                $current_product_instock = true;
                $count_instock_items++;
                $is_instock_items = "Y";
                $cidev_instock_items_table .= '<tr><td width="150px" style="text-align: left;">' . $tmp_sku . '</td><td width="250px" style="text-align: left;"><a data-mce-href="' . $v["links"]["customer"] . '" href="' . $v["links"]["customer"] . '">' . $v["product"] . '</a>' . $selected_product_options . '</td><td style="text-align: right;">' . $instock_items . '</td></tr>';
            }

            if ($v["back"] > 0) {
                $is_back = "Y";

                $count_out_of_stock_items++;

                if ($v["forsale"] == "N") {
                    $tmp_eta_date_mm_dd_yyyy = "discontinued";

                    $count_eta_unknown++;

                    if (!$current_product_instock) {
                        $count_out_of_stock_products_with_eta_unknown++;
                    }
                }
                else {
                    $tmp_eta_date_mm_dd_yyyy = $v["eta_date_mm_dd_yyyy"];

                    if (!empty($tmp_eta_date_mm_dd_yyyy))
                    {
                        $tmp_eta_date_mm_dd_yyyy = date("j-M-Y", $tmp_eta_date_mm_dd_yyyy);

                        if ($type_of_message == 'backorder_decision_request') {
                            $tmp_time_diff = $tmp_mktime - time();
                            $tmp_time_diff = $tmp_time_diff / (60 * 60 * 24);

                            if ($tmp_time_diff > 30 && $v["offer_backorder"] != "Y") {
                                $tmp_eta_date_mm_dd_yyyy = "unknown";
                                $count_eta_unknown++;

                                if (!$current_product_instock) {
                                    $count_out_of_stock_products_with_eta_unknown++;
                                }
                            }
                        }
                    }
                    else {
                        if ($type_of_message == 'backorder_decision_request') {
                            $tmp_eta_date_mm_dd_yyyy = "unknown";
                            $count_eta_unknown++;

                            if (!$current_product_instock) {
                                $count_out_of_stock_products_with_eta_unknown++;
                            }
                        }
                    }

                    if ($tmp_eta_date_mm_dd_yyyy != "unknown") {
                        $count_eta_with_date++;
                        $count_out_of_stock_products_with_eta_with_date++;
                    }
                }

                if ($v["offer_backorder"] == "Y" || $type_of_message == "compose_message_page") {
                    $cidev_outofstock_items_table .= '<tr><td width="150px" style="text-align: left;">' . $tmp_sku . '</td><td width="250px" style="text-align: left;"><a data-mce-href="' . $v["links"]["customer"] . '" href="' . $v["links"]["customer"] . '">' . $v["product"] . '</a>' . $selected_product_options . '</td><td style="text-align: right;">' . $v["back"] . '</td><td style="text-align: right;" nowrap="nowrap">' . $tmp_eta_date_mm_dd_yyyy . '</td></tr>';

                    $count_outofstock_products_with_offer_backorder_Y++;

                    $cidev_outofstock_items_eta_table .= '<tr><td width="150px" style="text-align: left;">' . $tmp_sku . '</td><td width="250px" style="text-align: left;"><a data-mce-href="' . $v["links"]["customer"] . '" href="' . $v["links"]["customer"] . '">' . $v["product"] . '</a>' . $selected_product_options . '</td><td style="text-align: right;">' . $v["amount"] . '</td><td style="text-align: right;" nowrap="nowrap">' . $tmp_eta_date_mm_dd_yyyy . '</td></tr>';

                    $outofstock_products_info[$counter_arr_info]["productid"] = $v["productid"];
                    $outofstock_products_info[$counter_arr_info]["product"]   = $v["product"];
                }
                else {
                    $cidev_discontinued_items_table .= '<tr><td width="150px" style="text-align: left;">' . $tmp_sku . '</td><td width="250px" style="text-align: left;"><a data-mce-href="' . $v["links"]["customer"] . '" href="' . $v["links"]["customer"] . '">' . $v["product"] . '</a>' . $selected_product_options . '</td><td style="text-align: right;">' . $v["back"] . '</td><td style="text-align: right;" nowrap="nowrap">Unknown</td></tr>';
                    $is_discontinued_items = true;
                    $count_discontinued_products_with_empty_offer_backorder++;

                    $discontinued_products_info[$counter_arr_info]["productid"] = $v["productid"];
                    $discontinued_products_info[$counter_arr_info]["product"]   = $v["product"];
                }

                $counter_arr_info++;
            }
        }
    }

    if (empty($is_instock_items)) {
        $cidev_instock_items_table = '<tr><td colspan="3" style="text-align: center">no items in stock</td></tr>';
    }

    if (empty($count_outofstock_products_with_offer_backorder_Y)) {
        $cidev_outofstock_items_table     = '<tr><td colspan="4" style="text-align: center">no out of stock items</td></tr>';
        $cidev_outofstock_items_eta_table = '<tr><td colspan="4" style="text-align: center">no out of stock items</td></tr>';
    }

    if (!$is_discontinued_items) {
        $cidev_discontinued_items_table = '<tr><td colspan="4" style="text-align: center">no discontinued items</td></tr>';
    }

    $cidev_instock_items_table .= "</table>";
    $cidev_outofstock_items_table .= "</table>";
    $cidev_outofstock_items_eta_table .= "</table>";
    $cidev_discontinued_items_table .= "</table>";

    $instock_and_outofstock_items_table["instock"]        = $cidev_instock_items_table;
    $instock_and_outofstock_items_table["outofstock"]     = $cidev_outofstock_items_table;
    $instock_and_outofstock_items_table["outofstock_eta"] = $cidev_outofstock_items_eta_table;
    $instock_and_outofstock_items_table["discontinued"]   = $cidev_discontinued_items_table;

    if (!empty($outofstock_products_info)) {
        $instock_and_outofstock_items_table["outofstock_products_info"] = $outofstock_products_info;
    }

    if (!empty($discontinued_products_info)) {
        $instock_and_outofstock_items_table["discontinued_products_info"] = $discontinued_products_info;
    }

    $instock_and_outofstock_items_table["additional_info"]["count_instock_items"]                                    = $count_instock_items;
    $instock_and_outofstock_items_table["additional_info"]["count_eta_unknown"]                                      = $count_eta_unknown;
    $instock_and_outofstock_items_table["additional_info"]["count_eta_with_date"]                                    = $count_eta_with_date;
    $instock_and_outofstock_items_table["additional_info"]["count_out_of_stock_items"]                               = $count_out_of_stock_items;
    $instock_and_outofstock_items_table["additional_info"]["count_out_of_stock_products_with_eta_with_date"]         = $count_out_of_stock_products_with_eta_with_date;
    $instock_and_outofstock_items_table["additional_info"]["count_out_of_stock_products_with_eta_unknown"]           = $count_out_of_stock_products_with_eta_unknown;
    $instock_and_outofstock_items_table["additional_info"]["count_outofstock_products_with_offer_backorder_Y"]       = $count_outofstock_products_with_offer_backorder_Y;
    $instock_and_outofstock_items_table["additional_info"]["count_discontinued_products_with_empty_offer_backorder"] = $count_discontinued_products_with_empty_offer_backorder;

    return $instock_and_outofstock_items_table;
}

function func_alt_products_table($alt_products)
{

    $alt_products_str = '<table width="500px" border="1" cellpadding="5" cellspacing="0" bordercolor="#414236" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #000000; line-height: 18px;"><tr><td width="150px" style="text-align: left; font-weight: bold;">Item number</td><td width="*"><B>Item name</B></td></tr>';
    foreach ($alt_products as $k => $v) {

        $item_number_arr = explode("-", $v["productcode"]);
        array_shift($item_number_arr);
        $item_number = implode("-", $item_number_arr);

        $alt_products_str .= '<tr><td>' . $item_number . '</td><td><a href="' . $v["url"] . '">' . $v["product"] . '</a></td></tr>';
    }
    $alt_products_str .= '</table>';

    return $alt_products_str;
}

function func_make_accounting($orderid = '', $manufacturerid = '', $group = "", $tbl = "order_groups")
{
    global $sql_tbl;

    if (empty($group) && !empty($orderid) && !empty($manufacturerid)) {

        $table = $sql_tbl[$tbl];

        $group = func_query_first("SELECT accounting_net_0, accounting_gst_0, accounting_pst_0, accounting_gross_0, accounting_filled_0, accounting_net_1_cost_to_us, accounting_gst_1_cost_to_us, accounting_pst_1_cost_to_us, accounting_gross_1_cost_to_us, accounting_filled_1_cost_to_us, accounting_net_2_shipping, accounting_gst_2_shipping, accounting_pst_2_shipping, accounting_gross_2_shipping, accounting_filled_2_shipping, accounting_net_3_ref_to_cust, accounting_gst_3_ref_to_cust, accounting_pst_3_ref_to_cust, accounting_gross_3_ref_to_cust, accounting_filled_3_ref_to_cust, accounting_net_4_ref_to_us, accounting_gst_4_ref_to_us, accounting_pst_4_ref_to_us, accounting_gross_4_ref_to_us, accounting_filled_4_ref_to_us, accounting_net_5_profit, accounting_gst_5_profit, accounting_pst_5_profit, accounting_gross_5_profit, accounting_filled_5_profit FROM $table WHERE orderid='$orderid' AND manufacturerid='$manufacturerid'");
    }

    if (!empty($group) && is_array($group)) {

        $accounting[0]["net"]    = $group["accounting_net_0"];
        $accounting[0]["gst"]    = $group["accounting_gst_0"];
        $accounting[0]["pst"]    = $group["accounting_pst_0"];
        $accounting[0]["gross"]  = $group["accounting_gross_0"];
        $accounting[0]["filled"] = $group["accounting_filled_0"];

        $accounting[1]["net"]    = $group["accounting_net_1_cost_to_us"];
        $accounting[1]["gst"]    = $group["accounting_gst_1_cost_to_us"];
        $accounting[1]["pst"]    = $group["accounting_pst_1_cost_to_us"];
        $accounting[1]["gross"]  = $group["accounting_gross_1_cost_to_us"];
        $accounting[1]["filled"] = $group["accounting_filled_1_cost_to_us"];

        $accounting[2]["net"]    = $group["accounting_net_2_shipping"];
        $accounting[2]["gst"]    = $group["accounting_gst_2_shipping"];
        $accounting[2]["pst"]    = $group["accounting_pst_2_shipping"];
        $accounting[2]["gross"]  = $group["accounting_gross_2_shipping"];
        $accounting[2]["filled"] = $group["accounting_filled_2_shipping"];

        $accounting[3]["net"]    = $group["accounting_net_3_ref_to_cust"];
        $accounting[3]["gst"]    = $group["accounting_gst_3_ref_to_cust"];
        $accounting[3]["pst"]    = $group["accounting_pst_3_ref_to_cust"];
        $accounting[3]["gross"]  = $group["accounting_gross_3_ref_to_cust"];
        $accounting[3]["filled"] = $group["accounting_filled_3_ref_to_cust"];

        $accounting[4]["net"]    = $group["accounting_net_4_ref_to_us"];
        $accounting[4]["gst"]    = $group["accounting_gst_4_ref_to_us"];
        $accounting[4]["pst"]    = $group["accounting_pst_4_ref_to_us"];
        $accounting[4]["gross"]  = $group["accounting_gross_4_ref_to_us"];
        $accounting[4]["filled"] = $group["accounting_filled_4_ref_to_us"];

        $accounting[5]["net"]    = $group["accounting_net_5_profit"];
        $accounting[5]["gst"]    = $group["accounting_gst_5_profit"];
        $accounting[5]["pst"]    = $group["accounting_pst_5_profit"];
        $accounting[5]["gross"]  = $group["accounting_gross_5_profit"];
        $accounting[5]["filled"] = $group["accounting_filled_5_profit"];
    }

    return $accounting;
}

function func_add_accounting_fields($query_data, $group = '', $orderid = '', $manufacturerid = '', $tbl = "order_groups", $accounting_arr = '')
{
    global $sql_tbl;

    if (!empty($accounting_arr) && is_array($accounting_arr)) {

        $group["accounting_net_0"]    = $accounting_arr[0]["net"];
        $group["accounting_gst_0"]    = $accounting_arr[0]["gst"];
        $group["accounting_pst_0"]    = $accounting_arr[0]["pst"];
        $group["accounting_gross_0"]  = $accounting_arr[0]["gross"];
        $group["accounting_filled_0"] = $accounting_arr[0]["filled"];

        $group["accounting_net_1_cost_to_us"]    = $accounting_arr[1]["net"];
        $group["accounting_gst_1_cost_to_us"]    = $accounting_arr[1]["gst"];
        $group["accounting_pst_1_cost_to_us"]    = $accounting_arr[1]["pst"];
        $group["accounting_gross_1_cost_to_us"]  = $accounting_arr[1]["gross"];
        $group["accounting_filled_1_cost_to_us"] = $accounting_arr[1]["filled"];

        $group["accounting_net_2_shipping"]    = $accounting_arr[2]["net"];
        $group["accounting_gst_2_shipping"]    = $accounting_arr[2]["gst"];
        $group["accounting_pst_2_shipping"]    = $accounting_arr[2]["pst"];
        $group["accounting_gross_2_shipping"]  = $accounting_arr[2]["gross"];
        $group["accounting_filled_2_shipping"] = $accounting_arr[2]["filled"];

        $group["accounting_net_3_ref_to_cust"]    = $accounting_arr[3]["net"];
        $group["accounting_gst_3_ref_to_cust"]    = $accounting_arr[3]["gst"];
        $group["accounting_pst_3_ref_to_cust"]    = $accounting_arr[3]["pst"];
        $group["accounting_gross_3_ref_to_cust"]  = $accounting_arr[3]["gross"];
        $group["accounting_filled_3_ref_to_cust"] = $accounting_arr[3]["filled"];

        $group["accounting_net_4_ref_to_us"]    = $accounting_arr[4]["net"];
        $group["accounting_gst_4_ref_to_us"]    = $accounting_arr[4]["gst"];
        $group["accounting_pst_4_ref_to_us"]    = $accounting_arr[4]["pst"];
        $group["accounting_gross_4_ref_to_us"]  = $accounting_arr[4]["gross"];
        $group["accounting_filled_4_ref_to_us"] = $accounting_arr[4]["filled"];

        $group["accounting_net_5_profit"]    = $accounting_arr[5]["net"];
        $group["accounting_gst_5_profit"]    = $accounting_arr[5]["gst"];
        $group["accounting_pst_5_profit"]    = $accounting_arr[5]["pst"];
        $group["accounting_gross_5_profit"]  = $accounting_arr[5]["gross"];
        $group["accounting_filled_5_profit"] = $accounting_arr[5]["filled"];
    }

    if (empty($group) && !empty($orderid) && !empty($manufacturerid)) {

        $table = $sql_tbl[$tbl];

        $group = func_query_first("SELECT accounting_net_0, accounting_gst_0, accounting_pst_0, accounting_gross_0, accounting_filled_0, accounting_net_1_cost_to_us, accounting_gst_1_cost_to_us, accounting_pst_1_cost_to_us, accounting_gross_1_cost_to_us, accounting_filled_1_cost_to_us, accounting_net_2_shipping, accounting_gst_2_shipping, accounting_pst_2_shipping, accounting_gross_2_shipping, accounting_filled_2_shipping, accounting_net_3_ref_to_cust, accounting_gst_3_ref_to_cust, accounting_pst_3_ref_to_cust, accounting_gross_3_ref_to_cust, accounting_filled_3_ref_to_cust, accounting_net_4_ref_to_us, accounting_gst_4_ref_to_us, accounting_pst_4_ref_to_us, accounting_gross_4_ref_to_us, accounting_filled_4_ref_to_us, accounting_net_5_profit, accounting_gst_5_profit, accounting_pst_5_profit, accounting_gross_5_profit, accounting_filled_5_profit FROM $table WHERE orderid='$orderid' AND manufacturerid='$manufacturerid'");
    }

    if (!empty($group) && is_array($group)) {
        $query_data["accounting_net_0"]    = $group["accounting_net_0"];
        $query_data["accounting_gst_0"]    = $group["accounting_gst_0"];
        $query_data["accounting_pst_0"]    = $group["accounting_pst_0"];
        $query_data["accounting_gross_0"]  = $group["accounting_gross_0"];
        $query_data["accounting_filled_0"] = $group["accounting_filled_0"];

        $query_data["accounting_net_1_cost_to_us"]    = $group["accounting_net_1_cost_to_us"];
        $query_data["accounting_gst_1_cost_to_us"]    = $group["accounting_gst_1_cost_to_us"];
        $query_data["accounting_pst_1_cost_to_us"]    = $group["accounting_pst_1_cost_to_us"];
        $query_data["accounting_gross_1_cost_to_us"]  = $group["accounting_gross_1_cost_to_us"];
        $query_data["accounting_filled_1_cost_to_us"] = $group["accounting_filled_1_cost_to_us"];

        $query_data["accounting_net_2_shipping"]    = $group["accounting_net_2_shipping"];
        $query_data["accounting_gst_2_shipping"]    = $group["accounting_gst_2_shipping"];
        $query_data["accounting_pst_2_shipping"]    = $group["accounting_pst_2_shipping"];
        $query_data["accounting_gross_2_shipping"]  = $group["accounting_gross_2_shipping"];
        $query_data["accounting_filled_2_shipping"] = $group["accounting_filled_2_shipping"];

        $query_data["accounting_net_3_ref_to_cust"]    = $group["accounting_net_3_ref_to_cust"];
        $query_data["accounting_gst_3_ref_to_cust"]    = $group["accounting_gst_3_ref_to_cust"];
        $query_data["accounting_pst_3_ref_to_cust"]    = $group["accounting_pst_3_ref_to_cust"];
        $query_data["accounting_gross_3_ref_to_cust"]  = $group["accounting_gross_3_ref_to_cust"];
        $query_data["accounting_filled_3_ref_to_cust"] = $group["accounting_filled_3_ref_to_cust"];

        $query_data["accounting_net_4_ref_to_us"]    = $group["accounting_net_4_ref_to_us"];
        $query_data["accounting_gst_4_ref_to_us"]    = $group["accounting_gst_4_ref_to_us"];
        $query_data["accounting_pst_4_ref_to_us"]    = $group["accounting_pst_4_ref_to_us"];
        $query_data["accounting_gross_4_ref_to_us"]  = $group["accounting_gross_4_ref_to_us"];
        $query_data["accounting_filled_4_ref_to_us"] = $group["accounting_filled_4_ref_to_us"];

        $query_data["accounting_net_5_profit"]    = $group["accounting_net_5_profit"];
        $query_data["accounting_gst_5_profit"]    = $group["accounting_gst_5_profit"];
        $query_data["accounting_pst_5_profit"]    = $group["accounting_pst_5_profit"];
        $query_data["accounting_gross_5_profit"]  = $group["accounting_gross_5_profit"];
        $query_data["accounting_filled_5_profit"] = $group["accounting_filled_5_profit"];
    }

    return $query_data;
}

function func_get_rma_info($rma_id)
{
    global $sql_tbl;

    $rma_info = func_query_first("SELECT * FROM $sql_tbl[rmas] WHERE rma_id='$rma_id'");

    if (!empty($rma_info)) {
        $rma_info["products"] = func_query_hash("SELECT * FROM $sql_tbl[rma_details] WHERE rma_id='$rma_id'", 'itemid', false);

        $rma_info["images"] = func_query("SELECT * FROM $sql_tbl[images_R] WHERE id='$rma_id'");
    }

    return $rma_info;
}

function func_other_customer_orders($email, $orderid = 0)
{
    global $sql_tbl, $smarty;

    $other_customer_orders = func_query("SELECT orderid, order_prefix, fraud_status FROM $sql_tbl[orders] WHERE email='$email' AND orderid!='$orderid' ORDER BY orderid DESC");

    if (!empty($other_customer_orders))
    {
        $count_Completed = 0;
        $count_Fraud     = 0;
        $count_Open      = 0;

        foreach ($other_customer_orders as $k => $v) {

            $Completed = "";
            $Fraud     = "";
            $Open      = "";

            $order_groups_info = func_query("SELECT cb_status, dc_status FROM $sql_tbl[order_groups] WHERE orderid='$v[orderid]'");
            if (!empty($order_groups_info)) {
                foreach ($order_groups_info as $kk => $vv) {

                    if (in_array($v["fraud_status"], ["C", "E"]) && $vv["cb_status"] == "P" && $vv["dc_status"] == "S") {
                        $Completed = "Y";
                        $count_Completed++;
                    }
                    elseif (!in_array($v["fraud_status"], ["C", "E", "U", "T", "N"])) {
                        $Fraud = "Y";
                        $count_Fraud++;
                    }
                    elseif (in_array($v["fraud_status"], ["C", "E"]) && in_array($vv["cb_status"], ['N', 'O', 'P', 'Q', 'IO', 'F', 'I', 'AP']) && in_array($vv["dc_status"], ['M', 'T', 'K', 'B', 'DP', 'L', 'C', 'E'])) {
                        $Open = "Y";
                        $count_Open++;
                    }
                }
            }

            $other_customer_orders[$k]["statuses"]["Completed"] = $Completed;
            $other_customer_orders[$k]["statuses"]["Fraud"]     = $Fraud;
            $other_customer_orders[$k]["statuses"]["Open"]      = $Open;
        }

        $smarty->assign("count_Completed", $count_Completed);
        $smarty->assign("count_Fraud", $count_Fraud);
        $smarty->assign("count_Open", $count_Open);

        $smarty->assign("other_customer_orders", $other_customer_orders);

        $count_other_customer_orders = count($other_customer_orders);
        $smarty->assign("count_other_customer_orders", $count_other_customer_orders);

        $show_count_before_see_more = 5;
        $smarty->assign("show_count_before_see_more", $show_count_before_see_more);

        if ($count_other_customer_orders > $show_count_before_see_more) {
            $show_see_more = "Y";
        }
        else {
            $show_see_more = "N";
        }
        $smarty->assign("show_see_more", $show_see_more);
    }
}

#
## PayPal functions
###
function func_paypal_get_access_token()
{
    global $config;

    $USERPWD_username_ClientId = $config['Paypal_API']['live_client_id'];
    $USERPWD_password_Secret   = $config['Paypal_API']['live_secret_key'];
    $paypalUrl                 = "https://api.paypal.com";

    if ($config['Paypal_API']['debug_mode'] == "Y") {
        $USERPWD_username_ClientId = $config['Paypal_API']['sandbox_client_id'];
        $USERPWD_password_Secret   = $config['Paypal_API']['sandbox_secret_key'];
        $paypalUrl                 = "https://api.sandbox.paypal.com";
    }

    $url = $paypalUrl . "/v1/oauth2/token";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json", "Accept-Language: en_US"]);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); //  CURLAUTH_BASIC|CURLAUTH_DIGEST
    curl_setopt($ch, CURLOPT_USERPWD, "$USERPWD_username_ClientId:$USERPWD_password_Secret");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result_json = curl_exec($ch);
    $info        = curl_getinfo($ch);
    curl_close($ch);
    $result = json_decode($result_json, true);

    if (!empty($result["access_token"])) {
        return $result["access_token"];
    }
    else {
        return false;
    }
}

/*
Capture an authorization. (Authorization_Id - it is TransID)

Use this resource to capture and process a previously created authorization. To use this resource, the original payment call must have the intent set to authorize.
*/
function func_paypal_capture($Access_Token, $Authorization_Id, $data_arr)
{

    $data_json = json_encode($data_arr);

    global $config;

    $paypalUrl = "https://api.paypal.com";
    if ($config['Paypal_API']['debug_mode'] == "Y") {
        $paypalUrl = "https://api.sandbox.paypal.com";
    }

    $url = $paypalUrl . "/v1/payments/authorization/$Authorization_Id/capture";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:application/json", "Authorization: Bearer $Access_Token"]);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result_json            = curl_exec($ch);
    $result                 = json_decode($result_json, true);
    $result["curl_getinfo"] = curl_getinfo($ch);
    curl_close($ch);

    return $result;
}

// Refund transaction
function func_paypal_refund($Access_Token, $Authorization_Id, $data_arr)
{

    $data_json = json_encode($data_arr);

    global $config;
    $paypalUrl = "https://api.paypal.com";
    if ($config['Paypal_API']['debug_mode'] == "Y") {
        $paypalUrl = "https://api.sandbox.paypal.com";
    }
    $url = $paypalUrl . "/v1/payments/sale/$Authorization_Id/refund";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:application/json", "Authorization: Bearer $Access_Token"]);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result_json            = curl_exec($ch);
    $result                 = json_decode($result_json, true);
    $result["curl_getinfo"] = curl_getinfo($ch);
    curl_close($ch);

    return $result;
}

/*
Void an authorization. (Authorization_Id - it is TransID)

Use this call to void a previously authorized payment.
*/
function func_paypal_void($Access_Token, $Authorization_Id)
{

    global $config;
    $paypalUrl = "https://api.paypal.com";
    if ($config['Paypal_API']['debug_mode'] == "Y") {
        $paypalUrl = "https://api.sandbox.paypal.com";
    }
    $url = $paypalUrl . "/v1/payments/authorization/$Authorization_Id/void";
    $ch  = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:application/json", "Authorization: Bearer $Access_Token"]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result_json            = curl_exec($ch);
    $result                 = json_decode($result_json, true);
    $result["curl_getinfo"] = curl_getinfo($ch);
    curl_close($ch);

    return $result;
}

/*
Reauthorize a payment. (Authorization_Id - it is TransID)
Use this call to reauthorize a PayPal account payment. We recommend that you reauthorize a payment after the initial 3-day honor period to ensure that funds are still available.
You can reauthorize a payment only once 4 to 29 days after 3-day honor period for the original authorization expires. If 30 days have passed from the original authorization, you must create a new authorization instead. A reauthorized payment itself has a new 3-day honor period. You can reauthorize a transaction once for up to 115% of the originally authorized amount, not to exceed an increase of $75 USD
*/
function func_paypal_reauthorize($Access_Token, $Authorization_Id, $data_arr)
{

    $data_json = json_encode($data_arr);
    global $config;
    $paypalUrl = "https://api.paypal.com";
    if ($config['Paypal_API']['debug_mode'] == "Y") {
        $paypalUrl = "https://api.sandbox.paypal.com";
    }
    $url = $paypalUrl . "/v1/payments/authorization/$Authorization_Id/reauthorize";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:application/json", "Authorization: Bearer $Access_Token"]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result_json            = curl_exec($ch);
    $result                 = json_decode($result_json, true);
    $result["curl_getinfo"] = curl_getinfo($ch);
    curl_close($ch);

    return $result;
}

/*
Create a payment
Depending on the payment_method and the funding_instrument, you can use the payment resource for direct credit card payments, stored credit card payments, or PayPal account payments.
*/
function func_paypal_create_payment($Access_Token, $data_json)
{

    global $config;
    $paypalUrl = "https://api.paypal.com";
    if ($config['Paypal_API']['debug_mode'] == "Y") {
        $paypalUrl = "https://api.sandbox.paypal.com";
    }
    $url = $paypalUrl . "/v1/payments/payment";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:application/json", "Authorization: Bearer $Access_Token"]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result_json            = curl_exec($ch);
    $result                 = json_decode($result_json, true);
    $result["curl_getinfo"] = curl_getinfo($ch);
    curl_close($ch);

    return $result;
}

// Look up a payment
function func_paypal_look_up_payment($Access_Token, $Authorization_Id, $transaction_type)
{
/*
transaction_types:
 payment - Use this call to get details about payments that have not completed, such as payments that are created and approved, or if a payment has failed.
 sale - Use this call to get details about a sale transaction. (This call returns only the sales that were created via the REST API.)
 refund - Use this call to get details about a specific refund.
 authorization - Use this call to get details about authorizations.
 capture - Use this call to get details about a captured payment.
*/

    global $config;
    $paypalUrl = "https://api.paypal.com";
    if ($config['Paypal_API']['debug_mode'] == "Y") {
        $paypalUrl = "https://api.sandbox.paypal.com";
    }

    $url = $paypalUrl . "/v1/payments/$transaction_type/$Authorization_Id";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:application/json", "Authorization: Bearer $Access_Token"]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result_json            = curl_exec($ch);
    $result                 = json_decode($result_json, true);
    $result["curl_getinfo"] = curl_getinfo($ch);
    curl_close($ch);

    return $result;
}

function func_check_for_the_allowed_statuses_for_create_payment($order)
{

    $allowed_statuses_flag = true;

    if (!empty($order["shipping_groups"]) && is_array($order["shipping_groups"])) {
        foreach ($order["shipping_groups"] as $ko => $vo) {
            if (!in_array($vo["cb_status"], ['Q', 'N', 'I'])) {
                $allowed_statuses_flag = false;
                break;
            }
        }
    }

    return $allowed_statuses_flag;
}
