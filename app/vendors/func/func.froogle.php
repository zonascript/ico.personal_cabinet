<?php
use Modules\Core\Helpers\CoreHelper;
use Modules\Product\Models\ProductModel;
use Modules\User\Models\UserModel;
use Xcart\StoreFront;


#
# Translation string to frogle-compatibility-string
#
function func_froogle_convert($str, $max_len = false) {
        static $tbl = false;


        if ($tbl === false)
                $tbl = array_flip(get_html_translation_table(HTML_ENTITIES));

        $str = str_replace(array("\n","\r","\t"), array(" ", "", " "), $str);
        $str = CoreHelper::stripTags($str);
        $str = strtr($str, $tbl);

        if ($max_len > 0 && strlen($str) > $max_len) {
                $str = preg_replace("/\s+?\S+.{".intval(strlen($str)-$max_len-1+FROOGLE_TAIL_LEN)."}$/Ss", "", $str).FROOGLE_TAIL;
                if (strlen($str) > $max_len)
                        $str = substr($str, 0, $max_len-FROOGLE_TAIL_LEN).FROOGLE_TAIL;
        }

        return $str;
}

function GetGooglePrice($fproduct){
		global $sql_tbl, $xcart_dir, $active_modules, $config, $https_location, $http_location;

		$price_min_amount = func_product_price($fproduct);
		
        if ($fproduct["min_amount"] > 1 && $fproduct["mult_order_quantity"] == "Y"){
			/* price for bundle */
			if ($fproduct["product_availability"] == "out of stock")
				{
					$product_price = $price_min_amount;
				}
			else
				{
					$product_price = $price_min_amount * $fproduct["min_amount"];
				}
        }
		else {
			/* price for dozen item*/
				$product_price = $price_min_amount;
			}
	

	return $product_price;
}

function GetGoogleBaseOneRow($productid, $scrip_name="", $sExtraLog = "N"){
	global $sql_tbl, $xcart_dir, $active_modules, $config, $https_location, $http_location, $xcart_states_US, $aManufacturerZones, $HTTPS,
    $storefrontid, $current_storefront;

    $productModel = null;

    $start_time = round(microtime(true) * 1000);

    if ($storefrontid != "") {
        $use_storefrontid = $storefrontid;
    } else {
        if (isset($current_storefront)) {
            $use_storefrontid = $current_storefront; // froogle.php
        }
    }

if ($sExtraLog=='Y')
	echo 'Storefrontid: ' . $use_storefrontid;

    if ($productid)  {
        /** @var ProductModel $productModel */
        $productModel = ProductModel::objects()->get(['productid' => $productid]);
    }

    if (!$productModel) {
//		$row = "title\tdescription\tlink\tadwords_redirect\tadwords_grouping\tadwords_labels\timage link\tadditional image link\tid\tprice\tpayment accepted\tpayment notes\tquantity\tweight\texpiration date\tbrand\tcondition\tproduct type\tmpn\tmodel number\tgtin\tcompatible with\tonline only\tshipping\tavailability\tmultipack\tgoogle product category\n";
		$row = "title\tdescription\tlink\tadwords_redirect\timage link\tadditional image link\tid\tprice\tshipping weight\texpiration date\tbrand\tcondition\tproduct type\tmpn\tgtin\tshipping\tavailability\tmultipack\tgoogle product category\n";
		return $row;
	}

    $distributorModel = $productModel->distributor;

	$froogle_location = $config['Froogle']['froogle_used_https_links'] == 'Y' ? $https_location : $http_location;
	$froogle_scheme = $config['Froogle']['froogle_used_https_links'] == 'Y' ? 'https://' : 'http://';

	if ($productModel) {
		$froogle_scheme = ($productModel->getStoreFront()->getConfigValue('https_enabled') == 'Y') ? 'https://' : 'http://';
	}

	$where = "";
	$fields = "";
	$joins = "";

		$fields .= ", $sql_tbl[products_sf].sfid";
		$joins .= " INNER JOIN $sql_tbl[products_sf] ON  $sql_tbl[products].productid= $sql_tbl[products_sf].productid";
		$where .= " AND $sql_tbl[products_sf].productid = $productid";
        if (isset($use_storefrontid)){
                $where .= " AND $sql_tbl[products_sf].sfid = '$use_storefrontid'";
        }

	    if ($scrip_name == "main_google" || $scrip_name == "main_google_with_min_amount"){
		if (!empty($active_modules['Product_Options'])) {
			$where .= " AND IFNULL($sql_tbl[variants].avail, $sql_tbl[products].avail) >= '0'";
		} else {
			$where .= " AND $sql_tbl[products].avail >= '0'";
		}
	    }
	    else {
                if (!empty($active_modules['Product_Options'])) {
                        $where .= " AND IFNULL($sql_tbl[variants].avail, $sql_tbl[products].avail) > '0'";
                } else {
                        $where .= " AND $sql_tbl[products].avail > '0'";
                }
	    }

	$joins .= " INNER JOIN $sql_tbl[quick_prices] ON $sql_tbl[quick_prices].productid = $sql_tbl[products].productid AND $sql_tbl[quick_prices].membershipid = '0'";
	if (!empty($active_modules['Product_Options'])) {
		$joins .= " LEFT JOIN $sql_tbl[variants] ON $sql_tbl[variants].productid = $sql_tbl[products].productid AND $sql_tbl[quick_prices].variantid = $sql_tbl[variants].variantid";
		$fields .= ", IFNULL($sql_tbl[variants].productcode, $sql_tbl[products].productcode) as productcode, IFNULL($sql_tbl[variants].avail, $sql_tbl[products].avail) as avail, IFNULL($sql_tbl[variants].weight, $sql_tbl[products].weight) as weight";
	}

	if (!empty($active_modules['Manufacturers'])) {
		$fields .= ", IF ($sql_tbl[manufacturers_lng].manufacturer != '', $sql_tbl[manufacturers_lng].manufacturer, $sql_tbl[manufacturers].manufacturer) as manufacturer ";
		$joins .= " LEFT JOIN $sql_tbl[manufacturers] ON $sql_tbl[products].manufacturerid = $sql_tbl[manufacturers].manufacturerid LEFT JOIN $sql_tbl[manufacturers_lng] ON $sql_tbl[products].manufacturerid = $sql_tbl[manufacturers_lng].manufacturerid AND $sql_tbl[manufacturers_lng].code = '$froogle_lng'";
	}

//        $joins .= " LEFT JOIN xcart_supplier_feeds SF ON SF.manufacturerid = xcart_products.manufacturerid and SF.feed_type = 'I' and SF.feed_file_name = xcart_products.provider";
	$joins .= " LEFT JOIN xcart_supplier_feeds SF ON SF.manufacturerid = xcart_products.manufacturerid and SF.feed_type = 'I' AND SF.enabled='Y' AND (SF.multiple_feed_destinations!='Y' OR (SF.multiple_feed_destinations='Y' AND xcart_products.controlled_by_feed=SF.feed_file_name))";
        $fields .= ", SF.enabled as supplier_feeds_enabled ";


	if (!empty($active_modules['Brands'])) {
		$fields .= ", IF ($sql_tbl[brands_lng].brand != '', $sql_tbl[brands_lng].brand, $sql_tbl[brands].brand) as brand";
		$joins .= " LEFT JOIN $sql_tbl[brands] ON $sql_tbl[products].brandid = $sql_tbl[brands].brandid LEFT JOIN $sql_tbl[brands_lng] ON $sql_tbl[products].brandid = $sql_tbl[brands_lng].brandid AND $sql_tbl[brands_lng].code = '$froogle_lng'";
	}

	$product = func_query_first($qqq="SELECT $sql_tbl[products].*, $sql_tbl[categories].categoryid_path, $sql_tbl[pricing].price, $sql_tbl[images_T].image_path $fields FROM ($sql_tbl[categories], $sql_tbl[products_categories], $sql_tbl[pricing], $sql_tbl[products]) LEFT JOIN $sql_tbl[images_T] ON $sql_tbl[products].productid = $sql_tbl[images_T].id $joins WHERE $sql_tbl[products].productid = $sql_tbl[products_categories].productid AND $sql_tbl[products_categories].categoryid = $sql_tbl[categories].categoryid AND $sql_tbl[products_categories].main='Y' AND $sql_tbl[pricing].priceid = $sql_tbl[quick_prices].priceid AND $sql_tbl[products].forsale = 'Y' AND $sql_tbl[categories].avail = 'Y' $where GROUP BY $sql_tbl[products].productid HAVING (price > '0' OR $sql_tbl[products].product_type = 'C')");


	if (empty($product))
		return;

	if ($scrip_name != "main_google"){
	        if ($product["min_amount"] > 1){
		}
	}
	$sf_info = func_get_storefront_info($product['sfid'], 'ID', true);

	$product_categories = func_query_hash("SELECT $sql_tbl[products].productid, $sql_tbl[categories].categoryid_path FROM ($sql_tbl[categories], $sql_tbl[products_categories], $sql_tbl[products]) WHERE $sql_tbl[products].productid = $sql_tbl[products_categories].productid AND $sql_tbl[products_categories].categoryid = $sql_tbl[categories].categoryid AND $sql_tbl[products].forsale = 'Y' AND $sql_tbl[categories].avail = 'Y' AND $sql_tbl[products].productid='$productid'", 'productid', true, true);

	if (!empty($product["eta_date_mm_dd_yyyy"])){
			if ($product["eta_date_mm_dd_yyyy"] > time()){
			}
	}

	$product['froogle_location'] = $froogle_scheme . func_get_http_location_sf($product['sfid']);

	$tmp_upc = trim($product['upc']);
	$tmp_upc = isset($tmp_upc) ? abs(intval($tmp_upc)) : 0;
	if (empty($tmp_upc) || $tmp_upc == "0"){
		$product['upc'] = "";
	}

	$clean_url_link = func_query_first_cell("SELECT clean_url FROM $sql_tbl[clean_urls] WHERE resource_type='P' AND resource_id='$product[productid]'");
	$clean_url_link .="/";

	$product['link'] = $product['froogle_location'] . constant('DIR_CUSTOMER') . '/'. $clean_url_link;

	if (!empty($sf_info['prefix'])){

		$utm_medium = trim($product['brand']);
		$utm_medium = preg_replace('/[^\w]/', '', $utm_medium);
		$utm_medium = preg_replace('/[_]/', '', $utm_medium);

		$utm_campaign = $product['productcode'];
		$utm_campaign = preg_replace('/[^\w]/', '', $utm_campaign);
		$utm_campaign = preg_replace('/[_]/', '', $utm_campaign);

		/*Google formatted links*/
		//$product['link'] = $product['froogle_location'] . constant('DIR_CUSTOMER') . '/' . $clean_url_link . '?utm_source=' . $sf_info['prefix'] . 'froogle_Google-Shopping&utm_medium='.$utm_medium.'&utm_campaign='.$utm_campaign;
//		$product['adwords_redirect'] = $product['froogle_location'] . constant('DIR_CUSTOMER') . '/' . $clean_url_link . '?utm_source=' . $sf_info['prefix'] . 'froogle_Product-Ads&utm_medium='.$utm_medium.'&utm_campaign='.$utm_campaign;
        $product['adwords_redirect'] = $product['froogle_location'] . constant('DIR_CUSTOMER') . '/' . $clean_url_link . '?origin=google_product_ads';
		/*Bing formatted links*/
		//$product['bing_link'] = $product['froogle_location'] . constant('DIR_CUSTOMER') . '/' . $clean_url_link . '?utm_source=' . $sf_info['prefix'] . 'Bing-Shopping&utm_medium='.$utm_medium.'&utm_campaign='.$utm_campaign;
//		$product['bing_adwords_redirect'] = $product['froogle_location'] . constant('DIR_CUSTOMER') . '/' . $clean_url_link . '?utm_source=' . $sf_info['prefix'] . 'Bing_Product-Ads&utm_medium='.$utm_medium.'&utm_campaign='.$utm_campaign;
		$product['bing_adwords_redirect'] = $product['froogle_location'] . constant('DIR_CUSTOMER') . '/' . $clean_url_link . '?origin=bing_product_ads';
		
		$product["adwords_grouping"] = $product['manufacturerid'];
		$product['page_url'] = $product['froogle_location'] . constant('DIR_CUSTOMER') . '/'. $clean_url_link . '?utm_source=' . $sf_info['prefix'] . 'thefind&utm_medium=feed&utm_campaign='.$utm_campaign;
	}

	# Get google product category
	$gpc = func_query_first_cell(" SELECT C.google_product_category FROM $sql_tbl[categories] As C LEFT JOIN $sql_tbl[products_categories] As PC ON PC.categoryid = C.categoryid WHERE PC.productid = ".$product['productid']." and PC.main = 'Y'");
	# Define product category path
	$cats = array();
	if (is_array($product_categories) && isset($product_categories[$product['productid']]) && is_array($product_categories[$product['productid']])) {
		foreach ($product_categories[$product['productid']] as $kpc => $pc) {
			$catids = explode("/", $pc);
			if ($catids[0] == EXCLUDE_CATEGORYID_BRANCH) {
				continue;
			}

			if (!empty($catids)) {
				$cats[$kpc] = func_query("SELECT categoryid, category, google_product_category FROM $sql_tbl[categories] WHERE categoryid IN ('".implode("','", $catids)."') $sf_cat_condition");
				$catids = array_flip($catids);
				if (!empty($cats[$kpc])) {
					/*if (count($cats[$kpc]) != count($catids))
                                                    continue;*/

					foreach ($cats[$kpc] as $k => $v) {
                                                    if (isset($catids[$v['categoryid']])) {
                                                        if (trim($v['google_product_category']) != '') $gpc = $v['google_product_category'];
                                                        $catids[$v['categoryid']] = $v['category'];
                                                    }
					}

					$cats[$kpc] = str_replace("\t", ' ', implode(' > ', $catids));

				}
			}
		}
	}
	if (!empty($cats[0])){
		$cats_path = $cats[0];
	}

	$cats_path_for_thefind = !empty($cats) ? implode(',', $cats) : '';

	$cats_path = func_froogle_convert($cats_path, 1000);
	$cats_path = func_cidev_check_froogle_field($cats_path);
	$cats_path = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $cats_path);

	$cats_path_for_thefind = func_froogle_convert($cats_path_for_thefind, 1000);
	$cats_path_for_thefind = func_cidev_check_froogle_field($cats_path_for_thefind);
	$cats_path_for_thefind = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $cats_path_for_thefind);

	# Define full description
	if (!empty($product['fulldescr']))
		$product['descr'] = $product['fulldescr'];
	
	if (strlen(trim($product['descr']))<20)
	{
		$product['descr'] = $product['descr'].' '.$product['product'];
	}

	$product['descr'] = func_froogle_convert($product['descr'], 10000);
	$product['descr'] = func_cidev_check_froogle_field($product['descr']);
	$product['descr'] = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $product['descr']);

	$product['product'] = func_froogle_convert($product['product'], 70);
	$product['product'] = func_cidev_check_froogle_field($product['product']);
	$product['product'] = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $product['product']);

	# Define product image
	$tmp = func_query_first("SELECT id, image_path FROM $sql_tbl[images_P] WHERE $sql_tbl[images_P].id = '$product[productid]'");
	$tmbn = "";
	$image_path = "";
	$image_type = "";

	if (!empty($tmp['id'])) {
		$image_path = $tmp['image_path'];
		$image_type = "P";
	} elseif (!is_null($product['image_path'])) {
		$image_path = $product['image_path'];
		$image_type = "T";
	}

	if (!empty($image_type)) {
		if (!empty($image_path))
			$tmbn = func_get_image_url($product['productid'], $image_type, $image_path);
		if ($tmbn === false || empty($tmbn)) {
			$tmbn = $product['froogle_location'] . '/image.php?id=' . $product['productid'] . '&type=' . $image_type;
		} elseif (strpos($tmbn, $https_location) !== false) {
			$tmbn = str_replace($https_location, $product['froogle_location'], $tmbn);
		}
	}

	$ci = array(
		"city" => $config['General']['default_city'],
		"state" => $config['General']['default_state'],
		"country" => $config['General']['default_country'],
		"zipcode" => $config['General']['default_zipcode']
	);
	if (!empty($active_modules['Product_Options']))
		$product['price'] += func_get_default_options_markup($product['productid'], $product['price']);

	$tmp = func_tax_price($product['price'], $product['productid'], false, NULL, $ci);
	$product['price'] = $tmp['taxed_price'];


	if (empty($cidev_number_clicks) || $cidev_number_clicks == 0){
		$cidev_number_clicks = $config["Froogle"]["froogle_number_clicks_last_used"];
	}

	if (empty($cidev_max_cpc_group) || $cidev_max_cpc_group == 0){
		$cidev_max_cpc_group = $config["Froogle"]["froogle_max_cpc_group_last_used"];
	}

	$CPC_group = price_format((max($product["new_map_price"], $product["price"]) - $product["cost_to_us"])/$cidev_number_clicks);

	$product['adwords_labels'] = $CPC_group."-cpc-group";

	if ($CPC_group >= $cidev_max_cpc_group){
		$product['adwords_labels'] = $cidev_max_cpc_group."-cpc-group";
	}

	if ($CPC_group <= 0){
		$product['adwords_labels'] = "0.01-cpc-group";
	}

	if ($product["list_price"] > 20 && (1 - ($product["price"]/$product["list_price"]))>0.50){
		$product['adwords_labels'] .= ", offlist";
	}


	$mpn = $productModel->getMPN();
	$product['custom_label_3'] = $distributorModel->manufacturer;

	# Define "compatible with"
	$upselling_products = func_query("SELECT p.product_froogle, p.productcode, p.upc, b.brand FROM $sql_tbl[product_links] as pl, $sql_tbl[products] as p LEFT JOIN $sql_tbl[brands] b ON b.brandid=p.brandid WHERE pl.productid1=$product[productid] AND p.productid=pl.productid2");
	$compatible_with = '';

	if (!empty($upselling_products) && is_array($upselling_products)) {

		foreach ($upselling_products as $up) {
			$tmp_upc = trim($up['upc']);
			$tmp_upc = isset($tmp_upc) ? abs(intval($tmp_upc)) : 0;
			if (empty($tmp_upc) || $tmp_upc == "0"){
				$up['upc'] = "";
			}

			$up_mpn = $productModel->getMPN();
			if ($compatible_with != '') {
				$compatible_with .= ', ';
			}

			if (!empty($up_mpn) && !empty($up['upc']) && !empty($up['brand']) && !empty($up['product_froogle'])){

				$up['product_froogle'] = str_replace(":", '-', $up['product_froogle']);
				$compatible_with .= $up['product_froogle'].':'.$up_mpn.':'.$up['upc'].':'.trim($up['brand']);
				break; # Internal SF tasks: Google Base feed COMPATIBLE_WITH issue
			}
		}
	}

	$online_only = '';
	if ($product['shipping_freight'] == 0.00) {
		$online_only = 'n';
		$product["onlineOnly"] = "0";
	} elseif ($product['shipping_freight'] > 0.00) {
		$online_only = 'y';
		$product["onlineOnly"] = "1";
	}
	$newShipping = $productModel->getStoreFront()->getConfigValue('new_shipping_calculation');

	if (!empty($newShipping) && $newShipping == 'Y') {
		$shippings_str_arr = $shippings_google_arr = $aShippingCarrier = [];
		$shipping_currency = "USD";
		$oManufacturer = $distributorModel;
		foreach ($xcart_states_US as $k => $v) {
			$oCart = new Xcart\Cart();
			$oCart->addObjectToCart(new \Xcart\CartElement($productModel));
            $oCustomer = new UserModel(
                [
                    's_country' => $v["country_code"],
                    's_state' => $v["code"],
                    's_city' => $v["city"],
                    's_zipcode' => $v["base_state_zipcode"]
                ]
            );

			$oShipping = Xcart\Shipping::model();

			if (!empty($aManufacturerZones['zones'][$distributorModel->manufacturerid][$oCustomer->s_country][$oCustomer->s_state])) {
				$oShipping->setShippingZones($aManufacturerZones['zones'][$distributorModel->manufacturerid][$oCustomer->s_country][$oCustomer->s_state]);
			}
			if (!empty($aManufacturerZones['methods'][$distributorModel->manufacturerid][$oCustomer->s_country][$oCustomer->s_state])) {
				$oShipping->setZoneShippingMethods($aManufacturerZones['methods'][$distributorModel->manufacturerid][$oCustomer->s_country][$oCustomer->s_state]);
			}

            try {
                $aShippingZoneRates = $oShipping->getShippingRates($oCustomer, $distributorModel, $oCart, true);
            } catch (\Exception $e) {
                $aShippingZoneRates = [];
            }

			$aManufacturerZones['zones'][$distributorModel->manufacturerid][$oCustomer->s_country][$oCustomer->s_state] = $oShipping->getShippingZones($oCustomer, $distributorModel);
			$aManufacturerZones['methods'][$distributorModel->manufacturerid][$oCustomer->s_country][$oCustomer->s_state] = $oShipping->getZoneShippingMethods();

			if (!empty($aShippingZoneRates)) {
				foreach ($aShippingZoneRates as $aShippingRates) {
					if (!empty($aShippingRates)) {
						/** @var \Xcart\ShippingRate $oShippingRate */
						$oShippingRate = reset($aShippingRates);
						$shippings_str_arr[] = $v["country_code"] . ":" . $v["code"] . ":" . $oShippingRate->getShippingEntity()->getFrontendName() . ":" . price_format($oShippingRate->getShippingCharge()) . $shipping_currency;
						$sga = [];
						$sga["price"]["value"] = price_format($oShippingRate->getShippingCharge());
						$sga["price"]["currency"] = trim($shipping_currency);
						$sga["country"] = $v["country_code"];
						$sga["region"] = $v["code"];
						$sga["service"] = $oShippingRate->getShippingEntity()->getFrontendName();
						$shippings_google_arr[] = $sga;
						$aShippingCarrier[] = $oShippingRate->getShippingEntity()->getShippingCarrier()->getName();
						break;
					}
				}
			}
		}
		$shipping_arr["shippings_str"] = implode(",", $shippings_str_arr);
		$shipping_arr["shippings_google_arr"] = $shippings_google_arr;
		$product['custom_label_2'] = 'UPS rates';
		if (in_array('Amazon', $aShippingCarrier)) {
			$product['custom_label_2'] = 'Amazon rates';
		}
	} else {
		if ($productModel->isProductFBAAvail()) {
			$start_time_amazon_shipping = round(microtime(true) * 1000);
			$amazon_shippings_arr = func_get_amazon_shippings_for_all_states($product);
			$diff_end_time_amazon_shipping = (round(microtime(true) * 1000) - $start_time_amazon_shipping);
		}

		$start_time_approximate_shipping = round(microtime(true) * 1000);
		$shipping_arr = func_define_approximate_shippings($product["productid"], $product);
		$diff_end_time_approximate_shipping = (round(microtime(true) * 1000) - $start_time_approximate_shipping);

		if ($sExtraLog == 'Y') {
			func_print_r($shipping_arr);
		}

		$product['custom_label_2'] = 'UPS rates';

		if (!empty($amazon_shippings_arr)) {

			$shipping_ground_arr = $shipping_arr;
			$shipping_arr = $amazon_shippings_arr;

			if (is_array($shipping_arr["not_found_rates_for_state"]) && !empty($shipping_ground_arr["shippings_google_arr"])) {

				foreach ($shipping_arr["not_found_rates_for_state"] as $k_n => $v_n) {
					foreach ($shipping_ground_arr["shippings_google_arr"] as $k_g => $v_g) {
						if ($v_g["region"] == $v_n) {
							$shipping_arr["shippings_google_arr"][] = $v_g;
							$shipping_arr["shippings_str"] .= ",US:" . $v_n . ":Ground:" . $v_g["price"]["value"] . "USD";
							$product['custom_label_2'] = 'FBA rates';
						}
					}
				}
			}
		}
	}
	$shipping = $shipping_arr["shippings_str"];
	$custom_label_0 = '';
	$custom_label_1 = '';
	$base_rel = 12/17;
	$product["shippings_google_arr"] = $shipping_arr["shippings_google_arr"];
	foreach ($shipping_arr["shippings_google_arr"] as $cl_k => $cl_sh) {
		if ($cl_sh['region'] == "CA") {
			if ($cl_sh['price']['value'] / $product['price'] > $base_rel) {
				$product["custom_label_0"] = "junk";
			} else {
				$product["custom_label_0"] = "normal";
			}
			break;
		}
	}

    $price_group_label = '0';
    $cmp_price = $product['price'];
    if ($cmp_price<10) {
        $price_group_label = '10';
    } elseif ($cmp_price<50) {
        $price_group_label = '50';
    } elseif ($cmp_price<100) {
        $price_group_label = '100';
    } elseif ($cmp_price<150) {
        $price_group_label = '150';
    } elseif ($cmp_price<200) {
        $price_group_label = '200';
    } elseif ($cmp_price<250) {
        $price_group_label = '250';
    } elseif ($cmp_price<300) {
        $price_group_label = '300';
    } elseif ($cmp_price<400) {
        $price_group_label = '400';
    } elseif ($cmp_price<500) {
        $price_group_label = '500';
    } elseif ($cmp_price<600) {
        $price_group_label = '600';
    } elseif ($cmp_price<700) {
        $price_group_label = '700';
    } elseif ($cmp_price<800) {
        $price_group_label = '800';
    } elseif ($cmp_price<900) {
        $price_group_label = '900';
    } elseif ($cmp_price<1000) {
        $price_group_label = '1000';
    } elseif ($cmp_price<1200) {
        $price_group_label = '1200';
    } elseif ($cmp_price<1400) {
        $price_group_label = '1400';
    } elseif ($cmp_price<1600) {
        $price_group_label = '1600';
    } elseif ($cmp_price<1800) {
        $price_group_label = '1800';
    } elseif ($cmp_price<2000) {
        $price_group_label = '2000';
    } elseif ($cmp_price<3000) {
        $price_group_label = '3000';
    } elseif ($cmp_price<4000) {
        $price_group_label = '4000';
    } elseif ($cmp_price<5000) {
        $price_group_label = '5000';
    } elseif ($cmp_price<10000) {
        $price_group_label = '10000';
    } else {
        $price_group_label = '100000';
    }
    $product["custom_label_1"] = $price_group_label;
    
	#
	# Define Detailed product image
	#
	$tmp_all = func_query("SELECT id, imageid, image_path FROM $sql_tbl[images_D] WHERE $sql_tbl[images_D].id = '$product[productid]' AND $sql_tbl[images_D].avail='Y' ORDER BY orderby");

	if (!empty($tmp_all) && is_array($tmp_all)){
		foreach($tmp_all as $k_tmp => $tmp){

			if (!empty($tmp['imageid'])) {

				$tmbn_d = "";
				$image_path = "";
				$image_type = "";

				$image_path = $tmp['image_path'];
				$image_type = "D";

				if (!empty($image_path))
					$tmbn_d = func_get_image_url($tmp['imageid'], $image_type, $image_path);

				if ($tmbn_d === false || empty($tmbn_d)) {
					$tmbn_d = $product['froogle_location'] . '/image.php?id=' . $tmp['imageid'] . '&type=' . $image_type;
				} elseif (strpos($tmbn_d, $https_location) !== false) {
					$tmbn_d = str_replace($https_location, $product['froogle_location'], $tmbn_d);
				}

				if (strpos($tmbn_d, "default_image") !== false) {
					$tmp_all[$k_tmp]["tmbn_no_img"] = "Y";
				}

				$tmp_all[$k_tmp]["tmbn_d"] = $tmbn_d;
			}
		}

		foreach($tmp_all as $k_tmp => $tmp){
			if ($tmp["tmbn_no_img"] != "Y"){
				$tmbn = $tmp["tmbn_d"];
				unset($tmp_all[$k_tmp]);
				break;
			}
		}
	}

	$additional_image_link = "";

	if (!empty($tmp_all) && is_array($tmp_all)){
		$arr_additional_image_link = array();
		$tmp_count_additional_image_link = 0;

		foreach($tmp_all as $k_tmp => $tmp){
			if ($tmp["tmbn_no_img"] != "Y"){
				$arr_additional_image_link[] = $tmp["tmbn_d"];
				$product["additional_image_link"][] = $tmp["tmbn_d"];
				$tmp_count_additional_image_link++;
			}

			if ($tmp_count_additional_image_link == "10"){
				break;
			}
		}

		if ($tmp_count_additional_image_link > 0){
			$additional_image_link = implode(",", $arr_additional_image_link);
		}

	}
	$tmbn_no_img = "";
	if ((strpos($tmbn, "default_image") !== false) || empty($tmbn)) {
		$tmbn_no_img = "Y";
	}

	if ($sf_info["config"]["Appearance"]["Enable_CDN"]=="Y" && !empty($sf_info["config"]["Appearance"]["CDN_domain"])){
		$imgurl = (($sf_info["config"]["Appearance"]['https_enabled']=='Y') ? 'https://' : 'http://') . $sf_info["config"]["Appearance"]["CDN_domain"];
	} else {
		$imgurl = ($sf_info["config"]["Appearance"]['https_enabled']=='Y') ? $https_location : $http_location;
	}
	if (!empty($tmbn)) {
		$tmbn = $imgurl . $tmbn;
	} else {
		$tmbn = $imgurl . "/default_image.gif";
	}
	if (!empty($additional_image_link)) {
		$additional_image_link = $imgurl . $additional_image_link;
	}
	$tmp_image_link = $tmbn;
	$product['image_link'] = $tmp_image_link;

	if (empty($product['weight'])){
		$product['weight'] = "0.1";
	}

		$product_availability = $product["product_availability"] = func_product_availability(false,$product);

		$multipack = "";
		if ($product["min_amount"]>1 && $product["mult_order_quantity"] == "Y")
		{
			$multipack = $product["min_amount"];
			$product['multipack'] = $multipack;
		}
		
		$product['price'] = price_format(GetGooglePrice($product));
		$product['taxed_price'] = $product['price'];


	$product['mpn'] = $mpn;
	$product['gpc'] = $gpc;
	$product['cats_path'] = $cats_path;
	$product['google_descr'] = iconv("UTF-8", "ISO-8859-1//TRANSLIT",func_froogle_convert(trim($product['descr']), 5000));
	$product['google_brand'] = iconv("UTF-8", "ISO-8859-1//TRANSLIT",func_froogle_convert(trim($product['brand']), 256));
	$product['google_product'] = iconv("UTF-8", "ISO-8859-1//TRANSLIT",func_froogle_convert((trim($product['product_froogle']) ? trim($product['product_froogle']) : trim($product['product'])), 80));

    if (($distributorModel->d_minimum_order_amount == 'applies_to_all_orders')
        && (($m_order_amount = floatval($distributorModel->d_minimum_order_amount_in_us)) > 0)
        && (floatval($product['price']) < $m_order_amount)
    ) {
        $m_order_amount = number_format($m_order_amount, 2);
        $product['shipping_label'] = "Minimum order value {$m_order_amount} USD";
    }


	if ($product['shipping_weight']) {
		$product['weight'] = $product['shipping_weight'];
	}
	if ($product['shipping_dim_x']||$product['shipping_dim_y']||$product['shipping_dim_z']) {
		$product['dim_x'] = $product['shipping_dim_x'];
		$product['dim_y'] = $product['shipping_dim_y'];
		$product['dim_z'] = $product['shipping_dim_z'];
	}
    $product['shipping_​​weight'] = $product['weight'];

	$row = $product['google_product']."\t".
	$product['google_descr']."\t".
	$product['link'] . "\t".
	$product['adwords_redirect'] . "\t".
	$tmp_image_link."\t".
	$additional_image_link."\t".
	$product['productid']."\t".
	$product['price']."\t".
	$product['shipping_​​weight'].($product['shipping_​​weight'] > 0 ? " lb":"")."\t".
	date("Y-m-d", time()+(empty($config['Froogle']['froogle_expiration_date']) ? 0.5 : $config['Froogle']['froogle_expiration_date'])*86400)."\t".
	$product['google_brand']."\t".
	"new\t".
	"$cats_path"."\t".
	"$mpn\t".
	trim($product['upc']) . "\t".
	"$shipping\t" .
	"$product_availability\t".$multipack."\t".$gpc;

	$row_arr["row"] = $row;
	$row_arr["product"] = $product;

	$current_time = round(microtime(true) * 1000);
	$diff_time = ($current_time - $start_time);

	if ($sExtraLog == "Y")
		func_backprocess_log("incremental feeds", sprintf("Row generated for pid=%d in %d msec. Amazon(%d msec), Approx(%d msec)",$product['productid'],$diff_time, $diff_end_time_amazon_shipping, $diff_end_time_approximate_shipping));

	return $row_arr;
}

function AddProductToAmazonBatch($productid, $update_type, $amazon_inventory_batch_count, $ainventory){

	if ($update_type == "2" || $update_type == "1,2" || $update_type == "1"){
                $count_ainventory = count($ainventory);
                $ainventory[$count_ainventory]["productid"] = $productid;
                $amazon_inventory_batch_count++;
	}

        $AddProductToAmazonBatch_arr["amazon_inventory_batch_count"] = $amazon_inventory_batch_count;
        $AddProductToAmazonBatch_arr["ainventory"] = $ainventory;

        return $AddProductToAmazonBatch_arr;
}

function AddProductToGoogleBaseBatch($productid, $update_type, $forsale, $google_products_batch_count, $gproducts, $google_inventory_batch_count, $ginventory, $sExtraLog = "N"){

	if ($update_type == "1" || $update_type == "1,2" || (($update_type == "2" && $forsale == "N"))){
			$Batchid = $google_products_batch_count;
			$count_gproducts = count($gproducts);
			$gproducts[$count_gproducts]["productid"] = $productid;
			$gproducts[$count_gproducts]["Batchid"] = $Batchid;
			$gproducts[$count_gproducts]["product_info"] = GetGoogleBaseOneRow($productid, "main_google", $sExtraLog);
			$google_products_batch_count++;
	}
	elseif ($update_type == "2" && $forsale == "Y"){
		$Batchid = $google_inventory_batch_count;
		$count_ginventory = count($ginventory);
		$ginventory[$count_ginventory]["productid"] = $productid;
		$ginventory[$count_ginventory]["Batchid"] = $Batchid;
		$google_inventory_batch_count++;
	}

	$AddProductToGoogleBaseBatch_arr["google_products_batch_count"] = $google_products_batch_count;
	$AddProductToGoogleBaseBatch_arr["gproducts"] = $gproducts;
	$AddProductToGoogleBaseBatch_arr["google_inventory_batch_count"] = $google_inventory_batch_count;
	$AddProductToGoogleBaseBatch_arr["ginventory"] = $ginventory;

	return $AddProductToGoogleBaseBatch_arr;
}

function SubmitGoogleInventoryBatch($ginventory, $service, $MerchantID, $debug_mode = 'N', $sExtraLog = 'N'){
			global $started_at, $sql_tbl, $froogle_tracing_token, $debug_requests;

	foreach ($ginventory as $k => $v){
				/*func_build_quick_prices($v["productid"]);*/
				if ($sExtraLog == 'Y')
					func_backprocess_log("incremental feeds", sprintf("Inventory updated for pid=%d",$v["productid"]));
                $fields = ", IFNULL($sql_tbl[variants].avail, $sql_tbl[products].r_avail) as r_avail, $sql_tbl[products].cost_to_us, $sql_tbl[products].map_price, $sql_tbl[products].manufacturerid, $sql_tbl[products].eta_date_mm_dd_yyyy";
                $joins = " INNER JOIN $sql_tbl[products_sf] ON  $sql_tbl[products].productid= $sql_tbl[products_sf].productid";
                $joins .= " INNER JOIN $sql_tbl[quick_prices] ON $sql_tbl[quick_prices].productid = $sql_tbl[products].productid AND $sql_tbl[quick_prices].membershipid = '0'";
                $joins .= " LEFT JOIN $sql_tbl[variants] ON $sql_tbl[variants].productid = $sql_tbl[products].productid AND $sql_tbl[quick_prices].variantid = $sql_tbl[variants].variantid";
                $where = " AND $sql_tbl[products_sf].productid = '$v[productid]' AND IFNULL($sql_tbl[variants].avail, $sql_tbl[products].avail) >= '0'";

                $product = func_query_first("SELECT SQL_NO_CACHE $sql_tbl[products].productid, $sql_tbl[products].provider, $sql_tbl[products].new_map_price, $sql_tbl[products].r_avail, $sql_tbl[products].avail, $sql_tbl[products].cost_to_us, $sql_tbl[products].product_type, $sql_tbl[pricing].price $fields, $sql_tbl[products].min_amount, $sql_tbl[products].mult_order_quantity FROM ($sql_tbl[categories], $sql_tbl[products_categories], $sql_tbl[pricing], $sql_tbl[products]) $joins WHERE $sql_tbl[products].productid = $sql_tbl[products_categories].productid AND $sql_tbl[products_categories].categoryid = $sql_tbl[categories].categoryid AND $sql_tbl[pricing].priceid = $sql_tbl[quick_prices].priceid $where GROUP BY $sql_tbl[products].productid HAVING (price > '0' OR $sql_tbl[products].product_type = 'C')");

				
				$product_availability = $product["product_availability"] = func_product_availability(false,$product);
				If ($product["min_amount"]>1 and $product["mult_order_quantity"] == "Y")
					{
						$product['multipack'] = $product["min_amount"];
					}
//                $product["supplier_feeds_enabled"] = func_query_first_cell("SELECT enabled FROM $sql_tbl[supplier_feeds] WHERE manufacturerid='$product[manufacturerid]' AND feed_file_name='$product[provider]' AND feed_type = 'I'");
		$product["supplier_feeds_enabled"] = func_query_first_cell("SELECT enabled FROM $sql_tbl[supplier_feeds] WHERE manufacturerid='$product[manufacturerid]' AND feed_type = 'I' AND enabled='Y' AND (multiple_feed_destinations!='Y' OR (multiple_feed_destinations='Y' AND feed_file_name='".$product["controlled_by_feed"]."'))");
		
				
				$product['price'] = price_format(GetGooglePrice($product));
				$postBody["entries"][$k]["inventory"]["price"]["value"] = $product["price"];
				$postBody["entries"][$k]["inventory"]["price"]["currency"] = "USD";
				$postBody["entries"][$k]["inventory"]["availability"]= $product_availability;

				$postBody["entries"][$k]["batchId"] = $v["productid"];
				$postBody["entries"][$k]["merchantId"] = $MerchantID;
				$postBody["entries"][$k]["storeCode"] = "online";
				$postBody["entries"][$k]["productId"] = "online:en:US:".$v["productid"];
				$postBody["entries"][$k]["inventory"]["kind"] = "content#inventory";

	}
	$code = 200;
if ($debug_mode != 'Y') {

	try {

		$k++;
		print("\nGB: tried to submit $k items as inventory feed \n");

		$log_text = "GB: tried to submit $k items as inventory feed ($MerchantID: " . $product["sfid"] . " )";
		func_backprocess_log("incremental feeds", $log_text);


		$params = array();
		$params["postBody"] = $postBody;


		if (!empty($froogle_tracing_token)) {
			$params['trace'] = 'token:' . $froogle_tracing_token;
		}

		print("GB: call custombatch return Google_Service_ShoppingContent_InventoryCustomBatchResponse");

		$results = $service->inventory->call('custombatch', array($params), "Google_Service_ShoppingContent_InventoryCustomBatchResponse");

		print("GB: call custombatch end");

		$results_arr = (array)$results;
		$log_text = "";
		foreach ($results_arr as $k => $v) {
			if (!empty($v) && is_array($v)) {
				foreach ($v as $kk => $vv) {
					if ($kk == "entries" && !empty($vv) && is_array($vv)) {
						foreach ($vv as $kkk => $vvv) {
							if (!empty($vvv["errors"])) {
								$log_text .= "batchId: " . $vvv["batchId"] . " code: " . $vvv["errors"]["code"] . " message: " . $vvv["errors"]["message"] . "\n";
								$code = $vvv["errors"]["code"];
							}
						}
					}
				}
			}
		}
		print("GB: End process results");

		if (!empty($log_text)) {
			func_backprocess_log("incremental feeds", $log_text);
		}

//func_print_r($log_text);
###


//$ginventory_new = json_encode($postBody);
//func_print_r($ginventory_new);
//		$results = $service->inventory->custombatch($ginventory_new);
	} catch (Google_Exception $e) {
		// Other error.
		print "An error occurred: (" . $e->getCode() . ") " . $e->getMessage() . "\n";

		$log_text = "An error occurred: (" . $e->getCode() . ") " . $e->getMessage();
		func_backprocess_log("incremental feeds", $log_text);
	}
	catch (Exception $e) {
		// Other error.
		print "An error occurred: (" . $e->getCode() . ") " . $e->getMessage() . "\n";

		$log_text = "An error occurred: (" . $e->getCode() . ") " . $e->getMessage();
		func_backprocess_log("incremental feeds", $log_text);
	}
}
return $code;
//func_print_r($results);
}
function SubmitBingInventoryBatch($binventory, $sEndpoint, $MerchantID, $CatalogID, $username, $password, $token, $debug_mode = 'N')
{
        global $xcart_dir, $active_modules, $config, $https_location, $http_location;
        global $started_at, $sql_tbl;
        global $storefrontid, $current_storefront;

        if ($storefrontid !=""){
                $use_storefrontid = $storefrontid;
        } else {
                if (isset($current_storefront)){
	                $use_storefrontid = $current_storefront; // froogle.php
                    }
                }
    $sf_info = func_get_storefront_info($use_storefrontid, 'ID', true);

	foreach ($binventory as $k => $v){
				/*func_build_quick_prices($v["productid"]);*/
                $fields = ", IFNULL($sql_tbl[variants].avail, $sql_tbl[products].r_avail) as r_avail, $sql_tbl[products].cost_to_us, $sql_tbl[products].map_price, $sql_tbl[products].manufacturerid, $sql_tbl[products].eta_date_mm_dd_yyyy, $sql_tbl[products].product, $sql_tbl[products_sf].sfid";
                $joins = " INNER JOIN $sql_tbl[products_sf] ON  $sql_tbl[products].productid= $sql_tbl[products_sf].productid";
                $joins .= " INNER JOIN $sql_tbl[quick_prices] ON $sql_tbl[quick_prices].productid = $sql_tbl[products].productid AND $sql_tbl[quick_prices].membershipid = '0'";
                $joins .= " LEFT JOIN $sql_tbl[variants] ON $sql_tbl[variants].productid = $sql_tbl[products].productid AND $sql_tbl[quick_prices].variantid = $sql_tbl[variants].variantid";
                $where = " AND $sql_tbl[products_sf].productid = '$v[productid]' AND IFNULL($sql_tbl[variants].avail, $sql_tbl[products].avail) >= '0' and $sql_tbl[products_sf].sfid = $use_storefrontid";

                $product = func_query_first("SELECT SQL_NO_CACHE $sql_tbl[products].productid, $sql_tbl[products].provider, $sql_tbl[products].new_map_price, $sql_tbl[products].avail, $sql_tbl[products].r_avail, $sql_tbl[products].cost_to_us, $sql_tbl[products].product_type, $sql_tbl[pricing].price $fields, $sql_tbl[products].min_amount, $sql_tbl[products].mult_order_quantity FROM ($sql_tbl[categories], $sql_tbl[products_categories], $sql_tbl[pricing], $sql_tbl[products]) $joins WHERE $sql_tbl[products].productid = $sql_tbl[products_categories].productid AND $sql_tbl[products_categories].categoryid = $sql_tbl[categories].categoryid AND $sql_tbl[pricing].priceid = $sql_tbl[quick_prices].priceid $where GROUP BY $sql_tbl[products].productid HAVING (price > '0' OR $sql_tbl[products].product_type = 'C')");

				
				//$product_availability = $product["product_availability"] = func_product_availability(false,$product);
        		$product_availability = $product["product_availability"] = 'in stock';

        		if ($product["min_amount"]>1 and $product["mult_order_quantity"] == "Y")
					{
						$product['multipack'] = $product["min_amount"];
					}
                    
                $product["supplier_feeds_enabled"] = func_query_first_cell("SELECT enabled FROM $sql_tbl[supplier_feeds] WHERE manufacturerid='$product[manufacturerid]' AND feed_type = 'I' AND enabled='Y' AND (multiple_feed_destinations!='Y' OR (multiple_feed_destinations='Y' AND feed_file_name='".$product["controlled_by_feed"]."'))");

				$product['price'] = price_format(GetGooglePrice($product));
                
   				$postBody["entries"][$k]["batchId"] = $v["productid"];
				$postBody["entries"][$k]["merchantId"] = $MerchantID;
				$postBody["entries"][$k]["storeCode"] = "online";
				$postBody["entries"][$k]["productId"] = "online:en:US:".$v["productid"];
                $postBody["entries"][$k]["method"] = "insert";
                
				$postBody["entries"][$k]["product"]["price"]["value"] = $product["price"];
				$postBody["entries"][$k]["product"]["price"]["currency"] = "USD";
				$postBody["entries"][$k]["product"]["availability"]= $product_availability;
                $postBody["entries"][$k]["product"]["offerId"] = $v["productid"];
				$postBody["entries"][$k]["product"]["kind"] = "content#product";
                $postBody["entries"][$k]["product"]["contentLanguage"] = "en";
                $postBody["entries"][$k]["product"]["targetCountry"] = "US";
                $postBody["entries"][$k]["product"]["channel"] = "online";
                $postBody["entries"][$k]["product"]["condition"] = "new";
                $postBody["entries"][$k]["product"]["title"] = $product["product"];

                /* get product link */
                $froogle_location = $config['Froogle']['froogle_used_https_links'] == 'Y' ? $https_location : $http_location;
                $froogle_scheme = $config['Froogle']['froogle_used_https_links'] == 'Y' ? 'https://' : 'http://';

                if(isset($product['sfid']) && $product['sfid'] != 0) {
                    $product['froogle_location'] = $froogle_scheme . func_get_http_location_sf($product['sfid']);
                } else {
                    $product['froogle_location'] = $froogle_location;
                }

                $clean_url_link = func_query_first_cell("SELECT clean_url FROM $sql_tbl[clean_urls] WHERE resource_type='P' AND resource_id='$product[productid]'");
                $clean_url_link .="/";

                $product['link'] = $product['froogle_location'] . constant('DIR_CUSTOMER') . '/'. $clean_url_link;
                
                $postBody["entries"][$k]["product"]["link"] = $product["link"];
                
                /* get detailed image link */
                $tmp_all = func_query("SELECT id, imageid, image_path FROM $sql_tbl[images_D] WHERE $sql_tbl[images_D].id = '$v[productid]' AND $sql_tbl[images_D].avail='Y' ORDER BY orderby");

                if (!empty($tmp_all) && is_array($tmp_all)){
                    foreach($tmp_all as $k_tmp => $tmp){

                        if (!empty($tmp['imageid'])) {

                            $tmbn_d = "";
                            $image_path = "";
                            $image_type = "";

                            $image_path = $tmp['image_path'];
                            $image_type = "D";

                            if (!empty($image_path))
                                $tmbn_d = func_get_image_url($tmp['imageid'], $image_type, $image_path);

                            if ($tmbn_d === false || empty($tmbn_d)) {
                                $tmbn_d = $product['froogle_location'] . '/image.php?id=' . $tmp['imageid'] . '&type=' . $image_type;
                            } elseif (strpos($tmbn_d, $https_location) !== false) {
                                $tmbn_d = str_replace($https_location, $product['froogle_location'], $tmbn_d);
                            }

                            if (strpos($tmbn_d, "default_image") !== false) {
                                $tmp_all[$k_tmp]["tmbn_no_img"] = "Y";
                            }

                            $tmp_all[$k_tmp]["tmbn_d"] = $tmbn_d;
                        }
                    }

                    foreach($tmp_all as $k_tmp => $tmp){
                        if ($tmp["tmbn_no_img"] != "Y"){
                                $tmbn = $tmp["tmbn_d"];
                                unset($tmp_all[$k_tmp]);
                                break;
                        }
                    }
                }


                $tmbn_no_img = "";
                if ((strpos($tmbn, "default_image") !== false) || empty($tmbn)) {
                    $tmbn_no_img = "Y";
                }

                if ($sf_info["config"]["Appearance"]["Enable_CDN"]=="Y" && !empty($sf_info["config"]["Appearance"]["CDN_domain"])){
                    $tmbn = str_replace($sf_info["domain"], $sf_info["config"]["Appearance"]["CDN_domain"], $tmbn);
                    $tmbn = str_replace("www.artistsupplysource.com", $sf_info["config"]["Appearance"]["CDN_domain"], $tmbn);

                }


				if ($sf_info["config"]["Appearance"]["Enable_CDN"]=="Y" && !empty($sf_info["config"]["Appearance"]["CDN_domain"])){
					$imgurl = (($sf_info["config"]["Appearance"]['https_enabled']=='Y') ? 'https://' : 'http://') . $sf_info["config"]["Appearance"]["CDN_domain"];
				} else {
					$imgurl = ($sf_info["config"]["Appearance"]['https_enabled']=='Y') ? $https_location : $http_location;
				}
				if (!empty($tmbn)) {
					$tmbn = $imgurl . $tmbn;
				} else {
					$tmbn = $imgurl . "/default_image.gif";
				}
				if (!empty($additional_image_link)) {
					$additional_image_link = $imgurl . $additional_image_link;
				}
				$tmp_image_link = $tmbn;

                $product['image_link'] = $tmp_image_link;
                $postBody["entries"][$k]["product"]["imagelink"] = $product["image_link"];

	}

    //func_print_r($postBody);

	$code = 200;
	if ($debug_mode != 'Y') {
		try {
			$k++;

			print("\nBing: tried to submit $k items as inventory feed ($MerchantID) \n");

			$log_text = "Bing: tried to submit $k items as inventory feed";
			func_backprocess_log("incremental feeds", $log_text);

			$json = json_encode($postBody);

			$baseuri = $sEndpoint;//"https://content.api.bingads.microsoft.com/shopping/v9.1";
			$bmcuri = $baseuri . "/bmc/" . $MerchantID;
			$batchuri = $bmcuri . "/products/batch";

			$query = "?alt=json";

			$url = $batchuri . $query;

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

//		$headers = [];
			$headers = array();
			$headers[] = 'Username: ' . $username;
			$headers[] = 'Password: ' . $password;
			$headers[] = 'DeveloperToken: ' . $token;
			$headers[] = 'Content-Type: application/json';
			$headers[] = 'Content-Length: ' . strlen($json);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

			$output = curl_exec($ch);

			$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($code != 200) {
				$log_text = "Bing: the operation failed with code = $code.\n";
				func_backprocess_log("incremental feeds", $log_text);
			}

			curl_close($ch);

			//echo $output . "\n";

			$results = json_decode($output);
###
			$results_arr = (array)$results;
			$log_text = "";
			foreach ($results_arr as $k => $v) {
				if (!empty($v) && is_array($v)) {
					foreach ($v as $kk => $vv) {
						if ($kk == "entries" && !empty($vv) && is_array($vv)) {
							foreach ($vv as $kkk => $vvv) {
								if (!empty($vvv["errors"])) {
									$log_text .= "batchId: " . $vvv["batchId"] . " code: " . $vvv["errors"]["code"] . " message: " . $vvv["errors"]["message"] . "\n";
								}
							}
						}
					}
				}
			}
			if (!empty($log_text)) {
				func_backprocess_log("incremental feeds", $log_text);
			}
###

		} catch (Exception $e) {
			print "Error code :" . $e->getCode() . "\n";
			// Error message is formatted as "Error calling <REQUEST METHOD> <REQUEST URL>: (<CODE>) <MESSAGE OR REASON>".
			print "Error message: " . $e->getMessage() . "\n";

			$log_text = "Error code :" . $e->getCode() . "\n" . "Error message: " . $e->getMessage();
			func_backprocess_log("incremental feeds", $log_text);
		}
	}
	return $code;
}

function SubmitBingProductsBatch($bproducts, $sEndpoint, $MerchantID, $CatalogID, $username, $password, $token, $debug_mode = 'N')
{
	global $sql_tbl;

	$count_skipped = 0;
	$k_counter = 0;

	foreach ($bproducts as $k => $v){

		//$product_info = GetGoogleBaseOneRow($v["productid"], "main_google");
		$product_info = $v["product_info"];

		$pforsale = func_query_first_cell("SELECT SQL_NO_CACHE $sql_tbl[products].forsale FROM $sql_tbl[products] WHERE $sql_tbl[products].productid = '$v[productid]'");



		if ( $pforsale == 'Y' && empty($product_info["product"]["shippings_google_arr"])){
			print("\nProduct skipped - $v[productid] \n");

			$log_text = "Product skipped shipping null for sale item - ".$v["productid"];
			func_backprocess_log("incremental feeds", $log_text);

			$count_skipped++;

			continue;
		}





		if ($pforsale == 'N' || (empty($product_info["product"]) || !is_array($product_info["product"])) ) {
			$postBody["entries"][$k_counter]["batchId"] = $v["productid"];
			$postBody["entries"][$k_counter]["merchantId"] = $MerchantID;
			$postBody["entries"][$k_counter]["method"] = "delete";
			$postBody["entries"][$k_counter]["productId"] = "online:en:US:".$v["productid"];
			$k_counter++;


		} else
		{
			$postBody["entries"][$k_counter]["batchId"] = $v["productid"];
			$postBody["entries"][$k_counter]["merchantId"] = $MerchantID;
			$postBody["entries"][$k_counter]["method"] = "insert";
			$postBody["entries"][$k_counter]["productId"] = "online:en:US:".$v["productid"];
			$postBody["entries"][$k_counter]["product"]["kind"] = "content#product";
			$postBody["entries"][$k_counter]["product"]["offerId"] = $v["productid"];
			$postBody["entries"][$k_counter]["product"]["title"] = $product_info["product"]["google_product"];
			$postBody["entries"][$k_counter]["product"]["description"] = $product_info["product"]["google_descr"];
			$postBody["entries"][$k_counter]["product"]["link"] = $product_info["product"]["link"];
			$postBody["entries"][$k_counter]["product"]["imageLink"] = $product_info["product"]["image_link"];
			$postBody["entries"][$k_counter]["product"]["contentLanguage"] = "en";
			$postBody["entries"][$k_counter]["product"]["targetCountry"] = "US";
			$postBody["entries"][$k_counter]["product"]["channel"] = "online";
###
			//$product_availability = func_product_availability(false,$product_info["product"]);
			$product_availability = 'in stock';
###
			$postBody["entries"][$k_counter]["product"]["availability"] = $product_availability;
			$postBody["entries"][$k_counter]["product"]["brand"] = $product_info["product"]["google_brand"];
			$postBody["entries"][$k_counter]["product"]["condition"] = "new";
            if (!empty($product_info["product"]["upc"])) {
                $postBody["entries"][$k_counter]["product"]["gtin"] = "".$product_info['product']['upc'];
            }
			$postBody["entries"][$k_counter]["product"]["mpn"] = $product_info["product"]["mpn"];
			$postBody["entries"][$k_counter]["product"]["price"]["value"] = $product_info["product"]["price"];
			$postBody["entries"][$k_counter]["product"]["price"]["currency"] = "USD";
			if (!empty($product_info["product"]["cats_path"]))
				$postBody["entries"][$k_counter]["product"]["productType"] = $product_info["product"]["cats_path"];
			if (trim($product_info["product"]["gpc"]) != '') $postBody["entries"][$k_counter]["product"]["googleProductCategory"] = $product_info["product"]["gpc"];

#
##
			if (!empty($product_info["product"]["multipack"])){
				$postBody["entries"][$k_counter]["product"]["multipack"] = $product_info["product"]["multipack"];
			}
##
#

			$postBody["entries"][$k_counter]["product"]["shipping"] = $product_info["product"]["shippings_google_arr"];
			$postBody["entries"][$k_counter]["product"]["shippingWeight"]["value"] = $product_info["product"]["weight"];
			$postBody["entries"][$k_counter]["product"]["shippingWeight"]["unit"] = "lb";

#
##
		/*Custom Labels*/
            $postBody["entries"][$k_counter]["product"]["customLabel0"] = $product_info["product"]["custom_label_0"];
            $postBody["entries"][$k_counter]["product"]["customLabel1"] = $product_info["product"]["custom_label_1"];
            $postBody["entries"][$k_counter]["product"]["customLabel2"] = $product_info["product"]["custom_label_2"];
            $postBody["entries"][$k_counter]["product"]["customLabel3"] = $product_info["product"]["custom_label_3"];

			$postBody["entries"][$k_counter]["product"]["adwordsGrouping"] = $product_info["product"]["adwords_grouping"];
			$postBody["entries"][$k_counter]["product"]["adwordsLabels"][0] = $product_info["product"]["adwords_labels"];
			$postBody["entries"][$k_counter]["product"]["adwordsRedirect"] = $product_info["product"]["bing_adwords_redirect"];

			$postBody["entries"][$k_counter]["product"]["destinations"][0]["destinationName"] = "ShoppingApi";
			$postBody["entries"][$k_counter]["product"]["destinations"][0]["intention"] = "required";
			$postBody["entries"][$k_counter]["product"]["destinations"][1]["destinationName"] = "AffiliateNetwork";
			$postBody["entries"][$k_counter]["product"]["destinations"][1]["intention"] = "required";
			$postBody["entries"][$k_counter]["product"]["destinations"][2]["destinationName"] = "Shopping";
			$postBody["entries"][$k_counter]["product"]["destinations"][2]["intention"] = "required";

			$postBody["entries"][$k_counter]["product"]["onlineOnly"] = $product_info["product"]["onlineOnly"];

			$postBody["entries"][$k_counter]["product"]["customAttributes"][0]["name"] = "payment accepted";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][0]["type"] = "text";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][0]["value"] = "check";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][1]["name"] = "payment accepted";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][1]["type"] = "text";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][1]["value"] = "visa";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][2]["name"] = "payment accepted";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][2]["type"] = "text";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][2]["value"] = "mastercard";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][3]["name"] = "payment accepted";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][3]["type"] = "text";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][3]["value"] = "discover";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][4]["name"] = "payment accepted";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][4]["type"] = "text";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][4]["value"] = "american express";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][5]["name"] = "payment accepted";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][5]["type"] = "text";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][5]["value"] = "All purchase orders are subject to verification.";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][6]["name"] = "quantity";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][6]["type"] = "int";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][6]["value"] = $product_info["product"]["r_avail"];
			$postBody["entries"][$k_counter]["product"]["customAttributes"][7]["name"] = "model number";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][7]["type"] = "text";
			$postBody["entries"][$k_counter]["product"]["customAttributes"][7]["value"] = $product_info["product"]["mpn"];

			$k_counter++;
		}
	}
	$code = 200;
if ($debug_mode != 'Y') {

	try {

		print("\nBing: tried to submit $k_counter items as product feed ($MerchantID) \n");

		$log_text = "Bing: tried to submit $k_counter items as product feed ($MerchantID)";
		func_backprocess_log("incremental feeds", $log_text);

		$json = json_encode($postBody);

		if ($json == "null") {
			func_print_r("json = json_encode(postBody); print_r(postBody):", $postBody);
		}

		$baseuri = $sEndpoint;//"https://content.api.bingads.microsoft.com/shopping/v9.1";
		$bmcuri = $baseuri . "/bmc/" . $MerchantID;
		$batchuri = $bmcuri . "/products/batch";
		$query = "?bmc-catalog-id=" . $CatalogID . "&alt=json";

		$url = $batchuri . $query;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

//		$headers = [];
		$headers = array();
		$headers[] = 'Username: ' . $username;
		$headers[] = 'Password: ' . $password;
		$headers[] = 'DeveloperToken: ' . $token;
		$headers[] = 'Content-Type: application/json';
		$headers[] = 'Content-Length: ' . strlen($json);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

		$output = curl_exec($ch);

		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($code != 200) {
			$log_text = "Bing: the operation failed with code = $code.\n";
			func_backprocess_log("incremental feeds", $log_text);
		}


		curl_close($ch);

		//echo $output . "\n";

		$results = json_decode($output);
###
		$results_arr = (array)$results;
		$log_text = "";
		foreach ($results_arr as $k => $v) {
			if (!empty($v) && is_array($v)) {
				foreach ($v as $kk => $vv) {
					if ($kk == "entries" && !empty($vv) && is_array($vv)) {
						foreach ($vv as $kkk => $vvv) {
							if (!empty($vvv["errors"])) {
								$log_text .= "batchId: " . $vvv["batchId"] . " code: " . $vvv["errors"]["code"] . " message: " . $vvv["errors"]["message"] . "\n";
							}
						}
					}
				}
			}
		}
		if (!empty($log_text)) {
			func_backprocess_log("incremental feeds", $log_text);
		}
###

	} catch (Exception $e) {
		print "Error code :" . $e->getCode() . "\n";
		// Error message is formatted as "Error calling <REQUEST METHOD> <REQUEST URL>: (<CODE>) <MESSAGE OR REASON>".
		print "Error message: " . $e->getMessage() . "\n";

		$log_text = "Error code :" . $e->getCode() . "\n" . "Error message: " . $e->getMessage();
		func_backprocess_log("incremental feeds", $log_text);
	}
}
return $code;

}



function AddProductToBingBaseBatch($productid,$update_type,$forsale,$bing_products_batch_count,$bproducts,$bing_inventory_batch_count,$binventory)
{

	if ($update_type == "1" || $update_type == "1,2" || (($update_type == "2" && $forsale == "N"))){
		$batchid = $bing_products_batch_count;
		$count_bproducts = count($bproducts);
		$bproducts[$count_bproducts]["productid"] = $productid;
		$bproducts[$count_bproducts]["Batchid"] = $batchid;
		$bing_products_batch_count++;
	}
	elseif ($update_type == "2" && $forsale == "Y"){
		$batchid = $bing_inventory_batch_count;
		$count_binventory = count($binventory);
		$binventory[$count_binventory]["productid"] = $productid;
		$binventory[$count_binventory]["Batchid"] = $batchid;
		$bing_inventory_batch_count++;
	}

	$AddProductToBingBaseBatch_arr["bing_products_batch_count"] = $bing_products_batch_count;
	$AddProductToBingBaseBatch_arr["bproducts"] = $bproducts;
	$AddProductToBingBaseBatch_arr["bing_inventory_batch_count"] = $bing_inventory_batch_count;
	$AddProductToBingBaseBatch_arr["binventory"] = $binventory;

	return $AddProductToBingBaseBatch_arr;

}

function SubmitGoogleProductsBatch($gproducts, $service, $MerchantID, $debug_mode = 'N'){
	global $sql_tbl, $froogle_tracing_token, $debug_requests;

	$count_skipped = 0;
	$k_counter = 0;


	foreach ($gproducts as $k => $v){

		$product_info = $v['product_info'];
		$pforsale = $product_info['product']['forsale'];

        if ( $pforsale == 'Y' && empty($product_info["product"]["shippings_google_arr"])){
			print("\nProduct skipped - $v[productid] \n");

	        $log_text = "Product skipped shipping null for sale item- ".$v["productid"];
        	func_backprocess_log("incremental feeds", $log_text);

			$count_skipped++;
			continue;
		}

		
		if ($pforsale == 'N' || (empty($product_info["product"]) || !is_array($product_info["product"])) || ($product_info["product"]["min_amount"]>1))  {
                $postBody["entries"][$k_counter]["batchId"] = $v["productid"];
	            $postBody["entries"][$k_counter]["merchantId"] = $MerchantID;
    	        $postBody["entries"][$k_counter]["method"] = "delete";
        	    $postBody["entries"][$k_counter]["productId"] = "online:en:US:".$v["productid"];
		    $k_counter++;
		    
		
		} else
		{
                $postBody["entries"][$k_counter]["batchId"] = $v["productid"];
                $postBody["entries"][$k_counter]["merchantId"] = $MerchantID;
                $postBody["entries"][$k_counter]["method"] = "insert";
                $postBody["entries"][$k_counter]["productId"] = "online:en:US:".$v["productid"];
                $postBody["entries"][$k_counter]["product"]["kind"] = "content#product";
                $postBody["entries"][$k_counter]["product"]["id"] = "online:en:US:".$v["productid"];
                $postBody["entries"][$k_counter]["product"]["offerId"] = $v["productid"];
                $postBody["entries"][$k_counter]["product"]["title"] = $product_info["product"]["google_product"];
                $postBody["entries"][$k_counter]["product"]["description"] = $product_info["product"]["google_descr"];
                $postBody["entries"][$k_counter]["product"]["link"] = $product_info["product"]["link"];
                $postBody["entries"][$k_counter]["product"]["imageLink"] = $product_info["product"]["image_link"];
                $postBody["entries"][$k_counter]["product"]["contentLanguage"] = "en";
                $postBody["entries"][$k_counter]["product"]["targetCountry"] = "US";
                $postBody["entries"][$k_counter]["product"]["channel"] = "online";


				$product_availability = func_product_availability(false,$product_info["product"]);

			
                $postBody["entries"][$k_counter]["product"]["availability"] = $product_availability;
                $postBody["entries"][$k_counter]["product"]["brand"] = $product_info["product"]["google_brand"];
                $postBody["entries"][$k_counter]["product"]["condition"] = "new";
                if (!empty($product_info["product"]["upc"])) {
                    $postBody["entries"][$k_counter]["product"]["gtin"] = "".$product_info['product']['upc'];
                }
                $postBody["entries"][$k_counter]["product"]["mpn"] = $product_info["product"]["mpn"];

                $postBody["entries"][$k_counter]["product"]["price"]["value"] = $product_info["product"]["price"];
                $postBody["entries"][$k_counter]["product"]["price"]["currency"] = "USD";
				if (!empty($product_info["product"]["cats_path"]))
                	$postBody["entries"][$k_counter]["product"]["productType"] = $product_info["product"]["cats_path"];
                if (trim($product_info["product"]["gpc"]) != '') $postBody["entries"][$k_counter]["product"]["googleProductCategory"] = $product_info["product"]["gpc"];

#
##
                if (!empty($product_info["product"]["multipack"])){
                        $postBody["entries"][$k_counter]["product"]["multipack"] = $product_info["product"]["multipack"];
                }
##
#

                $postBody["entries"][$k_counter]["product"]["shipping"] = $product_info["product"]["shippings_google_arr"];
                $postBody["entries"][$k_counter]["product"]["shippingWeight"]["value"] = $product_info["product"]["weight"];
                $postBody["entries"][$k_counter]["product"]["shippingWeight"]["unit"] = "lb";

#
##
				if ($product_info["product"]["dim_z"] > 0 && $product_info["product"]["dim_x"] > 0 && $product_info["product"]["dim_y"] > 0){
	                $postBody["entries"][$k_counter]["product"]["shippingHeight"]["unit"] = "in";
        	        $postBody["entries"][$k_counter]["product"]["shippingHeight"]["value"] = $product_info["product"]["dim_z"];

	                $postBody["entries"][$k_counter]["product"]["shippingLength"]["unit"] = "in";
	                $postBody["entries"][$k_counter]["product"]["shippingLength"]["value"] = max($product_info["product"]["dim_x"], $product_info["product"]["dim_y"]);

	                $postBody["entries"][$k_counter]["product"]["shippingWidth"]["unit"] = "in";
        	        $postBody["entries"][$k_counter]["product"]["shippingWidth"]["value"] = min($product_info["product"]["dim_x"], $product_info["product"]["dim_y"]);
			}
##
#

                $postBody["entries"][$k_counter]["product"]["adwordsGrouping"] = $product_info["product"]["adwords_grouping"];
                $postBody["entries"][$k_counter]["product"]["adwordsLabels"][0] = $product_info["product"]["adwords_labels"];
                $postBody["entries"][$k_counter]["product"]["adwordsRedirect"] = $product_info["product"]["adwords_redirect"];

		/*Custom Labels*/
                $postBody["entries"][$k_counter]["product"]["customLabel0"] = $product_info["product"]["custom_label_0"];
                $postBody["entries"][$k_counter]["product"]["customLabel1"] = $product_info["product"]["custom_label_1"];
                $postBody["entries"][$k_counter]["product"]["customLabel2"] = $product_info["product"]["custom_label_2"];
                $postBody["entries"][$k_counter]["product"]["customLabel3"] = $product_info["product"]["custom_label_3"];

                /*$postBody["entries"][$k_counter]["product"]["destinations"][0]["destinationName"] = "ShoppingApi";
                $postBody["entries"][$k_counter]["product"]["destinations"][0]["intention"] = "required";
                $postBody["entries"][$k_counter]["product"]["destinations"][1]["destinationName"] = "AffiliateNetwork";
                $postBody["entries"][$k_counter]["product"]["destinations"][1]["intention"] = "required";
                $postBody["entries"][$k_counter]["product"]["destinations"][2]["destinationName"] = "Shopping";
                $postBody["entries"][$k_counter]["product"]["destinations"][2]["intention"] = "required";*/

                $postBody["entries"][$k_counter]["product"]["onlineOnly"] = $product_info["product"]["onlineOnly"];

                $postBody["entries"][$k_counter]["product"]["customAttributes"][0]["name"] = "payment accepted";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][0]["type"] = "text";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][0]["value"] = "check";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][1]["name"] = "payment accepted";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][1]["type"] = "text";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][1]["value"] = "visa";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][2]["name"] = "payment accepted";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][2]["type"] = "text";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][2]["value"] = "mastercard";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][3]["name"] = "payment accepted";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][3]["type"] = "text";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][3]["value"] = "discover";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][4]["name"] = "payment accepted";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][4]["type"] = "text";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][4]["value"] = "american express";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][5]["name"] = "payment accepted";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][5]["type"] = "text";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][5]["value"] = "All purchase orders are subject to verification.";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][6]["name"] = "quantity";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][6]["type"] = "int";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][6]["value"] = $product_info["product"]["r_avail"];
                $postBody["entries"][$k_counter]["product"]["customAttributes"][7]["name"] = "model number";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][7]["type"] = "text";
                $postBody["entries"][$k_counter]["product"]["customAttributes"][7]["value"] = $product_info["product"]["mpn"];

		$k_counter++;
		}
	}
	$code = 200;
if ($debug_mode != 'Y') {

	try {

		print("\nGB: tried to submit $k_counter items as product feed ($MerchantID)\n");

		$log_text = "GB: tried to submit $k_counter items as product feed ($MerchantID)";
		func_backprocess_log("incremental feeds", $log_text);

		$params = array();
		$params["postBody"] = $postBody;
		if (!empty($froogle_tracing_token)) {
			$params['trace'] = 'token:' . $froogle_tracing_token;
		}

		print("GB: call custombatch return Google_Service_ShoppingContent_ProductsCustomBatchResponse");

		$results = $service->products->call('custombatch', array($params), "Google_Service_ShoppingContent_ProductsCustomBatchResponse");

		print("GB: call custombatch end");

		if ($debug_requests == "Y") {
			func_print_r($results);
		}


###
		$results_arr = (array)$results;
		$log_text = "";
		foreach ($results_arr as $k => $v) {
			if (!empty($v) && is_array($v)) {
				foreach ($v as $kk => $vv) {
					if ($kk == "entries" && !empty($vv) && is_array($vv)) {
						foreach ($vv as $kkk => $vvv) {
							if (!empty($vvv["errors"])) {
								$log_text .= "batchId: " . $vvv["batchId"] . " code: " . $vvv["errors"]["code"] . " message: " . $vvv["errors"]["message"] . "\n";
								$code = $vvv["errors"]["code"];
							}
						}
					}
				}
			}
		}
		print("GB: end process results");

		if (!empty($log_text)) {
			func_backprocess_log("incremental feeds", $log_text);
		}
###

	} catch (Google_Exception $e) {
		// Other error.
		print "An error occurred: (" . $e->getCode() . ") " . $e->getMessage() . "\n";

		$log_text = "An error occurred: (" . $e->getCode() . ") " . $e->getMessage();
		func_backprocess_log("incremental feeds", $log_text);
	}
	catch (Exception $e) {
		// Other error.
		print "An error occurred: (" . $e->getCode() . ") " . $e->getMessage() . "\n";

		$log_text = "An error occurred: (" . $e->getCode() . ") " . $e->getMessage();
		func_backprocess_log("incremental feeds", $log_text);
	}
}


return $code;

}

function SubmitAmazonInventoryBatch($ainventory, $a_config, $marketplaceIdArray, \Xcart\External_Marketplaces\StoreFrontMarketPlace $oMarketPlace){
        global $sql_tbl, $xcart_dir;

	if (empty($ainventory) || !is_array($ainventory)){
	        print('Amazon inventory empty\r');
		return false;
	}
	$sMerchantId = $ainventory[''];
######################### Avail start #########################
$feed = <<<EOD
<?xml version="1.0" encoding="utf-8" ?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
<Header>
   <DocumentVersion>1.01</DocumentVersion>
   <MerchantIdentifier>$sMerchantId</MerchantIdentifier>
   </Header>
   <MessageType>Inventory</MessageType>
EOD;

	$MessageID = 1;
	foreach ($ainventory as $k => $v) {

		$oProduct = \Xcart\Product::model(['productid' => $v["productid"]]);
		$oProductAmazonFields = \Xcart\ProductsAmazonFields::model(['productid' => $v["productid"]]);
		$productcode = $oProduct->getSKU();

		$aFBAProductCodes = null;

			if ($oProduct->isAmazonFBAEnabled() &&
				($oProduct->getAmazonFBAAvailReal() > 0 || $oProduct->getAmazonFBAStockReservedTransfers() > 0) &&
				!in_array($oProductAmazonFields->getPreventSellingOnAmazon(), ['FBA', 'MFN'])
			) {
				$aFBAProductCodes[] =  $productcode;
				$aMissingSKU = \Xcart\FbaMissingSku::model()->findAll(\Xcart\SQLBuilder::getInstance()->addCondition('productid = '.$oProduct->getProductId()));
				if (!empty($aMissingSKU)) {
					foreach ($aMissingSKU as $oMissingSKU) {
						$aFBAProductCodes[] = $oMissingSKU->getMissingSKU();
					}
				}

				foreach ($aFBAProductCodes as $sProductCode) {

					$feed .= <<<EOD
<Message>
<MessageID>$MessageID</MessageID>
<OperationType>Update</OperationType>
<Inventory>
<SKU>$sProductCode</SKU>
<FulfillmentCenterID>AMAZON_NA</FulfillmentCenterID>
<Lookup>FulfillmentNetwork</Lookup>
<SwitchFulfillmentTo>AFN</SwitchFulfillmentTo>
</Inventory>
</Message>
EOD;
					$MessageID++;
				}
			} else {

				$avail = $oProduct->getAmazonQuantity();
				if ($oProductAmazonFields->getPreventSellingOnAmazon() == 'MFN' ||
					!($oMarketPlace->checkProductExcludedMarketPlace($oProduct->getProductId()))) {
					$avail = 0;
				}
				$aleadtime = $oProduct->getManfacturerClass()->getAmazonLeadtimetoship();

				$feed .= <<<EOD
<Message>
<MessageID>$MessageID</MessageID>
<OperationType>Update</OperationType>
<Inventory>
<SKU>$productcode</SKU>
<FulfillmentCenterID>DEFAULT</FulfillmentCenterID>
<Quantity>$avail</Quantity>
<FulfillmentLatency>$aleadtime</FulfillmentLatency>
<SwitchFulfillmentTo>MFN</SwitchFulfillmentTo>
</Inventory>
</Message>
EOD;
				$MessageID++;
			}
	}

	$feed .= <<<EOD
</AmazonEnvelope>
EOD;

	print($feed."\n\n");

	print("INVENTORY pull\n\n");
	
	$a_service = new MarketplaceWebService_Client(
	     AWS_ACCESS_KEY_ID,
	     AWS_SECRET_ACCESS_KEY,
	     $a_config,
	     APPLICATION_NAME,
	     APPLICATION_VERSION);


	$feedHandle = @fopen('php://temp', 'rw+');
	fwrite($feedHandle, $feed);
	if(!$feedHandle) die("Can't open device");
	rewind($feedHandle);


	$parameters = array (
	  'Merchant' => MERCHANT_ID,
	  'MarketplaceIdList' => $marketplaceIdArray,
	  'FeedType' => '_POST_INVENTORY_AVAILABILITY_DATA_',
	  'FeedContent' => $feedHandle,
	  'PurgeAndReplace' => false,
	  'ContentMd5' => base64_encode(md5(stream_get_contents($feedHandle), true)),
	//  'MWSAuthToken' => '<MWS Auth Token>', // Optional
	);

	$request = new MarketplaceWebService_Model_SubmitFeedRequest($parameters);

	invokeSubmitFeed($a_service, $request);

	@fclose($feedHandle);

######################### Avail End #########################

######################### Price start #########################
        $feed = <<<EOD
<?xml version="1.0" encoding="utf-8" ?>
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
<Header>
   <DocumentVersion>1.01</DocumentVersion>
   <MerchantIdentifier>$sMerchantId</MerchantIdentifier>
   </Header>
   <MessageType>Price</MessageType>
EOD;

        $MessageID = 1;
        foreach ($ainventory as $k => $product){

			$aFBAProductCodes = null;
			$oProduct = \Xcart\Product::model(['productid' => $product["productid"]]);
			$price = $oProduct->getAmazonPrice();


			$aFBAProductCodes[] = $oProduct->getSKU();

			if ($oProduct->isAmazonFBAEnabled() && ($oProduct->getAmazonFBAAvailReal() > 0 || $oProduct->getAmazonFBAStockReservedTransfers() > 0)) {
				$aMissingSKU = \Xcart\FbaMissingSku::model()->findAll(\Xcart\SQLBuilder::getInstance()->addCondition('productid = '.$oProduct->getProductId()));
				if (!empty($aMissingSKU)) {
					foreach ($aMissingSKU as $oMissingSKU) {
						$aFBAProductCodes[] = $oMissingSKU->getMissingSKU();
					}
				}
			}

			foreach ($aFBAProductCodes as $sProductCode) {
                    $feed .= <<<EOD
<Message>
<MessageID>$MessageID</MessageID>
<Price>
<SKU>$sProductCode</SKU>
<StandardPrice currency="USD">$price</StandardPrice>
</Price>
</Message>
EOD;
				$MessageID++;
			}
        }

        $feed .= <<<EOD
</AmazonEnvelope>
EOD;

	print($feed."\n\n");

	print("INVENTORY pull\n\n");

 
        $a_service = new MarketplaceWebService_Client(
             AWS_ACCESS_KEY_ID,
             AWS_SECRET_ACCESS_KEY,
             $a_config,
             APPLICATION_NAME,
             APPLICATION_VERSION);


        $feedHandle = @fopen('php://temp', 'rw+');
        fwrite($feedHandle, $feed);
        if(!$feedHandle) die("Can't open device");
        rewind($feedHandle);


        $parameters = array (
          'Merchant' => MERCHANT_ID,
          'MarketplaceIdList' => $marketplaceIdArray,
          'FeedType' => '_POST_PRODUCT_PRICING_DATA_',
          'FeedContent' => $feedHandle,
          'PurgeAndReplace' => false,
          'ContentMd5' => base64_encode(md5(stream_get_contents($feedHandle), true)),
        //  'MWSAuthToken' => '<MWS Auth Token>', // Optional
        );

        $request = new MarketplaceWebService_Model_SubmitFeedRequest($parameters);

        invokeSubmitFeed($a_service, $request);

        @fclose($feedHandle);
######################### Price end #########################
		if ($MessageID-- > 0) {
			print("\nAMZ: tried to submit $MessageID items as inventory feed \n");
			$log_text = "AMZ: tried to submit $MessageID items as inventory feed";
			func_backprocess_log("incremental feeds", $log_text);
		}

}

function SubmitAmazonProductsBatch(){
        global $sql_tbl;

}

function invokeSubmitFeed(MarketplaceWebService_Interface $a_service, $request)
  {
      try {
              $response = $a_service->submitFeed($request);

                echo ("Service Response\n");
                echo ("=============================================================================\n");

                echo("        SubmitFeedResponse\n");
                if ($response->isSetSubmitFeedResult()) {
                    echo("            SubmitFeedResult\n");
                    $submitFeedResult = $response->getSubmitFeedResult();
                    if ($submitFeedResult->isSetFeedSubmissionInfo()) {
                        echo("                FeedSubmissionInfo\n");
                        $feedSubmissionInfo = $submitFeedResult->getFeedSubmissionInfo();
                        if ($feedSubmissionInfo->isSetFeedSubmissionId())
                        {
                            echo("                    FeedSubmissionId\n");
                            echo("                        " . $feedSubmissionInfo->getFeedSubmissionId() . "\n");
                        }
                        if ($feedSubmissionInfo->isSetFeedType())
                        {
                            echo("                    FeedType\n");
                            echo("                        " . $feedSubmissionInfo->getFeedType() . "\n");
                        }
                        if ($feedSubmissionInfo->isSetSubmittedDate())
                        {
                            echo("                    SubmittedDate\n");
                            echo("                        " . $feedSubmissionInfo->getSubmittedDate()->format(DATE_FORMAT) . "\n");
                        }
                        if ($feedSubmissionInfo->isSetFeedProcessingStatus())
                        {
                            echo("                    FeedProcessingStatus\n");
                            echo("                        " . $feedSubmissionInfo->getFeedProcessingStatus() . "\n");
                        }
                        if ($feedSubmissionInfo->isSetStartedProcessingDate())
                        {
                            echo("                    StartedProcessingDate\n");
                            echo("                        " . $feedSubmissionInfo->getStartedProcessingDate()->format(DATE_FORMAT) . "\n");
                        }
                        if ($feedSubmissionInfo->isSetCompletedProcessingDate())
                        {
                            echo("                    CompletedProcessingDate\n");
                            echo("                        " . $feedSubmissionInfo->getCompletedProcessingDate()->format(DATE_FORMAT) . "\n");
                        }
                    }
                }
                if ($response->isSetResponseMetadata()) {
                    echo("            ResponseMetadata\n");
                    $responseMetadata = $response->getResponseMetadata();
                    if ($responseMetadata->isSetRequestId())
                    {
                        echo("                RequestId\n");
                        echo("                    " . $responseMetadata->getRequestId() . "\n");
                    }
                }

                echo("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");
     } catch (MarketplaceWebService_Exception $ex) {
         echo("Caught Exception: " . $ex->getMessage() . "\n");
         echo("Response Status Code: " . $ex->getStatusCode() . "\n");
         echo("Error Code: " . $ex->getErrorCode() . "\n");
         echo("Error Type: " . $ex->getErrorType() . "\n");
         echo("Request ID: " . $ex->getRequestId() . "\n");
         echo("XML: " . $ex->getXML() . "\n");
         echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
     }

//     return $feedSubmissionInfo->getFeedProcessingStatus();

}

function Submit_expirationDate_ToGBFeed($productid, $MerchantID, $client_id, $key_file_location, $service){
	global $sql_tbl;

	try {
		$results = $service->products->get($MerchantID, "online:en:US:".$productid);

		$postBody = $results->toSimpleObject();
		$postBody = (array)$postBody;

		$expirationDate = time()+60*60*24*30;
		$expirationDate = date("Y-m-d", $expirationDate);
		$postBody["expirationDate"] = $expirationDate;

		$optParams = array();
		$params = array('merchantId' => $MerchantID, 'postBody' => $postBody);
		$params = array_merge($params, $optParams);
		$results2 = $service->products->call('insert', array($params), "Google_Service_ShoppingContent_Product");
	}
	catch (Google_ServiceException $e) {
		print "Error code :" . $e->getCode() . "\n";
		// Error message is formatted as "Error calling <REQUEST METHOD> <REQUEST URL>: (<CODE>) <MESSAGE OR REASON>".
		print "Error message: " . $e->getMessage() . "\n";
	}
	catch (Google_Exception $e) {
		// Other error.
		print "An error occurred: (" . $e->getCode() . ") " . $e->getMessage() . "\n";
		if ($e->getCode() == '404') {
		}
	}
}
