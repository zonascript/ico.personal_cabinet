<?php

x_load('cart','mail','order','product','taxes', 'files', 'backoffice', "image", "gd", "xml");

function func_GENERAL_ALV_FEED($manufacturerid){
	global $sql_tbl, $xcart_dir, $launch_time, $function_launch_time;

	$file_is_found_and_uploaded = false;
	$file_is_found = false;
	$count_updated_products = 0;
	$count_marked_as_out_of_stock_products = 0;
	$count_marked_as_in_stock_products = 0;

	if (function_exists("ftp_connect")) {

		$general_info = func_query_first("SELECT manufacturer, d_ftp_host, d_ftp_login, d_ftp_password, d_ftp_folder, d_product_management_team_email FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");

		$ftp = ftp_connect($general_info["d_ftp_host"]);
		if ($ftp && @ftp_login($ftp, $general_info["d_ftp_login"], $general_info["d_ftp_password"])) {

			ftp_pasv($ftp, true);

			$local_file = $xcart_dir . "/files/product_feeds/" .$manufacturerid."_AlvinInventoryFeed.txt";
			$server_file = $general_info["d_ftp_folder"]."AlvinInventoryFeed.txt";

			if (@ftp_get($ftp, $local_file, $server_file, FTP_BINARY)) {
				$file_is_found = true;
			}

			ftp_quit($ftp);

		} else {
			print("Could not open host. (Distributor: ".$general_info["manufacturer"] .")<br />");
		}
	}

    if ($file_is_found){

#
##
###
//		$lines_in_file = count(file($local_file));

###
                $lines_in_file = 0;
                $handle = @fopen($local_file, "r");
                while (($buffer = fgets($handle, 20480)) !== false) {

                        $lines_in_file++;
                        if ($lines_in_file % 100 == 0) {
                                func_flush(".");
                                if($lines_in_file % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }
                }
                fclose($handle);
###


		$additional_info = func_query_first("SELECT d_last_feed_rows_processed, d_validation_threshold FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");

		if ($additional_info["d_last_feed_rows_processed"] > 0){
			$lines_in_file_DIV_last_feed_rows_processed = $lines_in_file/$additional_info["d_last_feed_rows_processed"];

			if ($lines_in_file_DIV_last_feed_rows_processed < $additional_info["d_validation_threshold"]){
			        $subj = "SUPPLIER FEED validation failed!!!";
			        $body = $general_info["manufacturer"] . ". Count lines in new file: ".$lines_in_file."\n";
			        $body .= "Last feed rows processed: ".$additional_info["d_last_feed_rows_processed"]."\n";
			        $body .= "processing stopped";
			        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
				return;
			}
		}
		db_query("UPDATE $sql_tbl[manufacturers] SET d_last_feed_rows_processed='$lines_in_file' WHERE manufacturerid='$manufacturerid'");
###
##
#
		$handle = @fopen($local_file, "r");
		if ($handle) {
		    $line_number = 0;
		    $NEW_PRODUCTS = array();
		    $discontinued_products = array();
		    $all_feed_productcodes = array();

		    print "<br />".$general_info["manufacturer"]."<br />";
		    print "First iteration:<br />";

		    while (($buffer = fgets($handle, 20480)) !== false) {
			$line_number++;

			if ($line_number % 100 == 0) {
				func_flush(".");
				if($line_number % 5000 == 0) {
					func_flush("<br />\n");
				}

				func_flush();
			}

/*
			if ($line_number < 10){
			        echo $line_number.": ".$buffer."<br />";
			}
*/
			
			if ($line_number > 1){

				$buffer_arr = explode("|", $buffer);

				$ITEM = trim(substr(trim($buffer_arr[0]), 1, -1));
				$ITEM = strtoupper($ITEM);
				$UPCEAN = trim(substr(trim($buffer_arr[1]), 1, -1));
				$Stock = trim(substr(trim($buffer_arr[2]), 1, -1));
				$ExpectedDate = trim(substr(trim($buffer_arr[3]), 1, -1));
				$DropShip  = trim(substr(trim($buffer_arr[4]), 1, -1));

				if ($Stock == ""){
					$Stock = 0;
				}

				$ExpectedDate_time = 0;
				if (!empty($ExpectedDate)){
					$ExpectedDate_arr = explode("/", $ExpectedDate);
					$day = $ExpectedDate_arr[1];
					$month = $ExpectedDate_arr[0];
					$year = "20".$ExpectedDate_arr[2];
					$ExpectedDate_time = mktime(0, 0, 0, $month, $day, $year);
				}

				$feed_productcode = "ALV-".$ITEM;
				$all_feed_productcodes[] = $feed_productcode;

				$product_info_arr = func_query_first("SELECT productid, productcode, forsale, avail, r_avail, eta_date_mm_dd_yyyy FROM $sql_tbl[products] WHERE productcode='".addslashes($feed_productcode)."'");

				if (!empty($product_info_arr)){

					$productid = $product_info_arr["productid"];
					$productcode = $product_info_arr["productcode"];
					$current_forsale = $product_info_arr["forsale"];
					$current_avail = $product_info_arr["r_avail"];
					$current_eta_date_mm_dd_yyyy = $product_info_arr["eta_date_mm_dd_yyyy"];
					$current_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($current_eta_date_mm_dd_yyyy, "m/d/Y");

					$product_is_updated = false;
					$marked_as_out_of_stock_products = false;
					$marked_as_in_stock_products = false;

					if ($current_forsale != "Y"){
//						db_query("UPDATE $sql_tbl[products] SET forsale='Y', update_search_index='Y' WHERE productid='$productid'");
						db_query("UPDATE $sql_tbl[products] SET forsale='Y' WHERE productid='$productid'");
						$product_is_updated = true;
					}

					$new_eta_date_mm_dd_yyyy_time = 0;
					$update_product = false;

	                                if ($DropShip == "N"){
        	                                if ($Stock > 0){
							$update_product = true;
							$new_avail = $Stock;

                	                        } elseif ($Stock == 0){
							$update_product = true;	
							$new_avail = $Stock;

        	                                        if ($ExpectedDate_time == 0){
        	                                                $new_eta_date_mm_dd_yyyy_time = time() + 60*60*24*20;
        	                                        } else {
        	                                                $new_eta_date_mm_dd_yyyy_time = $ExpectedDate_time;
	                                                }
        	                                }
                	                } elseif ($DropShip == "Y") {
	
        	                                if ($Stock > 0){
							$update_product = true;
							$new_avail = $Stock;
		
        	                                } elseif ($Stock == 0){
	
							$update_product = true;

        	                                        if ($ExpectedDate_time == 0){
								$new_avail = 1000000;
        	                                        } else {
								$new_avail = 0;
        	                                                $new_eta_date_mm_dd_yyyy_time = $ExpectedDate_time;
                                                	}
	                                        }
        	                        }

					if ($update_product){

						if ($new_eta_date_mm_dd_yyyy_time == 0){
							$new_eta_date_mm_dd_yyyy = "";
						} else {
							$new_eta_date_mm_dd_yyyy = date("m/d/Y", $new_eta_date_mm_dd_yyyy_time);
						}

                                                if ($new_avail == "0"){
	                                                if ($current_avail > 0){
        	                                                $marked_as_out_of_stock_products = true;
                                                        }
                                                } else {
                	                                if ($current_avail == 0){
                        	                                $marked_as_in_stock_products = true;
                                                        }
                                                }

//						db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy' WHERE productid='$productid'");
						db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy_time' WHERE productid='$productid'");

						if ($new_eta_date_mm_dd_yyyy != $current_eta_date_mm_dd_yyyy || $current_avail != $new_avail){
							$product_is_updated = true;
						}
					}

/*
					if ($DropShip == "N"){

						if ($Stock != $current_avail){
							db_query("UPDATE $sql_tbl[products] SET r_avail='$Stock' WHERE productcode='".addslashes($productcode)."'");
							$product_is_updated = true;

							if ($Stock == "0"){
								if ($current_avail > 0){
									$marked_as_out_of_stock_products = true;
								}
							} else {
								if ($current_avail == 0){
									$marked_as_in_stock_products = true;
								}
							}
						}

						if ($CorrectED_mm_dd_yyyy != $current_eta_date_mm_dd_yyyy){
							db_query("UPDATE $sql_tbl[products] SET eta_date_mm_dd_yyyy='$CorrectED_mm_dd_yyyy' WHERE productcode='".addslashes($productcode)."'");
							$product_is_updated = true;
						}

					} elseif ($DropShip == "Y" && $Stock > 0){

						if ($Stock != $current_avail){
							db_query("UPDATE $sql_tbl[products] SET r_avail='$Stock' WHERE productcode='".addslashes($productcode)."'");
							$product_is_updated = true;

							if ($current_avail == 0){
								$marked_as_in_stock_products = true;
							}
						}

						if ($CorrectED_mm_dd_yyyy != $current_eta_date_mm_dd_yyyy){
							db_query("UPDATE $sql_tbl[products] SET eta_date_mm_dd_yyyy='$CorrectED_mm_dd_yyyy' WHERE productcode='".addslashes($productcode)."'");
							$product_is_updated = true;
						}

					} elseif ($DropShip == "Y" && $Stock == 0){

						if (!empty($CorrectED_mm_dd_yyyy)){

							if ($Stock != $current_avail){
								db_query("UPDATE $sql_tbl[products] SET r_avail='$Stock' WHERE productcode='".addslashes($productcode)."'");
								$product_is_updated = true;

								if ($current_avail > 0){
									$marked_as_out_of_stock_products = true;
								}
							}

							if ($CorrectED_mm_dd_yyyy != $current_eta_date_mm_dd_yyyy){
								db_query("UPDATE $sql_tbl[products] SET eta_date_mm_dd_yyyy='$CorrectED_mm_dd_yyyy' WHERE productcode='".addslashes($productcode)."'");
								$product_is_updated = true;
							}

						} else {

							if ($current_avail != "1000000"){
								db_query("UPDATE $sql_tbl[products] SET r_avail='1000000' WHERE productcode='".addslashes($productcode)."'");
	                                                        $product_is_updated = true;

								if ($current_avail == 0){
	        	                                                $marked_as_in_stock_products = true;
								}
							}

							if ($current_eta_date_mm_dd_yyyy != ""){
								db_query("UPDATE $sql_tbl[products] SET eta_date_mm_dd_yyyy='' WHERE productcode='".addslashes($productcode)."'");
								$product_is_updated = true;
							}
						}
					}

*/

					if ($product_is_updated){
						$file_is_found_and_uploaded = true;
						$count_updated_products++;
					}

					if ($marked_as_out_of_stock_products){
						$count_marked_as_out_of_stock_products++;
					}

					if ($marked_as_in_stock_products){
						$count_marked_as_in_stock_products++;
					}
				} else {
					$NEW_PRODUCTS[] = $buffer;
				}
			}

		    }
		    if (!feof($handle)) {
		        echo "Error: unexpected fgets() fail\n";
		    }
		    fclose($handle);


 		    $count_products = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[products] WHERE productcode LIKE 'ALV-%' AND forsale='Y'");

		    if (!empty($all_feed_productcodes) && is_array($all_feed_productcodes)){

                        if ($count_products > 0){

				$ALV_products = db_query("SELECT productid, productcode, forsale, update_search_index FROM $sql_tbl[products] WHERE productcode LIKE 'ALV-%' AND forsale='Y'");

				$line_number = 0;
				print "<br />Second iteration:<br />";
				while ($product = db_fetch_array($ALV_products)) {

		                        $line_number++;
        		                if ($line_number % 100 == 0) {
                		                func_flush(".");
                        		        if($line_number % 5000 == 0) {
                                		        func_flush("<br />\n");
	                                	}

	        	                        func_flush();
        	        	        }

                                        $productcode = strtoupper(trim($product["productcode"]));

                                        if (!in_array($productcode, $all_feed_productcodes) && $product["forsale"] != "N") {
						$file_is_found_and_uploaded = true;
						$discontinued_products[] = $product;
						db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N' WHERE productid='".$product["productid"]."'");
/*
						if ($product["update_search_index"] == "N"){
							db_query("UPDATE $sql_tbl[products] SET update_search_index='D' WHERE productid='".$product["productid"]."'");
						}
*/
					}
				}
			}
		    }
		}
	

//	if ($file_is_found_and_uploaded){
		db_query("UPDATE $sql_tbl[manufacturers] SET d_most_recent_feed_updation_date='".$launch_time."' WHERE manufacturerid='$manufacturerid'");
//	}


	$count_discontinued_products = count($discontinued_products);
        if (!empty($discontinued_products) && is_array($discontinued_products)){

		$subj = "ALVIN FEED UPDATE - discontinued products";
		$body = "Discontinued products count: ".$count_discontinued_products."\n\n";

                $body .= "<table border='1'>\n<tr><td>ProductCode</td><td>Link to SF backend</td></tr>\n";
                foreach ($discontinued_products as $k => $v){
	                $store_url = "www.artistsupplysource.com";
                        $body .= "<tr><td> ".$v["productcode"]." </td><td> <a href='http://".$store_url."/admin/product_modify.php?productid=".$v["productid"]."'>http://".$store_url."/admin/product.php?productid=".$v["productid"]."</a> </td></tr>\n";
                }
                $body .= "</table>";

		func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

//                print"<br />For test purpose: discontinued_products:";
//                func_print_r($discontinued_products);
        }

	$count_NEW_PRODUCTS = count($NEW_PRODUCTS);
	if (!empty($NEW_PRODUCTS) && is_array($NEW_PRODUCTS)){
		$subj = "ALVIN FEED UPDATE - new products";
		$body = "Expected new products count: ".$count_NEW_PRODUCTS."\n\n";

		$body .= "ITEM|UPCEAN|Stock|ExpectedDate|DropShip\n";
		foreach ($NEW_PRODUCTS as $k => $v){
			$body .= $v."\n";
		}

		func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

//		print"<br />For test purpose: NEW_PRODUCTS:";
//		func_print_r($NEW_PRODUCTS);
	}

	$count_all_feed_productcodes = count($all_feed_productcodes);
	$sum_updated_products = $count_marked_as_out_of_stock_products + $count_marked_as_in_stock_products + $count_discontinued_products;

	$subj = "ALVIN FEED UPDATE - summary";
	$body = "ALVIN FEED UPDATE - summary";
	$body .= "products in storefront: ".$count_products."\n";
	$body .= "products in feed: ".$count_all_feed_productcodes."\n";
	$body .= "updated products: ".$sum_updated_products."\n";
	$body .= "marked as `out of stock` products: ".$count_marked_as_out_of_stock_products."\n";
//	$body .= "marked as `out of stock` products (in second iteration): ".$count_discontinued_products."\n";
	$body .= "marked as `in stock` products: ".$count_marked_as_in_stock_products."\n";
	$body .= "new products: ".$count_NEW_PRODUCTS."\n";
	$body .= "discontinued products: ".$count_discontinued_products."\n"; 
		$function_time = time() - $function_launch_time; 
		$function_time = $function_time/(60);
		$function_time = round($function_time,1); 
		$body .= "Duration: ".$function_time." Mins\n";

	func_backprocess_log("supplier feeds", $body);
//	func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");


        #Clear FTP files
        $ftp = ftp_connect($general_info["d_ftp_host"]);
        if ($ftp && @ftp_login($ftp, $general_info["d_ftp_login"], $general_info["d_ftp_password"])) {

                        ftp_pasv($ftp, true);

                        $server_file = $general_info["d_ftp_folder"]."AlvinInventoryFeed.txt";
                        $new_server_file = $general_info["d_ftp_folder"]."backup/AlvinInventoryFeed_".time().".txt";

                        if (ftp_rename($ftp, $server_file, $new_server_file)) {

                        } else {
                                echo "There was a problem while renaming $old_file to $new_file\n";
                        }

                        ftp_quit($ftp);

	} else {
                        print("Could not open host. (Distributor: ".$general_info["manufacturer"] .")<br />");
	}
        #
    } //if ($file_is_found)
}


function func_GENERAL_EDR_FEED($manufacturerid){
        global $sql_tbl, $xcart_dir, $launch_time, $function_launch_time;

        $file_is_found_and_uploaded = false;
        $file_is_found = false;
        $count_updated_products = 0;
        $count_marked_as_out_of_stock_products = 0;
        $count_marked_as_in_stock_products = 0;
	$current_time = time();

        if (function_exists("ftp_connect")) {

                $general_info = func_query_first("SELECT manufacturer, d_ftp_host, d_ftp_login, d_ftp_password, d_ftp_folder, d_product_management_team_email FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");

                $ftp = ftp_connect($general_info["d_ftp_host"]);
                if ($ftp && @ftp_login($ftp, $general_info["d_ftp_login"], $general_info["d_ftp_password"])) {

                        ftp_pasv($ftp, true);

                        $local_file = $xcart_dir . "/files/product_feeds/" .$manufacturerid."_ERQTY.csv";
                        $server_file = $general_info["d_ftp_folder"]."ERQTY.csv";

                        if (@ftp_get($ftp, $local_file, $server_file, FTP_BINARY)) {
                                $file_is_found = true;
                        }

                        ftp_quit($ftp);

                } else {
                        print("Could not open host. (Distributor: ".$general_info["manufacturer"] .")<br />");
                }
        }

    if ($file_is_found){

#
##
###
//                $lines_in_file = count(file($local_file));
###
                $lines_in_file = 0;
                $handle = @fopen($local_file, "r");
                while (($buffer = fgetcsv($handle, 20480, ",")) !== FALSE) {
                        $lines_in_file++;
                        if ($lines_in_file % 100 == 0) {
                                func_flush(".");
                                if($lines_in_file % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }
                }
                fclose($handle);
###

                $additional_info = func_query_first("SELECT d_last_feed_rows_processed, d_validation_threshold FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");

                if ($additional_info["d_last_feed_rows_processed"] > 0){
                        $lines_in_file_DIV_last_feed_rows_processed = $lines_in_file/$additional_info["d_last_feed_rows_processed"];

                        if ($lines_in_file_DIV_last_feed_rows_processed < $additional_info["d_validation_threshold"]){
                                $subj = "SUPPLIER FEED validation failed!!!";
                                $body = $general_info["manufacturer"] . ". Count lines in new file: ".$lines_in_file."\n";
                                $body .= "Last feed rows processed: ".$additional_info["d_last_feed_rows_processed"]."\n";
                                $body .= "processing stopped";
                                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                                return;
                        }
                }
                db_query("UPDATE $sql_tbl[manufacturers] SET d_last_feed_rows_processed='$lines_in_file' WHERE manufacturerid='$manufacturerid'");
###
##
#

                $handle = @fopen($local_file, "r");
                if ($handle) {
                    $line_number = 0;
                    $NEW_PRODUCTS = array();
                    $discontinued_products = array();
                    $all_feed_productcodes = array();

                    print "<br />".$general_info["manufacturer"]."<br />";
                    print "First iteration:<br />";

		    while (($buffer = fgetcsv($handle, 20480, ",")) !== FALSE) {
                        $line_number++;

                        if ($line_number % 100 == 0) {
                                func_flush(".");
                                if($line_number % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }

                        if ($line_number > 1){

				$Part_Id = trim($buffer[0]);
				$Part_Id = strtoupper($Part_Id);
				$Description = trim($buffer[1]);
				$Available = trim($buffer[2]);
				$Base_Um = trim($buffer[3]);
				$Base_Price = trim($buffer[4]);
				$Base_Price = price_format($Base_Price);
				$On_Order_Qt = trim($buffer[5]);

                                if ($Available == ""){
                                        $Available = 0;
                                }

                                $feed_productcode = "EDR-".$Part_Id;
                                $all_feed_productcodes[] = $feed_productcode;

                                $product_info_arr = func_query_first("SELECT productcode, forsale, avail, r_avail, eta_date_mm_dd_yyyy, list_price FROM $sql_tbl[products] WHERE productcode='".addslashes($feed_productcode)."'");

                                if (!empty($product_info_arr)){

                                        $productcode = $product_info_arr["productcode"];
                                        $current_forsale = $product_info_arr["forsale"];
                                        $current_avail = $product_info_arr["r_avail"];
                                        $current_list_price = $product_info_arr["list_price"];
                                        $current_eta_date_mm_dd_yyyy = $product_info_arr["eta_date_mm_dd_yyyy"];
					$current_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($current_eta_date_mm_dd_yyyy, "m/d/Y");

                                        $product_is_updated = false;
                                        $marked_as_out_of_stock_products = false;
                                        $marked_as_in_stock_products = false;

//                                        $current_forsale = func_query_first_cell("SELECT forsale FROM $sql_tbl[products] WHERE productcode='".addslashes($productcode)."'");

                                        if ($current_forsale != "Y"){
//                                                db_query("UPDATE $sql_tbl[products] SET forsale='Y', update_search_index='Y' WHERE productcode='".addslashes($productcode)."'");
                                                db_query("UPDATE $sql_tbl[products] SET forsale='Y' WHERE productcode='".addslashes($productcode)."'");
                                                $product_is_updated = true;
                                        }

//					$current_eta_date_mm_dd_yyyy = func_query_first_cell("SELECT eta_date_mm_dd_yyyy FROM $sql_tbl[products] WHERE productcode='".addslashes($feed_productcode)."'");
					if (!empty($current_eta_date_mm_dd_yyyy)){
						$current_eta_date_mm_dd_yyyy_arr = explode("/", $current_eta_date_mm_dd_yyyy);
						$current_eta_date_mm_dd_yyyy_time = mktime(0, 0, 0, $current_eta_date_mm_dd_yyyy_arr[0], $current_eta_date_mm_dd_yyyy_arr[1], $current_eta_date_mm_dd_yyyy_arr[2]);
					}
					else {
						$current_eta_date_mm_dd_yyyy_time = 0;
					}

                                        if ($Available > 0){

						$new_eta_date_mm_dd_yyyy = $new_eta_date_mm_dd_yyyy_time = $current_time - 60*60*24*1;
						$new_eta_date_mm_dd_yyyy = date("m/d/Y", $new_eta_date_mm_dd_yyyy);

						if ($current_list_price != $Base_Price || $current_avail != $Available || $current_eta_date_mm_dd_yyyy != $new_eta_date_mm_dd_yyyy){
	                                                db_query("UPDATE $sql_tbl[products] SET list_price='$Base_Price', r_avail='$Available', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy_time' WHERE productcode='".addslashes($productcode)."'");
                                                	$product_is_updated = true;
		
							if ($current_avail == 0){
	                	                                $marked_as_in_stock_products = true;
							}
						}
                                        } elseif ($Available == 0 && $current_eta_date_mm_dd_yyyy_time < $current_time){

                                                $new_eta_date_mm_dd_yyyy = $new_eta_date_mm_dd_yyyy_time = $current_time + 60*60*24*35;
                                                $new_eta_date_mm_dd_yyyy = date("m/d/Y", $new_eta_date_mm_dd_yyyy);

						if ($current_list_price != $Base_Price || $current_avail != $Available || $current_eta_date_mm_dd_yyyy != $new_eta_date_mm_dd_yyyy){
							db_query("UPDATE $sql_tbl[products] SET list_price='$Base_Price', r_avail='$Available', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy_time' WHERE productcode='".addslashes($productcode)."'");

        	                                        $product_is_updated = true;

							if ($current_avail > 0){
	                	                                $marked_as_out_of_stock_products = true;
							}
						}
                                        }

                                        if ($product_is_updated){
                                                $file_is_found_and_uploaded = true;
                                                $count_updated_products++;
                                        }

                                        if ($marked_as_out_of_stock_products){
                                                $count_marked_as_out_of_stock_products++;
                                        }

                                        if ($marked_as_in_stock_products){
                                                $count_marked_as_in_stock_products++;
                                        }

                                } else {
                                        $NEW_PRODUCTS[] = $buffer;
                                }
                        }

                    }
                    fclose($handle);


		    $count_products = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[products] WHERE productcode LIKE 'EDR-%' AND forsale='Y'");
                    if (!empty($all_feed_productcodes) && is_array($all_feed_productcodes)){

			if ($count_products > 0){
	                        $EDR_products = db_query("SELECT productid, productcode, forsale, update_search_index FROM $sql_tbl[products] WHERE productcode LIKE 'EDR-%' AND forsale='Y'");

        	                $line_number = 0;
                	        print "<br />Second iteration:<br />";
                        	while ($product = db_fetch_array($EDR_products)) {
	
        	                        $line_number++;
                	                if ($line_number % 100 == 0) {
                        	                func_flush(".");
                                	        if($line_number % 5000 == 0) {
                                        	        func_flush("<br />\n");
	                                        }

        	                                func_flush();
                	                }

                                        $productcode = strtoupper(trim($product["productcode"]));

                                        if (!in_array($productcode, $all_feed_productcodes) && $product["forsale"] != "N") {
                                	        $file_is_found_and_uploaded = true;
                                        	$discontinued_products[] = $product;
	                                        db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N' WHERE productid='".$product["productid"]."'");
/*
                                                if ($product["update_search_index"] == "N"){
                                                        db_query("UPDATE $sql_tbl[products] SET update_search_index='D' WHERE productid='".$product["productid"]."'");
                                                }
*/
        	                        }
                	        }
			}
                    }
                }

//        if ($file_is_found_and_uploaded){
                db_query("UPDATE $sql_tbl[manufacturers] SET d_most_recent_feed_updation_date='".$launch_time."' WHERE manufacturerid='$manufacturerid'");
//        }


        $count_discontinued_products = count($discontinued_products);
        if (!empty($discontinued_products) && is_array($discontinued_products)){

                $subj = "EDR FEED UPDATE - discontinued products";
                $body = "Discontinued products count: ".$count_discontinued_products."\n\n";

                $body .= "<table border='1'>\n<tr><td>ProductCode</td><td>Link to SF backend</td></tr>\n";
                foreach ($discontinued_products as $k => $v){
                        $store_url = "www.artistsupplysource.com";
                        $body .= "<tr><td> ".$v["productcode"]." </td><td> <a href='http://".$store_url."/admin/product_modify.php?productid=".$v["productid"]."'>http://".$store_url."/admin/product.php?productid=".$v["productid"]."</a> </td></tr>\n";
                }
                $body .= "</table>";

                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

//                print"<br />For test purpose: discontinued_products:";
//                func_print_r($discontinued_products);
        }

        $count_NEW_PRODUCTS = count($NEW_PRODUCTS);
        if (!empty($NEW_PRODUCTS) && is_array($NEW_PRODUCTS)){
                $subj = "EDR FEED UPDATE - new products";
                $body = "Expected new products count: ".$count_NEW_PRODUCTS."\n\n";

		$body .= "Part Id,Description,Available,Base Um,Base Price,On Order Qty\n";
                foreach ($NEW_PRODUCTS as $k => $v){
                        $body .= implode(",", $v)."\n";
                }

                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

//              print"<br />For test purpose: NEW_PRODUCTS:";
//              func_print_r($NEW_PRODUCTS);
        }

	$sum_updated_products = $count_marked_as_out_of_stock_products + $count_marked_as_in_stock_products + $count_discontinued_products;

        $subj = "EDR FEED UPDATE - summary";
        $body = "EDR FEED UPDATE - summary";
	$body .= "products in storefront: ".$count_products."\n";
        $body .= "products in feed: ".count($all_feed_productcodes)."\n";
        $body .= "updated products: ".$sum_updated_products."\n";
        $body .= "marked as `out of stock` products: ".$count_marked_as_out_of_stock_products."\n";
//        $body .= "marked as `out of stock` products (in second iteration): ".$count_discontinued_products."\n";
        $body .= "marked as `in stock` products: ".$count_marked_as_in_stock_products."\n";
        $body .= "new products: ".$count_NEW_PRODUCTS."\n";
        $body .= "discontinued products: ".$count_discontinued_products."\n"; 
		$function_time = time() - $function_launch_time; 
		$function_time = $function_time/(60);
		$function_time = round($function_time,1); 
		$body .= "Duration: ".$function_time." Mins\n";

	func_backprocess_log("supplier feeds", $body);
//        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");


        #Clear FTP files
        $ftp = ftp_connect($general_info["d_ftp_host"]);
        if ($ftp && @ftp_login($ftp, $general_info["d_ftp_login"], $general_info["d_ftp_password"])) {

                        ftp_pasv($ftp, true);

                        $server_file = $general_info["d_ftp_folder"]."ERQTY.csv";
                        $new_server_file = $general_info["d_ftp_folder"]."backup/ERQTY_".time().".csv";

                        if (ftp_rename($ftp, $server_file, $new_server_file)) {

                        } else {
                                echo "There was a problem while renaming $old_file to $new_file\n";
                        }

                        ftp_quit($ftp);

        } else {
                        print("Could not open host. (Distributor: ".$general_info["manufacturer"] .")<br />");
        }
        #
    } // if ($file_is_found)

}


function func_GENERAL_MOT_FEED($manufacturerid){
        global $sql_tbl, $xcart_dir, $launch_time, $function_launch_time;

        $file_is_found_and_uploaded = false;
        $file_is_found = false;
        $count_updated_products = 0;
        $count_marked_as_out_of_stock_products = 0;
        $count_marked_as_in_stock_products = 0;
        $current_time = time();

        if (function_exists("ftp_connect")) {

                $general_info = func_query_first("SELECT manufacturer, d_ftp_host, d_ftp_login, d_ftp_password, d_ftp_folder, d_product_management_team_email FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");

                $ftp = ftp_connect($general_info["d_ftp_host"]);
                if ($ftp && @ftp_login($ftp, $general_info["d_ftp_login"], $general_info["d_ftp_password"])) {

                        ftp_pasv($ftp, true);

                        $local_file = $xcart_dir . "/files/product_feeds/" .$manufacturerid."_InvStatFile.txt";
                        $server_file = $general_info["d_ftp_folder"]."InvStatFile.txt";

                        if (@ftp_get($ftp, $local_file, $server_file, FTP_BINARY)) {
                                $file_is_found = true;
                        }

/* -------------------- */
                        $local_file2 = $xcart_dir . "/files/product_feeds/" .$manufacturerid."PricingFile.txt";
                        $server_file2 = $general_info["d_ftp_folder"]."PricingFile.txt";

			$file_is_found2 = false;
                        if (@ftp_get($ftp, $local_file2, $server_file2, FTP_BINARY)) {
                                $file_is_found2 = true;
                        }
/* -------------------- */

                        ftp_quit($ftp);

                } else {
                        print("Could not open host. (Distributor: ".$general_info["manufacturer"] .")<br />");
                }
        }

/* -------------------- */
        if ($file_is_found2){

                $handle = @fopen($local_file2, "r");
                if ($handle) {
                    $line_number = 0;

                    print "<br />".$general_info["manufacturer"]."<br />";
                    print "Updating price...:<br />";

                    while (($buffer = fgetcsv($handle, 20480, "\t")) !== FALSE) {
                        $line_number++;

                        if ($line_number % 100 == 0) {
                                func_flush(".");
                                if($line_number % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }

			if ($line_number > 0){
                                $SKU = trim($buffer[0]);
                                $SKU = strtoupper($SKU);
                                $cost_to_us = trim($buffer[1]);
				$cost_to_us = price_format($cost_to_us);

                                $feed_productcode = "MOT-".$SKU;

				db_query("UPDATE $sql_tbl[products] SET cost_to_us='$cost_to_us' WHERE productcode='".addslashes($feed_productcode)."'");
			}
                    }
                    fclose($handle);
                }
        }
/* -------------------- */


        if ($file_is_found){

#
##
###
//                $lines_in_file = count(file($local_file));
###
                $lines_in_file = 0;
                $handle = @fopen($local_file, "r");
                while (($buffer = fgetcsv($handle, 20480, "\t")) !== FALSE) {
                        $lines_in_file++;
                        if ($lines_in_file % 100 == 0) {
                                func_flush(".");
                                if($lines_in_file % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }
                }
                fclose($handle);
###

                $additional_info = func_query_first("SELECT d_last_feed_rows_processed, d_validation_threshold FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");

                if ($additional_info["d_last_feed_rows_processed"] > 0){
                        $lines_in_file_DIV_last_feed_rows_processed = $lines_in_file/$additional_info["d_last_feed_rows_processed"];

                        if ($lines_in_file_DIV_last_feed_rows_processed < $additional_info["d_validation_threshold"]){
                                $subj = "SUPPLIER FEED validation failed!!!";
                                $body = $general_info["manufacturer"] . ". Count lines in new file: ".$lines_in_file."\n";
                                $body .= "Last feed rows processed: ".$additional_info["d_last_feed_rows_processed"]."\n";
                                $body .= "processing stopped";
                                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                                return;
                        }
                }
                db_query("UPDATE $sql_tbl[manufacturers] SET d_last_feed_rows_processed='$lines_in_file' WHERE manufacturerid='$manufacturerid'");
###
##
#

                $handle = @fopen($local_file, "r");
                if ($handle) {
                    $line_number = 0;
                    $NEW_PRODUCTS = array();
                    $discontinued_products = array();
                    $all_feed_productcodes = array();

                    print "<br />".$general_info["manufacturer"]."<br />";
                    print "First iteration:<br />";

                    while (($buffer = fgetcsv($handle, 20480, "\t")) !== FALSE) {
                        $line_number++;

                        if ($line_number % 100 == 0) {
                                func_flush(".");
                                if($line_number % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }

                        if ($line_number > 0){  //<-------------------
                                $SKU = trim($buffer[0]);
                                $SKU = strtoupper($SKU);
                                $AVAIL = trim($buffer[1]); //FORSALE
                                $ONORDER = trim($buffer[2]);
                                $ETA_DAYS = trim($buffer[3]);
                                $QUALIFIER = trim($buffer[4]);

                                if ($AVAIL == ""){
                                        $AVAIL = 0;
                                }

                                $feed_productcode = "MOT-".$SKU;
                                $all_feed_productcodes[] = $feed_productcode;

                                $product_info_arr = func_query_first("SELECT productid, productcode, forsale, avail, r_avail, eta_date_mm_dd_yyyy, list_price, update_search_index FROM $sql_tbl[products] WHERE productcode='".addslashes($feed_productcode)."'");

                                if (!empty($product_info_arr)){

                                        $productcode = $product_info_arr["productcode"];
                                        $productid = $product_info_arr["productid"];
                                        $current_forsale = $product_info_arr["forsale"];
                                        $current_avail = $product_info_arr["r_avail"];
                                        $current_list_price = $product_info_arr["list_price"];
                                        $current_eta_date_mm_dd_yyyy = $product_info_arr["eta_date_mm_dd_yyyy"];
					$current_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($current_eta_date_mm_dd_yyyy, "m/d/Y");

                                        $product_is_updated = false;
                                        $marked_as_out_of_stock_products = false;
                                        $marked_as_in_stock_products = false;

/*
                                        if ($current_forsale != "Y"){
                                                db_query("UPDATE $sql_tbl[products] SET forsale='Y' WHERE productcode='".addslashes($productcode)."'");
                                                $product_is_updated = true;
                                        }
*/

                                        if (!empty($current_eta_date_mm_dd_yyyy)){
                                                $current_eta_date_mm_dd_yyyy_arr = explode("/", $current_eta_date_mm_dd_yyyy);
                                                $current_eta_date_mm_dd_yyyy_time = mktime(0, 0, 0, $current_eta_date_mm_dd_yyyy_arr[0], $current_eta_date_mm_dd_yyyy_arr[1], $current_eta_date_mm_dd_yyyy_arr[2]);
                                        }
                                        else {
                                                $current_eta_date_mm_dd_yyyy_time = 0;
                                        }


					if ($QUALIFIER == "D"){

                        	                if ($current_forsale != "N"){
	                                                $count_discontinued_products = count($discontinued_products);
        	                                        $discontinued_products[$count_discontinued_products]["productid"] = $productid;
                	                                $discontinued_products[$count_discontinued_products]["First_iteration"] = "Y";
						}

						$new_forsale = "N";
						$new_avail = 0;

               	                                db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', forsale='$new_forsale' WHERE productid='$productid'");
/*
						if ($product_info_arr["update_search_index"] == "N"){
	               	                                db_query("UPDATE $sql_tbl[products] SET update_search_index='D' WHERE productid='$productid'");
						}
*/
					} elseif ($QUALIFIER == "O" || $QUALIFIER == "P"){

						$new_forsale = "Y";

						if ($current_forsale != $new_forsale){
							$product_is_updated = true;
						}

						$new_avail = 0;

						if ($current_avail > 0){
							$marked_as_out_of_stock_products = true;
							$product_is_updated = true;
						}

//						db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', forsale='$new_forsale', update_search_index='Y' WHERE productid='$productid'");
						db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', forsale='$new_forsale' WHERE productid='$productid'");

					} elseif ($QUALIFIER == "S"){

                                                $new_forsale = "Y";

                                                if ($current_forsale != $new_forsale){
                                                        $product_is_updated = true;
                                                }

                                                $new_avail = $AVAIL;

                                                if ($current_avail > 0 && $AVAIL == 0){
                                                        $marked_as_out_of_stock_products = true;
                                                        $product_is_updated = true;
                                                }

						if ($current_avail == 0 && $AVAIL > 0) {
							$product_is_updated = true;
							$marked_as_in_stock_products = true;
						}

						if ($ETA_DAYS > 0){
							$new_eta_date_mm_dd_yyyy = $new_eta_date_mm_dd_yyyy_time =  $current_time + 60*60*24*$ETA_DAYS;
							$new_eta_date_mm_dd_yyyy = date("m/d/Y", $new_eta_date_mm_dd_yyyy);
						} else {
							$new_eta_date_mm_dd_yyyy = $new_eta_date_mm_dd_yyyy_time = "";
						}

						if ($current_eta_date_mm_dd_yyyy != $new_eta_date_mm_dd_yyyy){
							$product_is_updated = true;
						}

//                                                db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', forsale='$new_forsale', update_search_index='Y', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy' WHERE productid='$productid'");
                                                db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', forsale='$new_forsale', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy_time' WHERE productid='$productid'");
					}

                                        if ($product_is_updated){
                                                $file_is_found_and_uploaded = true;
                                                $count_updated_products++;
                                        }

                                        if ($marked_as_out_of_stock_products){
                                                $count_marked_as_out_of_stock_products++;
                                        }

                                        if ($marked_as_in_stock_products){
                                                $count_marked_as_in_stock_products++;
                                        }

                                } else {
                                        $NEW_PRODUCTS[] = $buffer;
                                }
                        }

                    }
                    fclose($handle);


                    $count_products = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[products] WHERE productcode LIKE 'MOT-%' AND forsale='Y'");
                    if (!empty($all_feed_productcodes) && is_array($all_feed_productcodes)){

                        if ($count_products > 0){
                                $MOT_products = db_query("SELECT productid, productcode, forsale, update_search_index FROM $sql_tbl[products] WHERE productcode LIKE 'MOT-%' AND forsale='Y'");

                                $line_number = 0;
                                print "<br />Second iteration:<br />";
                                while ($product = db_fetch_array($MOT_products)) {

                                        $line_number++;
                                        if ($line_number % 100 == 0) {
                                                func_flush(".");
                                                if($line_number % 5000 == 0) {
                                                        func_flush("<br />\n");
                                                }

                                                func_flush();
                                        }

					$productcode = strtoupper(trim($product["productcode"]));

                                        if (!in_array($productcode, $all_feed_productcodes) && $product["forsale"] != "N") {
                                                $file_is_found_and_uploaded = true;
                                                $discontinued_products[] = $product;
                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N' WHERE productid='".$product["productid"]."'");
/*
                                                if ($product["update_search_index"] == "N"){
                                                        db_query("UPDATE $sql_tbl[products] SET update_search_index='D' WHERE productid='".$product["productid"]."'");
                                                }
*/
                                        }
                                }
                        }
                    }
                }
        }

//        if ($file_is_found_and_uploaded){
                db_query("UPDATE $sql_tbl[manufacturers] SET d_most_recent_feed_updation_date='".$launch_time."' WHERE manufacturerid='$manufacturerid'");
//        }

        $count_discontinued_products = count($discontinued_products);
        if (!empty($discontinued_products) && is_array($discontinued_products)){

                $subj = "MOT FEED UPDATE - discontinued products";
                $body = "Discontinued products count: ".$count_discontinued_products."\n\n";

                $body .= "<table border='1'>\n<tr><td>ProductCode</td><td>Link to SF backend</td></tr>\n";
                foreach ($discontinued_products as $k => $v){
                        $store_url = "www.artistsupplysource.com";
                        $body .= "<tr><td> ".$v["productcode"]." </td><td> <a href='http://".$store_url."/admin/product_modify.php?productid=".$v["productid"]."'>http://".$store_url."/admin/product.php?productid=".$v["productid"]."</a> </td></tr>\n";
                }
                $body .= "</table>";

                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

//                print"<br />For test purpose: discontinued_products:";
//                func_print_r($discontinued_products);
        }

        $count_NEW_PRODUCTS = count($NEW_PRODUCTS);
        if (!empty($NEW_PRODUCTS) && is_array($NEW_PRODUCTS)){
                $subj = "MOT FEED UPDATE - new products";
                $body = "Expected new products count: ".$count_NEW_PRODUCTS."\n\n";

                $body .= "SKU\tFORSALE\tONORDER\tETA_DAYS\tQUALIFIER\n";
                foreach ($NEW_PRODUCTS as $k => $v){
                        $body .= implode("\t", $v)."\n";
                }

                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

//                print"<br />For test purpose: NEW_PRODUCTS:";
//                func_print_r($NEW_PRODUCTS);
        }

        $sum_updated_products = $count_marked_as_out_of_stock_products + $count_marked_as_in_stock_products + $count_discontinued_products;

        $subj = "MOT FEED UPDATE - summary";
        $body = "MOT FEED UPDATE - summary";
	$body .= "products in storefront: ".$count_products."\n";
        $body .= "products in feed: ".count($all_feed_productcodes)."\n";
        $body .= "updated products: ".$sum_updated_products."\n";
        $body .= "marked as `out of stock` products: ".$count_marked_as_out_of_stock_products."\n";
//        $body .= "marked as `out of stock` products (in second iteration): ".$count_discontinued_products."\n";
        $body .= "marked as `in stock` products: ".$count_marked_as_in_stock_products."\n";
        $body .= "new products: ".$count_NEW_PRODUCTS."\n";
        $body .= "discontinued products: ".$count_discontinued_products."\n"; 
		$function_time = time() - $function_launch_time; 
		$function_time = $function_time/(60);
		$function_time = round($function_time,1); 
		$body .= "Duration: ".$function_time." Mins\n";

	func_backprocess_log("supplier feeds", $body);
//        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
}

function func_GENERAL_HTTPS_LWF_FEED($manufacturerid){
        global $sql_tbl, $xcart_dir, $launch_time, $function_launch_time;

	$general_info = func_query_first("SELECT manufacturer, d_ftp_host, d_ftp_login, d_ftp_password, d_ftp_folder, d_product_management_team_email FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");


	$lines = file($general_info["d_ftp_host"]);

	$needed_lines = array();
	$needed_first_line_is_found = false;

	if (!empty($lines) && is_array($lines)){
		foreach ($lines as $line_num => $line) {

			if (strpos($line, 'id="rgReport_ctl00__0"') !== false){
				$needed_first_line_is_found = true;
			}

	                if (strpos($line, '</tbody>') !== false && $needed_first_line_is_found){
					break;
                	}

			if ($needed_first_line_is_found){
				$needed_lines[] = $line;
			}
		}
	}

	if (!empty($needed_lines)){

                print "<br />".$general_info["manufacturer"]."<br />";
                print "First iteration:<br />";
		$count_marked_as_in_stock_products = 0;
		$count_marked_as_out_of_stock_products = 0;
		$count_updated_products = 0;
		$line_number = 0;

                $NEW_PRODUCTS = array();
                $discontinued_products = array();
                $all_feed_productcodes = array();


		foreach ($needed_lines as $k => $v){
			if ($k % 2 != 0){

        	                $line_number++;

	                        if ($line_number % 100 == 0) {
	                                func_flush(".");
                                	if($line_number % 5000 == 0) {
                        	                func_flush("<br />\n");
                	                }

        	                        func_flush();
	                        }

				$line = trim($v);
				$line = str_replace("</td><td>", "|---delimiter---|", $line);
				$line = str_replace("</td>", "", $line);
				$line = str_replace("<td>", "", $line);

				$line_arr = explode("|---delimiter---|", $line);

				$SKU = trim($line_arr[0]);
				$SKU = strtoupper($SKU);
				$Item_Name = trim($line_arr[1]);
				$MSRP = trim($line_arr[2]);
				$MSRP = price_format($MSRP);
				$MAP = trim($line_arr[3]);
				$MAP = price_format($MAP);
				$Retail_Price = trim($line_arr[4]);
				$Retail_Price = price_format($Retail_Price);
				$Your_Price = trim($line_arr[5]);
				$Your_Price = price_format($Your_Price);
				$Inventory_Status = trim($line_arr[6]);
				$Inventory_Status = strtolower($Inventory_Status);

                                $feed_productcode = "LWF-".$SKU;
                                $all_feed_productcodes[] = $feed_productcode;

                                $product_info_arr = func_query_first("SELECT productid, productcode, forsale, avail, r_avail, eta_date_mm_dd_yyyy, list_price, cost_to_us, new_map_price, update_search_index FROM $sql_tbl[products] WHERE productcode='".addslashes($feed_productcode)."'");

                                if (!empty($product_info_arr)){

                                        $productcode = $product_info_arr["productcode"];
                                        $productid = $product_info_arr["productid"];
                                        $current_forsale = $product_info_arr["forsale"];
                                        $current_avail = $product_info_arr["r_avail"];
                                        $current_cost_to_us = $product_info_arr["cost_to_us"];
                                        $current_new_map_price = $product_info_arr["new_map_price"];
                                        $current_list_price = $product_info_arr["list_price"];
                                        $current_eta_date_mm_dd_yyyy = $product_info_arr["eta_date_mm_dd_yyyy"];
					$current_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($current_eta_date_mm_dd_yyyy, "m/d/Y");

                                        $product_is_updated = false;
                                        $marked_as_out_of_stock_products = false;
                                        $marked_as_in_stock_products = false;

					$new_forsale = "Y";
					$new_avail = $current_avail;
                                        $new_eta_date_mm_dd_yyyy = $current_eta_date_mm_dd_yyyy;
					$new_eta_date_mm_dd_yyyy_time = "";
					$new_list_price = $current_list_price;
					$new_cost_to_us = $current_cost_to_us;
					$new_new_map_price = $current_new_map_price;

					if ($Inventory_Status == "ample stock"){
						$new_avail = 100;
						$new_eta_date_mm_dd_yyyy = "";
						$new_list_price = $MSRP;
						$new_cost_to_us = $Your_Price;
//						$new_new_map_price = $MAP;
						$new_new_map_price = $MSRP * 0.9;
					} elseif ($Inventory_Status == "low stock"){
                                                $new_avail = 5;
                                                $new_eta_date_mm_dd_yyyy = "";
                                                $new_list_price = $MSRP;
                                                $new_cost_to_us = $Your_Price;
//                                                $new_new_map_price = $MAP;
                                                $new_new_map_price = $MSRP * 0.9;
					} elseif ($Inventory_Status == "back ordered"){
						$new_avail = 0;
						$new_cost_to_us = $Your_Price;
						$new_eta_date_mm_dd_yyyy_time = time() + 60*60*24*35;
					}


                                        if (!empty($new_eta_date_mm_dd_yyyy_time)){
	                                        $new_eta_date_mm_dd_yyyy = date("m/d/Y", $new_eta_date_mm_dd_yyyy_time);
                                        }

                                        if ($new_avail == "0"){
	                                        if ($current_avail > 0){
        	                                        $marked_as_out_of_stock_products = true;
                                                }
                                        } else {
                                                if ($current_avail == 0){
                	                                $marked_as_in_stock_products = true;
                                                }
                                        }

                                        if ($new_forsale != $current_forsale || $new_eta_date_mm_dd_yyyy != $current_eta_date_mm_dd_yyyy || $current_avail != $new_avail || $new_list_price != $current_list_price || $new_cost_to_us != $current_cost_to_us || $new_new_map_price != $current_new_map_price){

/*
							$update_search_index = 'Y';
							if ($new_forsale == 'N'){
								if ($product_info_arr["update_search_index"] == "N"){
									$update_search_index = 'D';
								}
							}
*/
							$new_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($new_eta_date_mm_dd_yyyy, "seconds");
                                        		db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale', list_price='$new_list_price', cost_to_us='$new_cost_to_us', new_map_price='$new_new_map_price' WHERE productid='$productid'");
                                                        $product_is_updated = true;
                                        }

                                        if ($product_is_updated){
                                                $count_updated_products++;
                                        }

                                        if ($marked_as_out_of_stock_products){
                                                $count_marked_as_out_of_stock_products++;
                                        }

                                        if ($marked_as_in_stock_products){
                                                $count_marked_as_in_stock_products++;
                                        }
                                } else {
                                        $NEW_PRODUCTS[] = implode("|", $line_arr);
                                }

//func_print_r($SKU, $Item_Name, $MSRP, $MAP, $Retail_Price, $Your_Price, $Inventory_Status);

			}
		}

                $count_products = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[products] WHERE productcode LIKE 'LWF-%' AND forsale='Y'");
		if (!empty($all_feed_productcodes) && is_array($all_feed_productcodes)){

                        if ($count_products > 0){

                                $LWF_products = db_query("SELECT productid, productcode, forsale, update_search_index FROM $sql_tbl[products] WHERE productcode LIKE 'LWF-%' AND forsale='Y'");

                                $line_number = 0;
                                print "<br />Second iteration:<br />";
                                while ($product = db_fetch_array($LWF_products)) {

                                        $line_number++;
                                        if ($line_number % 100 == 0) {
                                                func_flush(".");
                                                if($line_number % 5000 == 0) {
                                                        func_flush("<br />\n");
                                                }

                                                func_flush();
                                        }

                                        $productcode = strtoupper(trim($product["productcode"]));

                                        if (!in_array($productcode, $all_feed_productcodes) && $product["forsale"] != "N") {
                                                $discontinued_products[] = $product;
/*
						$update_search_index = $product["update_search_index"];
						if ($update_search_index == "N"){
							$update_search_index = "D";
						}
*/
//                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N', update_search_index='$update_search_index' WHERE productid='".$product["productid"]."'");
                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N' WHERE productid='".$product["productid"]."'");
                                        }
                                }
                        }
		}


		db_query("UPDATE $sql_tbl[manufacturers] SET d_most_recent_feed_updation_date='".$launch_time."' WHERE manufacturerid='$manufacturerid'");

	        $count_discontinued_products = count($discontinued_products);
        	if (!empty($discontinued_products) && is_array($discontinued_products)){

                	$subj = "LONEWOLF FEED UPDATE - discontinued products";
        	        $body = "Discontinued products count: ".$count_discontinued_products."\n\n";

	                $body .= "<table border='1'>\n<tr><td>ProductCode</td><td>Link to SF backend</td></tr>\n";
        	        foreach ($discontinued_products as $k => $v){
                	        $store_url = "www.artistsupplysource.com";
	                        $body .= "<tr><td> ".$v["productcode"]." </td><td> <a href='http://".$store_url."/admin/product_modify.php?productid=".$v["productid"]."'>http://".$store_url."/admin/product.php?productid=".$v["productid"]."</a> </td></tr>\n";
        	        }
                	$body .= "</table>";

                	func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

//                print"<br />For test purpose: discontinued_products:";
//                func_print_r($discontinued_products);
        	}

	        $count_NEW_PRODUCTS = count($NEW_PRODUCTS);
        	if (!empty($NEW_PRODUCTS) && is_array($NEW_PRODUCTS)){
	                $subj = "LONEWOLF FEED UPDATE - new products";
                	$body = "Expected new products count: ".$count_NEW_PRODUCTS."\n\n";

        	        $body .= "Item Number|Item Name|MSRP|MAP|Retail Price|Your Price|Inventory Status\n";
	                foreach ($NEW_PRODUCTS as $k => $v){
                        	$body .= $v."\n";
                	}

        	        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

//              print"<br />For test purpose: NEW_PRODUCTS:";
//              func_print_r($NEW_PRODUCTS);
	        }

        	$count_all_feed_productcodes = count($all_feed_productcodes);
	        $sum_updated_products = $count_marked_as_out_of_stock_products + $count_marked_as_in_stock_products + $count_discontinued_products;

	        $subj = "LONEWOLF FEED UPDATE - summary";
	        $body = "LONEWOLF FEED UPDATE - summary";
		$body .= "products in storefront: ".$count_products."\n";
	        $body .= "products in feed: ".$count_all_feed_productcodes."\n";
	        $body .= "updated products: ".$sum_updated_products."\n";
	        $body .= "marked as `out of stock` products: ".$count_marked_as_out_of_stock_products."\n";
	        $body .= "marked as `in stock` products: ".$count_marked_as_in_stock_products."\n";
	        $body .= "new products: ".$count_NEW_PRODUCTS."\n";
	        $body .= "discontinued products: ".$count_discontinued_products."\n"; 
		$function_time = time() - $function_launch_time; 
		$function_time = $function_time/(60);
		$function_time = round($function_time,1); 
		$body .= "Duration: ".$function_time." Mins\n";

		func_backprocess_log("supplier feeds", $body);
//	        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
	}
}


function func_GENERAL_PPF_FEED($manufacturerid){
        global $sql_tbl, $xcart_dir, $launch_time, $function_launch_time;

        $file_is_found_and_uploaded = false;
        $file_is_found = false;
        $count_updated_products = 0;
        $count_marked_as_out_of_stock_products = 0;
        $count_marked_as_in_stock_products = 0;
        $current_time = time();

        if (function_exists("ftp_connect")) {

                $general_info = func_query_first("SELECT manufacturer, d_ftp_host, d_ftp_login, d_ftp_password, d_ftp_folder, d_product_management_team_email FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");

                $ftp = ftp_connect($general_info["d_ftp_host"]);
                if ($ftp && @ftp_login($ftp, $general_info["d_ftp_login"], $general_info["d_ftp_password"])) {

                        ftp_pasv($ftp, true);

                        $local_file = $xcart_dir . "/files/product_feeds/" .$manufacturerid."_daily_catalog_export.csv";
                        $server_file = $general_info["d_ftp_folder"]."daily_catalog_export.csv";

                        if (@ftp_get($ftp, $local_file, $server_file, FTP_BINARY)) {
                                $file_is_found = true;
                        }

                        ftp_quit($ftp);


                } else {
                        print("Could not open host. (Distributor: ".$general_info["manufacturer"] .")<br />");
                }
        }


        if ($file_is_found){

#
##
###
//                $lines_in_file = count(file($local_file));
###
		$lines_in_file = 0;
		$handle = @fopen($local_file, "r");
		while (($buffer = fgetcsv($handle, 20480, ",")) !== FALSE) {
			$lines_in_file++;
                        if ($lines_in_file % 100 == 0) {
                                func_flush(".");
                                if($lines_in_file % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }
		}
		fclose($handle);
###

                $additional_info = func_query_first("SELECT d_last_feed_rows_processed, d_validation_threshold FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");

                if ($additional_info["d_last_feed_rows_processed"] > 0){
                        $lines_in_file_DIV_last_feed_rows_processed = $lines_in_file/$additional_info["d_last_feed_rows_processed"];

                        if ($lines_in_file_DIV_last_feed_rows_processed < $additional_info["d_validation_threshold"]){
                                $subj = "SUPPLIER FEED validation failed!!!";
                                $body = $general_info["manufacturer"] . ". Count lines in new file: ".$lines_in_file."\n";
                                $body .= "Last feed rows processed: ".$additional_info["d_last_feed_rows_processed"]."\n";
                                $body .= "processing stopped";
                                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                                return;
                        }
                }
                db_query("UPDATE $sql_tbl[manufacturers] SET d_last_feed_rows_processed='$lines_in_file' WHERE manufacturerid='$manufacturerid'");
###
##
#

                $handle = @fopen($local_file, "r");
                if ($handle) {
                    $line_number = 0;
                    $NEW_PRODUCTS = array();
                    $discontinued_products = array();
                    $all_feed_productcodes = array();

                    print "<br />".$general_info["manufacturer"]."<br />";
                    print "First iteration:<br />";

                    while (($buffer = fgetcsv($handle, 20480, ",")) !== FALSE) {
                        $line_number++;

                        if ($line_number % 100 == 0) {
                                func_flush(".");
                                if($line_number % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }

                        if ($line_number > 1) {  //<-------------------

                                $SKU = trim($buffer[62]);
                                $SKU = strtoupper($SKU);
                                $AVAIL = trim($buffer[58]);
				$FORSALE = trim($buffer[75]); //forsale (1), discontinued (2)
				$WEIGHT = trim($buffer[100]);
				$WEIGHT = price_format($WEIGHT);
				$PRICE = trim($buffer[54]);
				$PRICE = price_format($PRICE);

                                if ($AVAIL == ""){
                                        $AVAIL = 0;
                                }

                                $feed_productcode = "PPF-".$SKU;
                                $all_feed_productcodes[] = $feed_productcode;

                                $product_info_arr = func_query_first("SELECT productid, productcode, forsale, avail, r_avail, eta_date_mm_dd_yyyy, cost_to_us, weight, update_search_index FROM $sql_tbl[products] WHERE productcode='".addslashes($feed_productcode)."'");

                                if (!empty($product_info_arr)){

                                        $productcode = $product_info_arr["productcode"];
                                        $productid = $product_info_arr["productid"];

                                        $current_forsale = $product_info_arr["forsale"];
                                        $current_avail = $product_info_arr["r_avail"];
                                        $current_weight = $product_info_arr["weight"];
                                        $current_cost_to_us = $product_info_arr["cost_to_us"];
                                        $current_eta_date_mm_dd_yyyy = $product_info_arr["eta_date_mm_dd_yyyy"];
					$current_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($current_eta_date_mm_dd_yyyy, "m/d/Y");

                                        $new_forsale = $current_forsale;
                                        $new_avail = $current_avail;
                                        $new_weight = $current_weight;
                                        $new_eta_date_mm_dd_yyyy = $current_eta_date_mm_dd_yyyy;
                                        $new_eta_date_mm_dd_yyyy_time = "";
                                        $new_cost_to_us = $current_cost_to_us;

                                        $product_is_updated = false;
                                        $marked_as_out_of_stock_products = false;
                                        $marked_as_in_stock_products = false;

					if ($FORSALE == "2"){
						$new_forsale = "N";
						$new_avail = 0;

                                                if ($current_forsale != "N"){
                                                        $count_discontinued_products = count($discontinued_products);
                                                        $discontinued_products[$count_discontinued_products]["productid"] = $productid;
                                                        $discontinued_products[$count_discontinued_products]["First_iteration"] = "Y";
                                                }
					} 
					elseif ($FORSALE == "1"){
						$new_forsale = "Y";
						$new_avail = $AVAIL;
						$new_weight = $WEIGHT;
						$new_cost_to_us = $PRICE;

						if ($AVAIL == 0){
							$new_eta_date_mm_dd_yyyy_time = time() + 60*60*24*35;
						} else {
							$new_eta_date_mm_dd_yyyy = "";
						}
					}

                                        if (!empty($new_eta_date_mm_dd_yyyy_time)){
                                                $new_eta_date_mm_dd_yyyy = date("m/d/Y", $new_eta_date_mm_dd_yyyy_time);
                                        }

                                        if ($new_avail == "0"){
                                                if ($current_avail > 0){
                                                        $marked_as_out_of_stock_products = true;
                                                }
                                        } else {
                                                if ($current_avail == 0){
                                                        $marked_as_in_stock_products = true;
                                                }
                                        }

                                        if ($new_forsale != $current_forsale || $new_eta_date_mm_dd_yyyy != $current_eta_date_mm_dd_yyyy || $current_avail != $new_avail || $new_weight != $current_weight || $new_cost_to_us != $current_cost_to_us){
/*
                                                        $update_search_index = 'Y';
                                                        if ($new_forsale == 'N' && $product_info_arr["update_search_index"] == "N"){
                                                                $update_search_index = 'D';
                                                        }
*/
//                                                        db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale', cost_to_us='$new_cost_to_us', weight='$new_weight', update_search_index='$update_search_index' WHERE productid='$productid'");
							$new_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($new_eta_date_mm_dd_yyyy, "seconds");
                                                        db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale', cost_to_us='$new_cost_to_us', weight='$new_weight' WHERE productid='$productid'");
                                                        $product_is_updated = true;
                                        }

                                        if ($product_is_updated){
                                                $file_is_found_and_uploaded = true;
                                                $count_updated_products++;
                                        }

                                        if ($marked_as_out_of_stock_products){
                                                $count_marked_as_out_of_stock_products++;
                                        }

                                        if ($marked_as_in_stock_products){
                                                $count_marked_as_in_stock_products++;
                                        }

                                } else {
                                        $NEW_PRODUCTS[] = $SKU.", ".$PRICE.", ".$AVAIL.", ".$FORSALE.", ".$WEIGHT;
                                }
                        }

                    }
                    fclose($handle);

                    $count_products = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[products] WHERE productcode LIKE 'PPF-%' AND forsale='Y'");
                    if (!empty($all_feed_productcodes) && is_array($all_feed_productcodes)){

                        if ($count_products > 0){
                                $PPF_products = db_query("SELECT productid, productcode, forsale, update_search_index FROM $sql_tbl[products] WHERE productcode LIKE 'PPF-%' AND forsale='Y'");

                                $line_number = 0;
                                print "<br />Second iteration:<br />";
                                while ($product = db_fetch_array($PPF_products)) {

                                        $line_number++;
                                        if ($line_number % 100 == 0) {
                                                func_flush(".");
                                                if($line_number % 5000 == 0) {
                                                        func_flush("<br />\n");
                                                }

                                                func_flush();
                                        }

                                        $productcode = strtoupper(trim($product["productcode"]));

                                        if (!in_array($productcode, $all_feed_productcodes) && $product["forsale"] != "N") {
                                                $file_is_found_and_uploaded = true;
                                                $discontinued_products[] = $product;
/*
                                                $update_search_index = $product["update_search_index"];
                                                if ($update_search_index == "N"){
                                                        $update_search_index = "D";
                                                }
*/
//                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N', update_search_index='$update_search_index' WHERE productid='".$product["productid"]."'");
                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N' WHERE productid='".$product["productid"]."'");
                                        }
                                }
                        }
                    }
                }
        }

	db_query("UPDATE $sql_tbl[manufacturers] SET d_most_recent_feed_updation_date='".$launch_time."' WHERE manufacturerid='$manufacturerid'");

        $count_discontinued_products = count($discontinued_products);
        if (!empty($discontinued_products) && is_array($discontinued_products)){

                $subj = "PPF FEED UPDATE - discontinued products";
                $body = "Discontinued products count: ".$count_discontinued_products."\n\n";

                $body .= "<table border='1'>\n<tr><td>ProductCode</td><td>Link to SF backend</td></tr>\n";
                foreach ($discontinued_products as $k => $v){
                        $store_url = "www.artistsupplysource.com";
                        $body .= "<tr><td> ".$v["productcode"]." </td><td> <a href='http://".$store_url."/admin/product_modify.php?productid=".$v["productid"]."'>http://".$store_url."/admin/product.php?productid=".$v["productid"]."</a> </td></tr>\n";
                }
                $body .= "</table>";

                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

//                print"<br />For test purpose: discontinued_products:";
//                func_print_r($discontinued_products);
        }

        $count_NEW_PRODUCTS = count($NEW_PRODUCTS);
        if (!empty($NEW_PRODUCTS) && is_array($NEW_PRODUCTS)){
                $subj = "PPF FEED UPDATE - new products";
                $body = "Expected new products count: ".$count_NEW_PRODUCTS."\n\n";

                $body .= "SKU, Price, Qty, Status, Weight\n";
                foreach ($NEW_PRODUCTS as $k => $v){
                        $body .= $v."\n";
                }

                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

//                print"<br />For test purpose: NEW_PRODUCTS:";
//                func_print_r($NEW_PRODUCTS);
        }

        $sum_updated_products = $count_marked_as_out_of_stock_products + $count_marked_as_in_stock_products + $count_discontinued_products;

        $subj = "PPF FEED UPDATE - summary";
        $body = "PPF FEED UPDATE - summary";
	$body .= "products in storefront: ".$count_products."\n";
        $body .= "products in feed: ".count($all_feed_productcodes)."\n";
        $body .= "updated products: ".$sum_updated_products."\n";
        $body .= "marked as `out of stock` products: ".$count_marked_as_out_of_stock_products."\n";
//        $body .= "marked as `out of stock` products (in second iteration): ".$count_discontinued_products."\n";
        $body .= "marked as `in stock` products: ".$count_marked_as_in_stock_products."\n";
        $body .= "new products: ".$count_NEW_PRODUCTS."\n";
        $body .= "discontinued products: ".$count_discontinued_products."\n"; 
		$function_time = time() - $function_launch_time; 
		$function_time = $function_time/(60);
		$function_time = round($function_time,1); 
		$body .= "Duration: ".$function_time." Mins\n";

	func_backprocess_log("supplier feeds", $body);
//        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");


}

function func_GENERAL_HTTPS_KKL_FEED($manufacturerid){
        global $sql_tbl, $xcart_dir, $launch_time, $function_launch_time;

	$needed_lines = array();

        $general_info = func_query_first("SELECT manufacturer, code, d_ftp_host, d_ftp_login, d_ftp_password, d_ftp_folder, d_product_management_team_email FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");
	$manufacturer_code = trim($general_info["code"]);

	$local_file = $xcart_dir . "/files/product_feeds/" .$manufacturerid."_stockstatus.xls";

	if(!@copy($general_info["d_ftp_host"], $local_file)){
		$errors= error_get_last();
		echo "COPY ERROR: ".$errors['type'];
		echo "<br />\n".$errors['message'];
	} else {

		$data = new Spreadsheet_Excel_Reader();

		// Set output Encoding.
		//$data->setOutputEncoding('CP1251');

		$data->read($local_file);

		$counter = 0;

		for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
		    if ($i >= 11){
		        for ($j = 0; $j < $data->sheets[0]['numCols']; $j++) {

                		$tmp_val = $data->sheets[0]['cells'][$i][$j];
		                $tmp_val = trim($tmp_val);

				$needed_lines[$counter][$j] = $tmp_val;
		        }
			$counter++;
		    }
		}

//func_print_r($needed_lines);

	}

        if (!empty($needed_lines)){

                print "<br />".$general_info["manufacturer"]."<br />";
                print "First iteration:<br />";
                $count_marked_as_in_stock_products = 0;
                $count_marked_as_out_of_stock_products = 0;
                $count_updated_products = 0;

                foreach ($needed_lines as $k => $v){

			if ($k % 100 == 0) {
                                        func_flush(".");
                                        if($k % 5000 == 0) {
                                                func_flush("<br />\n");
                                        }

                                        func_flush();
                        }

			$tmp_SKU = $v[1];
			if (!empty($tmp_SKU) && strpos($tmp_SKU, '-') !== true){

                                $SKU = strtoupper($tmp_SKU);
				$In_Stock = strtoupper($v[4]);
				$Low_in_Stock = strtoupper($v[5]);
				$Out_of_Stock = strtoupper($v[6]);
				$cost_to_us = price_format($v[10]);

                                $feed_productcode = $manufacturer_code . "-" . $SKU;
                                $all_feed_productcodes[] = $feed_productcode;

                                $product_info_arr = func_query_first("SELECT productid, productcode, update_search_index, forsale, avail, r_avail, eta_date_mm_dd_yyyy, cost_to_us FROM $sql_tbl[products] WHERE productcode='".addslashes($feed_productcode)."'");

                                if (!empty($product_info_arr)){

                                        $productcode = $product_info_arr["productcode"];
                                        $productid = $product_info_arr["productid"];
                                        $current_forsale = $product_info_arr["forsale"];
                                        $current_avail = $product_info_arr["r_avail"];
                                        $current_cost_to_us = $product_info_arr["cost_to_us"];
                                        $current_eta_date_mm_dd_yyyy = $product_info_arr["eta_date_mm_dd_yyyy"];
					$current_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($current_eta_date_mm_dd_yyyy, "m/d/Y");

                                        $product_is_updated = false;
                                        $marked_as_out_of_stock_products = false;
                                        $marked_as_in_stock_products = false;

                                        $new_forsale = "Y";
                                        $new_avail = $current_avail;
                                        $new_eta_date_mm_dd_yyyy = $current_eta_date_mm_dd_yyyy;
                                        $new_eta_date_mm_dd_yyyy_time = "";
                                        $new_cost_to_us = $current_cost_to_us;

                                        if ($In_Stock == "X"){
                                                $new_avail = 1000;
                                                $new_eta_date_mm_dd_yyyy = "";
                                                $new_cost_to_us = $cost_to_us;
                                        } elseif ($Low_in_Stock == "X"){
                                                $new_avail = 1000;
                                                $new_eta_date_mm_dd_yyyy = "";
                                                $new_cost_to_us = $cost_to_us;
                                        } elseif ($Out_of_Stock == "X"){
                                                $new_avail = 0;
                                                $new_cost_to_us = $cost_to_us;
                                                $new_eta_date_mm_dd_yyyy_time = time() + 60*60*24*35;
                                        }

                                        if (!empty($new_eta_date_mm_dd_yyyy_time)){
                                                $new_eta_date_mm_dd_yyyy = date("m/d/Y", $new_eta_date_mm_dd_yyyy_time);
                                        }

                                        if ($new_avail == "0"){
                                                if ($current_avail > 0){
                                                        $marked_as_out_of_stock_products = true;
                                                }
                                        } else {
                                                if ($current_avail == 0){
                                                        $marked_as_in_stock_products = true;
                                                }
                                        }

                                        if ($new_forsale != $current_forsale || $new_eta_date_mm_dd_yyyy != $current_eta_date_mm_dd_yyyy || $current_avail != $new_avail || $new_cost_to_us != $current_cost_to_us){
/*
                                                        $update_search_index = 'Y';
                                                        if ($new_forsale == 'N' && $product_info_arr["update_search_index"] == "N"){
                                                                $update_search_index = 'D';
                                                        }
*/
//                                                        db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale', cost_to_us='$new_cost_to_us', update_search_index='$update_search_index' WHERE productid='$productid'");
							$new_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($new_eta_date_mm_dd_yyyy, "seconds");
                                                        db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale', cost_to_us='$new_cost_to_us'  WHERE productid='$productid'");
                                                        $product_is_updated = true;
                                        }

                                        if ($product_is_updated){
                                                $count_updated_products++;
                                        }

                                        if ($marked_as_out_of_stock_products){
                                                $count_marked_as_out_of_stock_products++;
                                        }

                                        if ($marked_as_in_stock_products){
                                                $count_marked_as_in_stock_products++;
                                        }
                                } else {
                                        $NEW_PRODUCTS[] = $v[1]." | ".$v[2]." | ".$In_Stock." | ".$Low_in_Stock." | ".$Out_of_Stock." | ".$cost_to_us;
                                }
			}
//func_print_r($SKU, $Item_Name, $MSRP, $MAP, $Retail_Price, $Your_Price, $Inventory_Status);

                }

                if (!empty($all_feed_productcodes) && is_array($all_feed_productcodes)){

                        $count_products = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[products] WHERE productcode LIKE '".$manufacturer_code."-%' AND forsale='Y'");

                        if ($count_products > 0){

                                $manufacturer_code_products = db_query("SELECT productid, productcode, forsale, update_search_index FROM $sql_tbl[products] WHERE productcode LIKE '".$manufacturer_code."-%' AND forsale='Y'");

                                $line_number = 0;
                                print "<br />Second iteration:<br />";
                                while ($product = db_fetch_array($manufacturer_code_products)) {

                                        $line_number++;
                                        if ($line_number % 100 == 0) {
                                                func_flush(".");
                                                if($line_number % 5000 == 0) {
                                                        func_flush("<br />\n");
                                                }

                                                func_flush();
                                        }

                                        $productcode = strtoupper(trim($product["productcode"]));

                                        if (!in_array($productcode, $all_feed_productcodes) && $product["forsale"] != "N") {
                                                $discontinued_products[] = $product;
/*
                                                $update_search_index = $product["update_search_index"];
                                                if ($update_search_index == "N"){
                                                        $update_search_index = "D";
                                                }
*/
//                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N', update_search_index='$update_search_index' WHERE productid='".$product["productid"]."'");
                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N' WHERE productid='".$product["productid"]."'");
                                        }
                                }
                        }
                }


                db_query("UPDATE $sql_tbl[manufacturers] SET d_most_recent_feed_updation_date='".$launch_time."' WHERE manufacturerid='$manufacturerid'");

                $count_discontinued_products = count($discontinued_products);
                if (!empty($discontinued_products) && is_array($discontinued_products)){

                        $subj = $general_info["manufacturer"]." FEED UPDATE - discontinued products";
                        $body = "Discontinued products count: ".$count_discontinued_products."\n\n";

                        $body .= "<table border='1'>\n<tr><td>ProductCode</td><td>Link to SF backend</td></tr>\n";
                        foreach ($discontinued_products as $k => $v){
                                $store_url = "www.artistsupplysource.com";
                                $body .= "<tr><td> ".$v["productcode"]." </td><td> <a href='http://".$store_url."/admin/product_modify.php?productid=".$v["productid"]."'>http://".$store_url."/admin/product.php?productid=".$v["productid"]."</a> </td></tr>\n";
                        }
                        $body .= "</table>";

                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

//                print"<br />For test purpose: discontinued_products:";
//                func_print_r($discontinued_products);
                }

                $count_NEW_PRODUCTS = count($NEW_PRODUCTS);
                if (!empty($NEW_PRODUCTS) && is_array($NEW_PRODUCTS)){
                        $subj = $general_info["manufacturer"]." FEED UPDATE - new products";
                        $body = "Expected new products count: ".$count_NEW_PRODUCTS."\n\n";

                        $body .= "Item Number | Item Description | In Stock | Low in Stock | Out of Stock | Price \n";
                        foreach ($NEW_PRODUCTS as $k => $v){
                                $body .= $v."\n";
                        }

                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

//              print"<br />For test purpose: NEW_PRODUCTS:";
//              func_print_r($NEW_PRODUCTS);
                }

                $count_all_feed_productcodes = count($all_feed_productcodes);
                $sum_updated_products = $count_marked_as_out_of_stock_products + $count_marked_as_in_stock_products + $count_discontinued_products;

                $subj = $general_info["manufacturer"]." FEED UPDATE - summary";
                $body = $general_info["manufacturer"]." FEED UPDATE - summary";
		$body .= "products in storefront: ".$count_products."\n";
                $body .= "products in feed: ".$count_all_feed_productcodes."\n";
                $body .= "updated products: ".$sum_updated_products."\n";
                $body .= "marked as `out of stock` products: ".$count_marked_as_out_of_stock_products."\n";
                $body .= "marked as `in stock` products: ".$count_marked_as_in_stock_products."\n";
                $body .= "new products: ".$count_NEW_PRODUCTS."\n";
                $body .= "discontinued products: ".$count_discontinued_products."\n"; 
		$function_time = time() - $function_launch_time; 
		$function_time = $function_time/(60);
		$function_time = round($function_time,1); 
		$body .= "Duration: ".$function_time." Mins\n";

		func_backprocess_log("supplier feeds", $body);
//                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
        }


}

function func_GENERAL_ARL_FEED($manufacturerid){
        global $sql_tbl, $xcart_dir, $launch_time, $function_launch_time;

        $needed_lines = array();

        $file_is_found_and_uploaded = false;
        $file_is_found = false;
        $count_updated_products = 0;
        $count_marked_as_out_of_stock_products = 0;
        $count_marked_as_in_stock_products = 0;
        $current_time = time();

        if (function_exists("ftp_connect")) {

                $general_info = func_query_first("SELECT manufacturer, d_ftp_host, d_ftp_login, d_ftp_password, d_ftp_folder, d_product_management_team_email, code FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");
		$manufacturer_code = trim($general_info["code"]);

                $ftp = ftp_connect($general_info["d_ftp_host"]);
                if ($ftp && @ftp_login($ftp, $general_info["d_ftp_login"], $general_info["d_ftp_password"])) {

                        ftp_pasv($ftp, true);

                        $local_file = $xcart_dir . "/files/product_feeds/" .$manufacturerid."_846Arlistk.csv";
                        $server_file = $general_info["d_ftp_folder"]."846Arlistk.csv";

                        if (@ftp_get($ftp, $local_file, $server_file, FTP_BINARY)) {
                                $file_is_found = true;
                        }

                        ftp_quit($ftp);


                } else {
                        print("Could not open host. (Distributor: ".$general_info["manufacturer"] .")<br />");
                }
        }

//die("asd");
        if ($file_is_found){

#
##
###
//                $lines_in_file = count(file($local_file));
###
                $lines_in_file = 0;
                $handle = @fopen($local_file, "r");
                while (($buffer = fgetcsv($handle, 20480, ",")) !== FALSE) {
                        $lines_in_file++;
                        if ($lines_in_file % 100 == 0) {
                                func_flush(".");
                                if($lines_in_file % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }
                }
                fclose($handle);
###

                $additional_info = func_query_first("SELECT d_last_feed_rows_processed, d_validation_threshold FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");

                if ($additional_info["d_last_feed_rows_processed"] > 0){
                        $lines_in_file_DIV_last_feed_rows_processed = $lines_in_file/$additional_info["d_last_feed_rows_processed"];

                        if ($lines_in_file_DIV_last_feed_rows_processed < $additional_info["d_validation_threshold"]){
                                $subj = "SUPPLIER FEED validation failed!!!";
                                $body = $general_info["manufacturer"] . ". Count lines in new file: ".$lines_in_file."\n";
                                $body .= "Last feed rows processed: ".$additional_info["d_last_feed_rows_processed"]."\n";
                                $body .= "processing stopped";
                                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                                return;
                        }
                }
                db_query("UPDATE $sql_tbl[manufacturers] SET d_last_feed_rows_processed='$lines_in_file' WHERE manufacturerid='$manufacturerid'");
###
##
#

                $handle = @fopen($local_file, "r");
                if ($handle) {
                    $line_number = 0;
                    $NEW_PRODUCTS = array();
                    $discontinued_products = array();
                    $all_feed_productcodes = array();

                    print "<br />".$general_info["manufacturer"]."<br />";
                    print "First iteration:<br />";

                    while (($buffer = fgetcsv($handle, 20480, ",")) !== FALSE) {
                        $line_number++;

                        if ($line_number % 100 == 0) {
                                func_flush(".");
                                if($line_number % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }

                        if ($line_number > 1) {  //<-------------------

//func_print_r($buffer);

                                $SKU = trim($buffer[0]);
                                $SKU = strtoupper($SKU);
                                $AVAIL = trim($buffer[7]);
                                $DATE_UPDATED = trim($buffer[8]);
                                $FEED_ETA = trim($buffer[9]);

                                if ($AVAIL == ""){
                                        $AVAIL = 0;
                                }

				$feed_productcode = $manufacturer_code . "-" . $SKU;
                                $all_feed_productcodes[] = $feed_productcode;

                                $product_info_arr = func_query_first("SELECT productid, productcode, forsale, avail, r_avail, update_search_index, eta_date_mm_dd_yyyy, lead_time_message FROM $sql_tbl[products] WHERE productcode='".addslashes($feed_productcode)."'");


//func_print_r($SKU, $AVAIL, $DATE_UPDATED, $FEED_ETA, $product_info_arr,$qqq);
//die();
                                if (!empty($product_info_arr)){

                                        $productcode = $product_info_arr["productcode"];
                                        $productid = $product_info_arr["productid"];

                                        $current_forsale = $product_info_arr["forsale"];
                                        $current_avail = $product_info_arr["r_avail"];
                                        $current_lead_time_message = $product_info_arr["lead_time_message"];
                                        $current_eta_date_mm_dd_yyyy = $product_info_arr["eta_date_mm_dd_yyyy"];
					$current_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($current_eta_date_mm_dd_yyyy, "m/d/Y");

                                        $new_forsale = "Y";
                                        $new_avail = $current_avail;
                                        $new_eta_date_mm_dd_yyyy = $current_eta_date_mm_dd_yyyy;
                                        $new_eta_date_mm_dd_yyyy_time = "";
                                        $new_lead_time_message = $current_lead_time_message;

                                        $product_is_updated = false;
                                        $marked_as_out_of_stock_products = false;
                                        $marked_as_in_stock_products = false;

					if ($AVAIL > 0){
						$new_avail = $AVAIL;
						$new_eta_date_mm_dd_yyyy = "";
					} else {
						if (strtolower($FEED_ETA) == "special order item"){
							$new_avail = 1000;
							$new_eta_date_mm_dd_yyyy = "";
							$new_lead_time_message = "Special order item";
						} elseif (strtolower($FEED_ETA) == "unknown eta"){
							$new_avail = 0;
							$new_eta_date_mm_dd_yyyy_time = time() + 60*60*24*35;
						} else {
							$new_avail = 0;
							$new_eta_date_mm_dd_yyyy = $FEED_ETA;
						}
					}

                                        if (!empty($new_eta_date_mm_dd_yyyy_time)){
                                                $new_eta_date_mm_dd_yyyy = date("m/d/Y", $new_eta_date_mm_dd_yyyy_time);
                                        }

                                        if ($new_avail == "0"){
                                                if ($current_avail > 0){
                                                        $marked_as_out_of_stock_products = true;
                                                }
                                        } else {
                                                if ($current_avail == 0){
                                                        $marked_as_in_stock_products = true;
                                                }
                                        }

                                        if ($new_forsale != $current_forsale || $new_eta_date_mm_dd_yyyy != $current_eta_date_mm_dd_yyyy || $current_avail != $new_avail || $new_lead_time_message != $current_lead_time_message){
/*
                                                        $update_search_index = 'Y';
                                                        if ($new_forsale == 'N' && $product_info_arr["update_search_index"] == "N"){
                                                                $update_search_index = 'D';
                                                        }
*/
//                                                        db_query("UPDATE $sql_tbl[products] SET forsale='$new_forsale', r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale', lead_time_message='$new_lead_time_message', update_search_index='$update_search_index' WHERE productid='$productid'");
							$new_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($new_eta_date_mm_dd_yyyy, "seconds");
                                                        db_query("UPDATE $sql_tbl[products] SET forsale='$new_forsale', r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale', lead_time_message='$new_lead_time_message' WHERE productid='$productid'");
                                                        $product_is_updated = true;
                                        }

                                        if ($product_is_updated){
                                                $file_is_found_and_uploaded = true;
                                                $count_updated_products++;
                                        }

                                        if ($marked_as_out_of_stock_products){
                                                $count_marked_as_out_of_stock_products++;
                                        }

                                        if ($marked_as_in_stock_products){
                                                $count_marked_as_in_stock_products++;
                                        }

                                } else {
                                        $NEW_PRODUCTS[] = implode(", ", $buffer);
                                }
                        } else {
				$NEW_PRODUCTS_header = implode(", ", $buffer);
			}

                    }
                    fclose($handle);

                    if (!empty($all_feed_productcodes) && is_array($all_feed_productcodes)){

                        $count_products = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[products] WHERE productcode LIKE '".$manufacturer_code."-%' AND forsale='Y'");

                        if ($count_products > 0){
                                $manufacturer_code_products = db_query("SELECT productid, productcode, forsale, update_search_index FROM $sql_tbl[products] WHERE productcode LIKE '".$manufacturer_code."-%' AND forsale='Y'");

                                $line_number = 0;
                                print "<br />Second iteration:<br />";
                                while ($product = db_fetch_array($manufacturer_code_products)) {

                                        $line_number++;
                                        if ($line_number % 100 == 0) {
                                                func_flush(".");
                                                if($line_number % 5000 == 0) {
                                                        func_flush("<br />\n");
                                                }

                                                func_flush();
                                        }

                                        $productcode = strtoupper(trim($product["productcode"]));

                                        if (!in_array($productcode, $all_feed_productcodes) && $product["forsale"] != "N") {
                                                $file_is_found_and_uploaded = true;
                                                $discontinued_products[] = $product;
/*
                                                $update_search_index = $product["update_search_index"];
                                                if ($update_search_index == "N"){
                                                        $update_search_index = "D";
                                                }
*/
//                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N', update_search_index='$update_search_index' WHERE productid='".$product["productid"]."'");
                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N' WHERE productid='".$product["productid"]."'");
                                        }
                                }
                        }
                    }
                }
        }

                db_query("UPDATE $sql_tbl[manufacturers] SET d_most_recent_feed_updation_date='".$launch_time."' WHERE manufacturerid='$manufacturerid'");

                $count_discontinued_products = count($discontinued_products);
                if (!empty($discontinued_products) && is_array($discontinued_products)){

                        $subj = $general_info["manufacturer"]." FEED UPDATE - discontinued products";
                        $body = "Discontinued products count: ".$count_discontinued_products."\n\n";

                        $body .= "<table border='1'>\n<tr><td>ProductCode</td><td>Link to SF backend</td></tr>\n";
                        foreach ($discontinued_products as $k => $v){
                                $store_url = "www.artistsupplysource.com";
                                $body .= "<tr><td> ".$v["productcode"]." </td><td> <a href='http://".$store_url."/admin/product_modify.php?productid=".$v["productid"]."'>http://".$store_url."/admin/product.php?productid=".$v["productid"]."</a> </td></tr>\n";
                        }
                        $body .= "</table>";

                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

//                print"<br />For test purpose: discontinued_products:";
//                func_print_r($discontinued_products);
                }

                $count_NEW_PRODUCTS = count($NEW_PRODUCTS);
                if (!empty($NEW_PRODUCTS) && is_array($NEW_PRODUCTS)){
                        $subj = $general_info["manufacturer"]." FEED UPDATE - new products";
                        $body = "Expected new products count: ".$count_NEW_PRODUCTS."\n\n";

			$body .= $NEW_PRODUCTS_header."\n";
                        foreach ($NEW_PRODUCTS as $k => $v){
                                $body .= $v."\n";
                        }

                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

//              print"<br />For test purpose: NEW_PRODUCTS:";
//              func_print_r($NEW_PRODUCTS);
                }

                $count_all_feed_productcodes = count($all_feed_productcodes);
                $sum_updated_products = $count_marked_as_out_of_stock_products + $count_marked_as_in_stock_products + $count_discontinued_products;

                $subj = $general_info["manufacturer"]." FEED UPDATE - summary";
                $body = $general_info["manufacturer"]." FEED UPDATE - summary";
                $body .= "products in storefront: ".$count_products."\n";
                $body .= "products in feed: ".$count_all_feed_productcodes."\n";
                $body .= "updated products: ".$sum_updated_products."\n";
                $body .= "marked as `out of stock` products: ".$count_marked_as_out_of_stock_products."\n";
                $body .= "marked as `in stock` products: ".$count_marked_as_in_stock_products."\n";
                $body .= "new products: ".$count_NEW_PRODUCTS."\n";
                $body .= "discontinued products: ".$count_discontinued_products."\n"; 
		$function_time = time() - $function_launch_time; 
		$function_time = $function_time/(60);
		$function_time = round($function_time,1); 
		$body .= "Duration: ".$function_time." Mins\n";

		func_backprocess_log("supplier feeds", $body);
//                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
}

function func_GENERAL_WSP_FEED($manufacturerid){
        global $sql_tbl, $xcart_dir, $launch_time, $function_launch_time;

	$general_info = func_query_first("SELECT manufacturer, d_ftp_host, d_ftp_login, d_ftp_password, d_ftp_folder, d_product_management_team_email, code FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");
	$manufacturer_code = trim($general_info["code"]);

	$my_subdomain_arr = explode(".sharefile.com", $general_info["d_ftp_host"]);
	$my_subdomain_arr2 = explode("https://",$my_subdomain_arr[0]);
	$my_subdomain = array_pop($my_subdomain_arr2);

	$folder = $my_subdomain_arr[1];

	// Create ShareFile object
	$secure = true;
	$sharefile = new Sharefile($secure);

	// Create connection object
	$connection = $sharefile->open($my_subdomain, $general_info["d_ftp_login"], $general_info["d_ftp_password"]);
	if (isset($connection) && ! $sharefile->is_error()) {


		$response = $connection->folder_list("foaa9bd9-8b0d-486e-a413-453b1e21d83a");
		if ($connection->is_error()) {
                	echo 'ERROR: ' . $connection->get_error();
	        }
        	else {
			$response_arr = (array) $response;

			if (!empty($response_arr["value"]) && is_array($response_arr["value"])){

				$current_date = date("m/d/Y");
				$all_files = array();

				foreach ($response_arr["value"] as $k => $v){
					$v = (array) $v;
					$response_arr["value"][$k] = $v;

					$creationdate_arr = explode(" ", trim($v["creationdate"]));
					$am_pm = $creationdate_arr["2"];

					$hour_min_sec_arr = explode(":", trim($creationdate_arr["1"]));

					$month_day_year_arr = explode("/", trim($creationdate_arr["0"]));

					if ($am_pm == "PM"){
						$HOUR = 12 + $hour_min_sec_arr[0];
					} else {
						$HOUR = $hour_min_sec_arr[0];
					}

					$MIN = $hour_min_sec_arr[1];
					$SEC = $hour_min_sec_arr[2];
					$MONTH = $month_day_year_arr["0"];
					$DAY = $month_day_year_arr["1"];
					$YEAR = $month_day_year_arr["2"];
					
					$creationdate_time = mktime($HOUR, $MIN, $SEC, $MONTH, $DAY, $YEAR);

					$response_arr["value"][$k]["creationdate_time"] = $creationdate_time;
					$v["creationdate_time"] = $creationdate_time;

					$creationdate_new_format = date("m/d/Y", $creationdate_time);
					$response_arr["value"][$k]["creationdate_new_format"] = $creationdate_new_format;
					$v["creationdate_new_format"] = $creationdate_new_format;
	
					$response_arr["value"][$k]["current_date"] = $current_date;
					$v["current_date"] = $current_date;

					$displayname = strtolower($v["displayname"]);

					if ($v["type"] == "file" && strpos($displayname, 'stock status') !== false){
						$all_files[] = $v;
					}
				}

				$all_files = my_array_sort($all_files, "creationdate_time", SORT_DESC);
				$all_files = array_values($all_files);

				if (!empty($all_files[0])){

#
##  UNCOMMENT OUT WHEN READY FOR UPLOAD !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
###
					if ($all_files[0]["creationdate_new_format"] == $all_files[0]["current_date"])
###
## UNCOMMENT OUT WHEN READY FOR UPLOAD !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
#

						$first_file_info = $all_files[0];
				}
			}
	        }

		if (!empty($first_file_info) && is_array($first_file_info)){


			$response = $connection->file_get_download_link($first_file_info["id"]);

	                if ($connection->is_error()) {
        	                echo 'ERROR: ' . $connection->get_error();
	                }
        	        else {

				$response_arr = (array) $response;

			        $local_file = $xcart_dir . "/files/product_feeds/" .$manufacturerid."_stockstatus.xls";

			        if(!@copy($response_arr["value"], $local_file)){
			                $errors= error_get_last();
			                echo "COPY ERROR: ".$errors['type'];
			                echo "<br />\n".$errors['message'];
			        } else {

			                $data = new Spreadsheet_Excel_Reader();

			                // Set output Encoding.
			                //$data->setOutputEncoding('CP1251');

			                $data->read($local_file);


			                $counter = 0;
			
			                for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
			                    if ($i >= 1){
			                        for ($j = 0; $j < $data->sheets[0]['numCols']; $j++) {
			
			                                $tmp_val = $data->sheets[0]['cells'][$i][$j];
			                                $tmp_val = trim($tmp_val);

			                                $needed_lines[$counter][$j] = $tmp_val;
			                        }
		                        $counter++;
			                    }
			                }
				}
			}
		}
	}
	else {
	        echo $sharefile->get_error();
	}

        if (!empty($needed_lines)){

		#
		$lines_in_file = count($needed_lines);
                db_query("UPDATE $sql_tbl[manufacturers] SET d_last_feed_rows_processed='$lines_in_file' WHERE manufacturerid='$manufacturerid'");
                #

                print "<br />".$general_info["manufacturer"]."<br />";
                print "First iteration:<br />";

//func_print_r($first_file_info, $response_arr, $needed_lines);
//die();

                $count_marked_as_in_stock_products = 0;
                $count_marked_as_out_of_stock_products = 0;
                $count_updated_products = 0;


                $count_marked_as_in_stock_products = 0;
                $count_marked_as_out_of_stock_products = 0;
                $count_updated_products = 0;

                $NEW_PRODUCTS = array();
                $discontinued_products = array();
                $all_feed_productcodes = array();


                foreach ($needed_lines as $k => $v){

                        if ($k % 100 == 0) {
                                        func_flush(".");
                                        if($k % 5000 == 0) {
                                                func_flush("<br />\n");
                                        }

                                        func_flush();
                        }

                        $tmp_SKU = trim($v[2]);
			$tmp_SKU = strtoupper($tmp_SKU);

                        if (!empty($tmp_SKU) && $tmp_SKU != 'SKU'){

                                $SKU = $tmp_SKU;
                                $cost_to_us = price_format(trim($v[6]));
                                $list_price = price_format(trim($v[7]));
                                $In_Stock = strtoupper(trim($v[8]));
				$avail = trim($v[9]);
				$UPC = trim($v[4]);

				$Line_for_NEW_PRODUCTS = $SKU . " | " . $UPC . " | " . $cost_to_us . " | " . $list_price . " | " . $In_Stock . " | " . $avail;

				if (empty($NEW_PRODUCTS)){
					$NEW_PRODUCTS[] = "SKU | UPC | COST | MSRP | AVAILABILITY | Quantity";
				}

                                $feed_productcode = $manufacturer_code . "-" . $SKU;
                                $all_feed_productcodes[] = $feed_productcode;

                                $product_info_arr = func_query_first("SELECT productid, productcode, forsale, avail, r_avail, update_search_index, eta_date_mm_dd_yyyy, cost_to_us, list_price FROM $sql_tbl[products] WHERE productcode='".addslashes($feed_productcode)."'");

                                if (!empty($product_info_arr)){

                                        $productcode = $product_info_arr["productcode"];
                                        $productid = $product_info_arr["productid"];
                                        $current_forsale = $product_info_arr["forsale"];
                                        $current_avail = $product_info_arr["r_avail"];
                                        $current_cost_to_us = $product_info_arr["cost_to_us"];
                                        $current_list_price = $product_info_arr["list_price"];
                                        $current_eta_date_mm_dd_yyyy = $product_info_arr["eta_date_mm_dd_yyyy"];
					$current_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($current_eta_date_mm_dd_yyyy, "m/d/Y");

                                        $product_is_updated = false;
                                        $marked_as_out_of_stock_products = false;
                                        $marked_as_in_stock_products = false;

                                        $new_forsale = "Y";
                                        $new_avail = $current_avail;
                                        $new_eta_date_mm_dd_yyyy = $current_eta_date_mm_dd_yyyy;
                                        $new_eta_date_mm_dd_yyyy_time = "";
                                        $new_cost_to_us = $current_cost_to_us;
                                        $new_list_price = $current_list_price;

                                        if ($In_Stock == "IN STOCK"){
                                                $new_avail = $avail;
                                                $new_eta_date_mm_dd_yyyy = "";
                                                $new_cost_to_us = $cost_to_us;
						$new_list_price = $list_price;
                                        } elseif ($In_Stock == "OUT OF STOCK"){
                                                $new_avail = 0;
						$new_eta_date_mm_dd_yyyy_time = time() + 60*60*24*35;
                                                $new_cost_to_us = $cost_to_us;
						$new_list_price = $list_price;
                                        } elseif ($In_Stock == "DISCONTINUED"){
                                                $new_avail = 0;
						$new_forsale = "N";

						if ($current_forsale != "N"){
							$discontinued_products[] = $product_info_arr;
						}
                                        }

                                        if (!empty($new_eta_date_mm_dd_yyyy_time)){
                                                $new_eta_date_mm_dd_yyyy = date("m/d/Y", $new_eta_date_mm_dd_yyyy_time);
                                        }


                                        if ($new_avail == "0"){
                                                if ($current_avail > 0){
                                                        $marked_as_out_of_stock_products = true;
                                                }
                                        } else {
                                                if ($current_avail == 0){
                                                        $marked_as_in_stock_products = true;
                                                }
                                        }

                                        if ($new_forsale != $current_forsale || $new_eta_date_mm_dd_yyyy != $current_eta_date_mm_dd_yyyy || $current_avail != $new_avail || $new_cost_to_us != $current_cost_to_us || $new_list_price != $current_list_price){
/*
                                                        $update_search_index = 'Y';
                                                        if ($new_forsale == 'N' && $product_info_arr["update_search_index"] == "N"){
                                                                $update_search_index = 'D';
                                                        }
*/
//                                                        db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale', cost_to_us='$new_cost_to_us', list_price='$new_list_price', update_search_index='$update_search_index' WHERE productid='$productid'");
							$new_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($new_eta_date_mm_dd_yyyy, "seconds");
                                                        db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale', cost_to_us='$new_cost_to_us', list_price='$new_list_price' WHERE productid='$productid'");
                                                        $product_is_updated = true;
                                        }

                                        if ($product_is_updated){
                                                $count_updated_products++;
                                        }

                                        if ($marked_as_out_of_stock_products){
                                                $count_marked_as_out_of_stock_products++;
                                        }

                                        if ($marked_as_in_stock_products){
                                                $count_marked_as_in_stock_products++;
                                        }
                                } else {
                                        $NEW_PRODUCTS[] = $Line_for_NEW_PRODUCTS;
                                }

                        }
//func_print_r($SKU, $Item_Name, $MSRP, $MAP, $Retail_Price, $Your_Price, $Inventory_Status);

                }


                $count_products = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[products] WHERE productcode LIKE '".$manufacturer_code."-%' AND forsale='Y'");
                if (!empty($all_feed_productcodes) && is_array($all_feed_productcodes)){

                        if ($count_products > 0){

                                $manufacturer_code_products = db_query("SELECT productid, productcode, forsale, update_search_index FROM $sql_tbl[products] WHERE productcode LIKE '".$manufacturer_code."-%' AND forsale='Y'");

                                $line_number = 0;
                                print "<br />Second iteration:<br />";
                                while ($product = db_fetch_array($manufacturer_code_products)) {

                                        $line_number++;
                                        if ($line_number % 100 == 0) {
                                                func_flush(".");
                                                if($line_number % 5000 == 0) {
                                                        func_flush("<br />\n");
                                                }

                                                func_flush();
                                        }

                                        $productcode = strtoupper(trim($product["productcode"]));

                                        if (!in_array($productcode, $all_feed_productcodes) && $product["forsale"] != "N") {
                                                $discontinued_products[] = $product;
/*
                                                $update_search_index = $product["update_search_index"];
                                                if ($update_search_index == "N"){
                                                        $update_search_index = "D";
                                                }
*/
//                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N', update_search_index='$update_search_index' WHERE productid='".$product["productid"]."'");
                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N' WHERE productid='".$product["productid"]."'");
                                        }
                                }
                        }
                }


                db_query("UPDATE $sql_tbl[manufacturers] SET d_most_recent_feed_updation_date='".$launch_time."' WHERE manufacturerid='$manufacturerid'");

                $count_discontinued_products = count($discontinued_products);
                if (!empty($discontinued_products) && is_array($discontinued_products)){

                        $subj = $general_info["manufacturer"]." FEED UPDATE - discontinued products";
                        $body = "Discontinued products count: ".$count_discontinued_products."\n\n";

                        $body .= "<table border='1'>\n<tr><td>ProductCode</td><td>Link to SF backend</td></tr>\n";
                        foreach ($discontinued_products as $k => $v){
                                $store_url = "www.artistsupplysource.com";
                                $body .= "<tr><td> ".$v["productcode"]." </td><td> <a href='http://".$store_url."/admin/product_modify.php?productid=".$v["productid"]."'>http://".$store_url."/admin/product.php?productid=".$v["productid"]."</a> </td></tr>\n";
                        }
                        $body .= "</table>";

                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                }

                $count_NEW_PRODUCTS = count($NEW_PRODUCTS);
                if (
			!empty($NEW_PRODUCTS) && is_array($NEW_PRODUCTS) 
			&& $count_NEW_PRODUCTS > 1  // First row is Header
		){
			$count_NEW_PRODUCTS -= 1; // First row is Header
                        $subj = $general_info["manufacturer"]." FEED UPDATE - new products";
                        $body = "Expected new products count: ".$count_NEW_PRODUCTS."\n\n";

                        foreach ($NEW_PRODUCTS as $k => $v){
                                $body .= $v."\n";
                        }

                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                }

                $count_all_feed_productcodes = count($all_feed_productcodes);
                $sum_updated_products = $count_marked_as_out_of_stock_products + $count_marked_as_in_stock_products + $count_discontinued_products;

                $subj = $general_info["manufacturer"]." FEED UPDATE - summary";
                $body = $general_info["manufacturer"]." FEED UPDATE - summary";
		$body .= "products in storefront: ".$count_products."\n";
                $body .= "products in feed: ".$count_all_feed_productcodes."\n";
                $body .= "updated products: ".$sum_updated_products."\n";
                $body .= "marked as `out of stock` products: ".$count_marked_as_out_of_stock_products."\n";
                $body .= "marked as `in stock` products: ".$count_marked_as_in_stock_products."\n";
                $body .= "new products: ".$count_NEW_PRODUCTS."\n";
                $body .= "discontinued products: ".$count_discontinued_products."\n"; 
		$function_time = time() - $function_launch_time; 
		$function_time = $function_time/(60);
		$function_time = round($function_time,1); 
		$body .= "Duration: ".$function_time." Mins\n";

		func_backprocess_log("supplier feeds", $body);
//                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
        }
}

function func_GENERAL_OSP_FEED($manufacturerid){
        global $sql_tbl, $xcart_dir, $launch_time, $function_launch_time;

        $file_is_found_and_uploaded = false;
        $file_is_found = false;
        $count_updated_products = 0;
        $count_marked_as_out_of_stock_products = 0;
        $count_marked_as_in_stock_products = 0;

        if (function_exists("ftp_connect")) {

                $general_info = func_query_first("SELECT manufacturer, code, d_ftp_host, d_ftp_login, d_ftp_password, d_ftp_folder, d_product_management_team_email, d_last_feed_rows_processed, d_validation_threshold FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");

		$manufacturer_code = trim($general_info["code"]);

                $ftp = ftp_connect($general_info["d_ftp_host"]);
                if ($ftp && @ftp_login($ftp, $general_info["d_ftp_login"], $general_info["d_ftp_password"])) {

                        ftp_pasv($ftp, true);

			$contents = ftp_nlist($ftp, $general_info["d_ftp_folder"]);
			
			if (!empty($contents) && is_array($contents)){
				$all_files = array();
				foreach ($contents as $k => $v){
					if (strpos($v, 'Warehouse_Inventory_for_Cust_00000_') !== false && strpos($v, '.csv') !== false ){

						$tmp_file_arr  = explode(".csv", $v);
						$tmp_date_arr = explode("Warehouse_Inventory_for_Cust_00000_", $tmp_file_arr[0]);
						$tmp_date = $tmp_date_arr[1];

						if (!empty($tmp_date)){
							$month = substr($tmp_date, 0, 2);
							$day = substr($tmp_date, 2, 2);
							$year = substr($tmp_date, 4, 4);

							$tmp_mktime = mktime(0, 0, 0, $month, $day, $year);
							$all_files[$k]["file"] = $v;
							$all_files[$k]["time"] = $tmp_mktime;
						}
					}
				}

				if (!empty($all_files)){
					$all_files = my_array_sort($all_files, "time", SORT_DESC);
					$all_files = array_values($all_files);

					$server_file_name = $all_files[0]["file"];
					$server_file_name_full_path = $general_info["d_ftp_folder"] . $server_file_name;

					$local_file_name = $manufacturerid."_".$server_file_name;
					$local_file_name_full_path = $xcart_dir . "/files/product_feeds/" . $local_file_name;
				}
			}

			if (!empty($local_file_name_full_path) && !empty($server_file_name_full_path)){
	                        if (@ftp_get($ftp, $local_file_name_full_path, $server_file_name_full_path, FTP_BINARY)) {
        	                        $file_is_found = true;
                	        }
			}

                        ftp_quit($ftp);

                } else {
                        print("Could not open host. (Distributor: ".$general_info["manufacturer"] .")<br />");
                }
        }

        if ($file_is_found){

                $handle = @fopen($local_file_name_full_path, "r");
                if ($handle) {

		    # check for lines
//		    $lines_in_file = count(file($local_file_name_full_path));
###
                    $lines_in_file = 0;
                    while (($buffer = fgetcsv($handle, 20480, ",")) !== FALSE) {
                        $lines_in_file++;
                        if ($lines_in_file % 100 == 0) {
                                func_flush(".");
                                if($lines_in_file % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }
                    }
                    fclose($handle);
###

	            if ($general_info["d_last_feed_rows_processed"] > 0){
        	                $lines_in_file_DIV_last_feed_rows_processed = $lines_in_file/$general_info["d_last_feed_rows_processed"];

	                        if ($lines_in_file_DIV_last_feed_rows_processed < $general_info["d_validation_threshold"]){
                        	        $subj = "SUPPLIER FEED validation failed!!!";
                	                $body = $general_info["manufacturer"] . ". Count lines in new file: ".$lines_in_file."\n";
        	                        $body .= "Last feed rows processed: ".$general_info["d_last_feed_rows_processed"]."\n";
	                                $body .= "processing stopped";
                                	func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
					fclose($handle);
                        	        return;
                	        }
        	    }
	            db_query("UPDATE $sql_tbl[manufacturers] SET d_last_feed_rows_processed='$lines_in_file' WHERE manufacturerid='$manufacturerid'");
		    #


                    $line_number = 0;
                    $NEW_PRODUCTS = array();
                    $discontinued_products = array();
                    $all_feed_productcodes = array();

                    print "<br />".$general_info["manufacturer"]."<br />";
                    print "First iteration:<br />";

		    $handle = @fopen($local_file_name_full_path, "r");
                    while (($buffer = fgetcsv($handle, 20480, ",")) !== FALSE) {
                        $line_number++;

                        if ($line_number % 100 == 0) {
                                func_flush(".");
                                if($line_number % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }

                        if ($line_number > 1) {

                                $SKU = trim($buffer[1]);
                                $SKU = strtoupper($SKU);
                                $AVAIL = trim($buffer[2]);
                                $FEED_ETA = trim($buffer[4]);
                                $FEED_STATUS = trim($buffer[5]);
				$FEED_STATUS = strtolower($FEED_STATUS);

                                if ($AVAIL == ""){
                                        $AVAIL = 0;
                                }

                                $feed_productcode = $manufacturer_code . "-" . $SKU;
                                $all_feed_productcodes[] = $feed_productcode;

                                $product_info_arr = func_query_first("SELECT productid, productcode, update_search_index, forsale, avail, r_avail, eta_date_mm_dd_yyyy FROM $sql_tbl[products] WHERE productcode='".addslashes($feed_productcode)."'");

                                if (!empty($product_info_arr)){

                                        $productcode = $product_info_arr["productcode"];
                                        $productid = $product_info_arr["productid"];

                                        $current_forsale = $product_info_arr["forsale"];
                                        $current_avail = $product_info_arr["r_avail"];
                                        $current_eta_date_mm_dd_yyyy = $product_info_arr["eta_date_mm_dd_yyyy"];
					$current_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($current_eta_date_mm_dd_yyyy, "m/d/Y");

                                        $new_forsale = $current_forsale;
                                        $new_avail = $current_avail;
                                        $new_eta_date_mm_dd_yyyy = $current_eta_date_mm_dd_yyyy;
                                        $new_eta_date_mm_dd_yyyy_time = "";

                                        $product_is_updated = false;
                                        $marked_as_out_of_stock_products = false;
                                        $marked_as_in_stock_products = false;

                                        if ($FEED_STATUS == "discontinued"){
						$new_forsale = "N";
                                                $new_avail = 0;
                                                $new_eta_date_mm_dd_yyyy = "";
                                        } elseif ($FEED_STATUS == "sales item") {

						$new_forsale = "Y";

                                                if ($AVAIL > 0){
                                                        $new_avail = $AVAIL;
                                                        $new_eta_date_mm_dd_yyyy = "";
                                                } else {
                                                        $new_avail = 0;
                                                        $new_eta_date_mm_dd_yyyy = $FEED_ETA;
                                                }
                                        }

                                        if (!empty($new_eta_date_mm_dd_yyyy_time)){
                                                $new_eta_date_mm_dd_yyyy = date("m/d/Y", $new_eta_date_mm_dd_yyyy_time);
                                        }

                                        if ($new_avail == "0"){
                                                if ($current_avail > 0){
                                                        $marked_as_out_of_stock_products = true;
                                                }
                                        } else {
                                                if ($current_avail == 0){
                                                        $marked_as_in_stock_products = true;
                                                }
                                        }

                                        if ($new_forsale != $current_forsale || $new_eta_date_mm_dd_yyyy != $current_eta_date_mm_dd_yyyy || $current_avail != $new_avail){
/*
                                                        $update_search_index = 'Y';
                                                        if ($new_forsale == 'N' && $product_info_arr["update_search_index"] == "N"){
                                                                $update_search_index = 'D';
                                                        }
*/
//                                                        db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale', update_search_index='$update_search_index' WHERE productid='$productid'");
							$new_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($new_eta_date_mm_dd_yyyy, "seconds");
                                                        db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale' WHERE productid='$productid'");
                                                        $product_is_updated = true;
                                        }

                                        if ($product_is_updated){
                                                $file_is_found_and_uploaded = true;
                                                $count_updated_products++;
                                        }

                                        if ($marked_as_out_of_stock_products){
                                                $count_marked_as_out_of_stock_products++;
                                        }

                                        if ($marked_as_in_stock_products){
                                                $count_marked_as_in_stock_products++;
                                        }

                                } else {
                                        $NEW_PRODUCTS[] = implode(", ", $buffer);
                                }
                        } else {
                                $NEW_PRODUCTS_header = implode(", ", $buffer);
                        }

                    }
		    fclose($handle);


                    if (!empty($all_feed_productcodes) && is_array($all_feed_productcodes)){

                        $count_products = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[products] WHERE productcode LIKE '".$manufacturer_code."-%' AND forsale='Y'");

                        if ($count_products > 0){
                                $manufacturer_code_products = db_query("SELECT productid, productcode, forsale, update_search_index FROM $sql_tbl[products] WHERE productcode LIKE '".$manufacturer_code."-%' AND forsale='Y'");

                                $line_number = 0;
                                print "<br />Second iteration:<br />";
                                while ($product = db_fetch_array($manufacturer_code_products)) {

                                        $line_number++;
                                        if ($line_number % 100 == 0) {
                                                func_flush(".");
                                                if($line_number % 5000 == 0) {
                                                        func_flush("<br />\n");
                                                }

                                                func_flush();
                                        }

                                        $productcode = strtoupper(trim($product["productcode"]));

                                        if (!in_array($productcode, $all_feed_productcodes) && $product["forsale"] != "N") {
                                                $file_is_found_and_uploaded = true;
                                                $discontinued_products[] = $product;
/*
                                                $update_search_index = $product["update_search_index"];
                                                if ($update_search_index == "N"){
                                                        $update_search_index = "D";
                                                }
*/
//                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N', update_search_index='$update_search_index' WHERE productid='".$product["productid"]."'");
                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N' WHERE productid='".$product["productid"]."'");
                                        }
                                }
                        }
                    }

                    db_query("UPDATE $sql_tbl[manufacturers] SET d_most_recent_feed_updation_date='".$launch_time."' WHERE manufacturerid='$manufacturerid'");


                    $count_discontinued_products = count($discontinued_products);
                    if (!empty($discontinued_products) && is_array($discontinued_products)){

                        $subj = $general_info["manufacturer"]." FEED UPDATE - discontinued products";
                        $body = "Discontinued products count: ".$count_discontinued_products."\n\n";

                        $body .= "<table border='1'>\n<tr><td>ProductCode</td><td>Link to SF backend</td></tr>\n";
                        foreach ($discontinued_products as $k => $v){
                                $store_url = "www.artistsupplysource.com";
                                $body .= "<tr><td> ".$v["productcode"]." </td><td> <a href='http://".$store_url."/admin/product_modify.php?productid=".$v["productid"]."'>http://".$store_url."/admin/product.php?productid=".$v["productid"]."</a> </td></tr>\n";
                        }
                        $body .= "</table>";

                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                    }

                    $count_NEW_PRODUCTS = count($NEW_PRODUCTS);
                    if (!empty($NEW_PRODUCTS) && is_array($NEW_PRODUCTS)){
                        $subj = $general_info["manufacturer"]." FEED UPDATE - new products";
                        $body = "Expected new products count: ".$count_NEW_PRODUCTS."\n\n";

                        $body .= $NEW_PRODUCTS_header."\n";
                        foreach ($NEW_PRODUCTS as $k => $v){
                                $body .= $v."\n";
                        }

                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                    }

                    $count_all_feed_productcodes = count($all_feed_productcodes);
                    $sum_updated_products = $count_marked_as_out_of_stock_products + $count_marked_as_in_stock_products + $count_discontinued_products;

                    $subj = $general_info["manufacturer"]." FEED UPDATE - summary";
                    $body = $general_info["manufacturer"]." FEED UPDATE - summary";
                    $body .= "products in storefront: ".$count_products."\n";
                    $body .= "products in feed: ".$count_all_feed_productcodes."\n";
                    $body .= "updated products: ".$sum_updated_products."\n";
                    $body .= "marked as `out of stock` products: ".$count_marked_as_out_of_stock_products."\n";
                    $body .= "marked as `in stock` products: ".$count_marked_as_in_stock_products."\n";
                    $body .= "new products: ".$count_NEW_PRODUCTS."\n";
                    $body .= "discontinued products: ".$count_discontinued_products."\n";
                    $function_time = time() - $function_launch_time;
                    $function_time = $function_time/(60);
                    $function_time = round($function_time,1);
                    $body .= "Duration: ".$function_time." Mins\n";

		    func_backprocess_log("supplier feeds", $body);
//                    func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

		    #Clear FTP files
                    $ftp = ftp_connect($general_info["d_ftp_host"]);
                    if ($ftp && @ftp_login($ftp, $general_info["d_ftp_login"], $general_info["d_ftp_password"]) && !empty($all_files)) {

                        ftp_pasv($ftp, true);

//			func_print_r($contents);

			$new_server_file_name_full_path = $general_info["d_ftp_folder"]."backup/".$server_file_name;

			if (ftp_rename($ftp, $server_file_name_full_path, $new_server_file_name_full_path)) {
				
				foreach ($all_files as $k => $v){
					if ($k > 0){
						$delete_file = $general_info["d_ftp_folder"].$v["file"];
						ftp_delete($ftp, $delete_file);
					}
				}
			} else {
				echo "There was a problem while renaming $old_file to $new_file\n";
			}
/*
$backup_contents = ftp_nlist($ftp, $general_info["d_ftp_folder"]."backup/");
func_print_r($backup_contents, $all_files);

$contents = ftp_nlist($ftp, $general_info["d_ftp_folder"]);
func_print_r($contents);
*/

                        ftp_quit($ftp);

                    } else { 
                        print("Could not open host. (Distributor: ".$general_info["manufacturer"] .")<br />");
                    }
		    #


                } // if ($handle)
		else {
			fclose($handle);

	                $subj = "SUPPLIER FEED validation failed!!!";
                        $body = $general_info["manufacturer"] . ". File cannot be opened.";
                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                        return;
		}
	} // if ($file_is_found)
	else {
//                $subj = "SUPPLIER FEED validation failed!!!";
//                $body = $general_info["manufacturer"] . ". File cannot be found.";
//                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
//		return;
	}
}

function func_GENERAL_DRE_FEED($manufacturerid){
        global $sql_tbl, $xcart_dir, $launch_time, $function_launch_time;

        $file_is_found_and_uploaded = false;
        $file_is_found = false;
        $count_updated_products = 0;
        $count_marked_as_out_of_stock_products = 0;
        $count_marked_as_in_stock_products = 0;

        if (function_exists("ftp_connect")) {

                $general_info = func_query_first("SELECT manufacturer, code, d_ftp_host, d_ftp_login, d_ftp_password, d_ftp_folder, d_product_management_team_email, d_last_feed_rows_processed, d_validation_threshold FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");

                $manufacturer_code = trim($general_info["code"]);

                $ftp = ftp_connect($general_info["d_ftp_host"]);
                if ($ftp && @ftp_login($ftp, $general_info["d_ftp_login"], $general_info["d_ftp_password"])) {

                        ftp_pasv($ftp, true);

                        $contents = ftp_nlist($ftp, $general_info["d_ftp_folder"]);

                        if (!empty($contents) && is_array($contents)){
                                $all_files = array();
                                foreach ($contents as $k => $v){
                                        if (strpos($v, 'TEE-ZED Inventory Update ') !== false && strpos($v, '.xls') !== false ){

                                                $tmp_file_arr  = explode(".xls", $v);
                                                $tmp_date_arr = explode("TEE-ZED Inventory Update ", $tmp_file_arr[0]);
                                                $tmp_date = $tmp_date_arr[1];

                                                if (!empty($tmp_date)){
                                                        $month = substr($tmp_date, 0, 2);
                                                        $day = substr($tmp_date, 3, 2);
                                                        $year = substr($tmp_date, 6, 4);
                                                        $tmp_mktime = mktime(0, 0, 0, $month, $day, $year);
                                                        $all_files[$k]["file"] = $v;
                                                        $all_files[$k]["time"] = $tmp_mktime;
                                                }
                                        }
                                }

                                if (!empty($all_files)){
                                        $all_files = my_array_sort($all_files, "time", SORT_DESC);
                                        $all_files = array_values($all_files);

                                        $server_file_name = $all_files[0]["file"];
                                        $server_file_name_full_path = $general_info["d_ftp_folder"] . $server_file_name;

                                        $local_file_name = $manufacturerid."_".$server_file_name;
                                        $local_file_name_full_path = $xcart_dir . "/files/product_feeds/" . $local_file_name;
                                }
                        }

                        if (!empty($local_file_name_full_path) && !empty($server_file_name_full_path)){
                                if (@ftp_get($ftp, $local_file_name_full_path, $server_file_name_full_path, FTP_BINARY)) {
                                        $file_is_found = true;
                                }
                        }

                        ftp_quit($ftp);

                } else {
                        print("Could not open host. (Distributor: ".$general_info["manufacturer"] .")<br />");
                }
        }

        if ($file_is_found){

		$data = new Spreadsheet_Excel_Reader();

                // Set output Encoding.
                //$data->setOutputEncoding('CP1251');

                $data->read($local_file_name_full_path);

                $counter = 0;

                for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
	                if ($i >= 1){
        	                for ($j = 0; $j < $data->sheets[0]['numCols']; $j++) {

                	                $tmp_val = $data->sheets[0]['cells'][$i][$j];
                                        $tmp_val = trim($tmp_val);

                                        $needed_lines[$counter][$j] = $tmp_val;
                                }
                                $counter++;
			}
		}

		$lines_in_file = count($needed_lines);
                if ($general_info["d_last_feed_rows_processed"] > 0){
                                $lines_in_file_DIV_last_feed_rows_processed = $lines_in_file/$general_info["d_last_feed_rows_processed"];

                                if ($lines_in_file_DIV_last_feed_rows_processed < $general_info["d_validation_threshold"]){
                                        $subj = "SUPPLIER FEED validation failed!!!";
                                        $body = $general_info["manufacturer"] . ". Count lines in new file: ".$lines_in_file."\n";
                                        $body .= "Last feed rows processed: ".$general_info["d_last_feed_rows_processed"]."\n";
                                        $body .= "processing stopped";
                                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                                        return;
                                }
                }
                db_query("UPDATE $sql_tbl[manufacturers] SET d_last_feed_rows_processed='$lines_in_file' WHERE manufacturerid='$manufacturerid'");


                if (!empty($needed_lines) && is_array($needed_lines)) {

                    $line_number = 0;
                    $NEW_PRODUCTS = array();
                    $discontinued_products = array();
                    $all_feed_productcodes = array();

                    print "<br />".$general_info["manufacturer"]."<br />";
                    print "First iteration:<br />";

		    $start = false;

		    $months_code = array("jan"=>"1", "feb"=>"2", "mar"=>"3", "apr"=>"4", "may"=>"5", "jun"=>"6", "jul"=>"7", "aug"=>"8", "sep"=>"9", "oct"=>"10", "nov"=>"11", "dec"=>"12");
//func_print_r($months_code);

		    foreach ($needed_lines as $k => $v){
                        $line_number++;

                        if ($line_number % 100 == 0) {
                                func_flush(".");
                                if($line_number % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }

                        $SKU = trim($v[1]);
                        $SKU = strtoupper($SKU);

			if ($SKU == "SKU"){
				$start = true;
				continue;
			}

                        if ($start && !empty($SKU)) {

                                $FEED_STOCK = trim($buffer[3]);
                                $FEED_STOCK = strtolower($FEED_STOCK);

                                $FEED_ETA = trim($v[6]);
				$FEED_ETA = strtolower($FEED_ETA);
				$FEED_ETA_arr = explode("-", $FEED_ETA);
				$year = $FEED_ETA_arr[2];
				if (empty($year)){
					$year = date("Y");
				}

				if (!empty($FEED_ETA_arr[1])){
					$FEED_ETA = mktime("0","0","0", $months_code[$FEED_ETA_arr[1]], $FEED_ETA_arr[0], $year);	
					$FEED_ETA = date("m/d/Y", $FEED_ETA);
				}

/*
                                $AVAIL = trim($buffer[2]);
                                if ($AVAIL == ""){
                                        $AVAIL = 0;
                                }
*/

                                $feed_productcode = $manufacturer_code . "-" . $SKU;
                                $all_feed_productcodes[] = $feed_productcode;

                                $product_info_arr = func_query_first("SELECT productid, productcode, update_search_index, forsale, avail, r_avail, eta_date_mm_dd_yyyy FROM $sql_tbl[products] WHERE productcode='".addslashes($feed_productcode)."'");

                                if (!empty($product_info_arr)){

                                        $productcode = $product_info_arr["productcode"];
                                        $productid = $product_info_arr["productid"];

                                        $current_forsale = $product_info_arr["forsale"];
                                        $current_avail = $product_info_arr["r_avail"];
                                        $current_eta_date_mm_dd_yyyy = $product_info_arr["eta_date_mm_dd_yyyy"];
					$current_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($current_eta_date_mm_dd_yyyy, "m/d/Y");

                                        $new_forsale = $current_forsale;
                                        $new_avail = $current_avail;
                                        $new_eta_date_mm_dd_yyyy = $current_eta_date_mm_dd_yyyy;
                                        $new_eta_date_mm_dd_yyyy_time = "";

                                        $product_is_updated = false;
                                        $marked_as_out_of_stock_products = false;
                                        $marked_as_in_stock_products = false;

                                        if ($FEED_STOCK == "discontinued"){
                                                $new_forsale = "N";
                                                $new_avail = 0;
                                                $new_eta_date_mm_dd_yyyy = "";
                                        } elseif (empty($FEED_STOCK)) {
						$new_forsale = "Y";
						$new_avail = "99999999";
						$new_eta_date_mm_dd_yyyy = "";
                                        } elseif ($FEED_STOCK == "sales item") {
                                                $new_forsale = "Y";
                                                $new_avail = 0;
                                                $new_eta_date_mm_dd_yyyy = $FEED_ETA;
                                        }

                                        if (!empty($new_eta_date_mm_dd_yyyy_time)){
                                                $new_eta_date_mm_dd_yyyy = date("m/d/Y", $new_eta_date_mm_dd_yyyy_time);
                                        }

                                        if ($new_avail == "0"){
                                                if ($current_avail > 0){
                                                        $marked_as_out_of_stock_products = true;
                                                }
                                        } else {
                                                if ($current_avail == 0){
                                                        $marked_as_in_stock_products = true;
                                                }
                                        }

                                        if ($new_forsale != $current_forsale || $new_eta_date_mm_dd_yyyy != $current_eta_date_mm_dd_yyyy || $current_avail != $new_avail){
/*
                                                        $update_search_index = 'Y';
                                                        if ($new_forsale == 'N' && $product_info_arr["update_search_index"] == "N"){
                                                                $update_search_index = 'D';
                                                        }
*/
//                                                        db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale', update_search_index='$update_search_index' WHERE productid='$productid'");
							$new_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($new_eta_date_mm_dd_yyyy, "seconds");
                                                        db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale' WHERE productid='$productid'");
                                                        $product_is_updated = true;
                                        }

                                        if ($product_is_updated){
                                                $file_is_found_and_uploaded = true;
                                                $count_updated_products++;
                                        }

                                        if ($marked_as_out_of_stock_products){
                                                $count_marked_as_out_of_stock_products++;
                                        }

                                        if ($marked_as_in_stock_products){
                                                $count_marked_as_in_stock_products++;
                                        }

                                } else {
                                        $NEW_PRODUCTS[] = $v[1] . ", " . $v[2] . ", " . $v[4] . ", " . $v[5] . ", " . $v[6];
                                }
                        } else {
                                $NEW_PRODUCTS_header = "SKU, Stock, UPC/EAN Code, Description, ETA";
                        }
                    }


                    if (!empty($all_feed_productcodes) && is_array($all_feed_productcodes)){

                        $count_products = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[products] WHERE productcode LIKE '".$manufacturer_code."-%' AND forsale='Y'");

                        if ($count_products > 0){
                                $manufacturer_code_products = db_query("SELECT productid, productcode, forsale, update_search_index FROM $sql_tbl[products] WHERE productcode LIKE '".$manufacturer_code."-%' AND forsale='Y'");

                                $line_number = 0;
                                print "<br />Second iteration:<br />";
                                while ($product = db_fetch_array($manufacturer_code_products)) {

                                        $line_number++;
                                        if ($line_number % 100 == 0) {
                                                func_flush(".");
                                                if($line_number % 5000 == 0) {
                                                        func_flush("<br />\n");
                                                }

                                                func_flush();
                                        }

                                        $productcode = strtoupper(trim($product["productcode"]));

                                        if (!in_array($productcode, $all_feed_productcodes) && $product["forsale"] != "N") {
                                                $file_is_found_and_uploaded = true;
                                                $discontinued_products[] = $product;
/*
                                                $update_search_index = $product["update_search_index"];
                                                if ($update_search_index == "N"){
                                                        $update_search_index = "D";
                                                }
*/
//                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N', update_search_index='$update_search_index' WHERE productid='".$product["productid"]."'");
                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N' WHERE productid='".$product["productid"]."'");
                                        }
                                }
                        }
                    }

                    db_query("UPDATE $sql_tbl[manufacturers] SET d_most_recent_feed_updation_date='".$launch_time."' WHERE manufacturerid='$manufacturerid'");

                    $count_discontinued_products = count($discontinued_products);
                    if (!empty($discontinued_products) && is_array($discontinued_products)){

                        $subj = $general_info["manufacturer"]." FEED UPDATE - discontinued products";
                        $body = "Discontinued products count: ".$count_discontinued_products."\n\n";

                        $body .= "<table border='1'>\n<tr><td>ProductCode</td><td>Link to SF backend</td></tr>\n";
                        foreach ($discontinued_products as $k => $v){
                                $store_url = "www.artistsupplysource.com";
                                $body .= "<tr><td> ".$v["productcode"]." </td><td> <a href='http://".$store_url."/admin/product_modify.php?productid=".$v["productid"]."'>http://".$store_url."/admin/product.php?productid=".$v["productid"]."</a> </td></tr>\n";
                        }
                        $body .= "</table>";

                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                    }

                    $count_NEW_PRODUCTS = count($NEW_PRODUCTS);
                    if (!empty($NEW_PRODUCTS) && is_array($NEW_PRODUCTS)){
                        $subj = $general_info["manufacturer"]." FEED UPDATE - new products";
                        $body = "Expected new products count: ".$count_NEW_PRODUCTS."\n\n";

                        $body .= $NEW_PRODUCTS_header."\n";
                        foreach ($NEW_PRODUCTS as $k => $v){
                                $body .= $v."\n";
                        }

                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                    }

                    $count_all_feed_productcodes = count($all_feed_productcodes);
                    $sum_updated_products = $count_marked_as_out_of_stock_products + $count_marked_as_in_stock_products + $count_discontinued_products;

                    $subj = $general_info["manufacturer"]." FEED UPDATE - summary";
                    $body = $general_info["manufacturer"]." FEED UPDATE - summary";
                    $body .= "products in storefront: ".$count_products."\n";
                    $body .= "products in feed: ".$count_all_feed_productcodes."\n";
                    $body .= "updated products: ".$sum_updated_products."\n";
                    $body .= "marked as `out of stock` products: ".$count_marked_as_out_of_stock_products."\n";
                    $body .= "marked as `in stock` products: ".$count_marked_as_in_stock_products."\n";
                    $body .= "new products: ".$count_NEW_PRODUCTS."\n";
                    $body .= "discontinued products: ".$count_discontinued_products."\n";
                    $function_time = time() - $function_launch_time;
                    $function_time = $function_time/(60);
                    $function_time = round($function_time,1);
                    $body .= "Duration: ".$function_time." Mins\n";

		    func_backprocess_log("supplier feeds", $body);
//                    func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

                    #Clear FTP files
                    $ftp = ftp_connect($general_info["d_ftp_host"]);
                    if ($ftp && @ftp_login($ftp, $general_info["d_ftp_login"], $general_info["d_ftp_password"]) && !empty($all_files)) {

                        ftp_pasv($ftp, true);

//                      func_print_r($contents);

                        $new_server_file_name_full_path = $general_info["d_ftp_folder"]."backup/".$server_file_name;

                        if (ftp_rename($ftp, $server_file_name_full_path, $new_server_file_name_full_path)) {

                                foreach ($all_files as $k => $v){
                                        if ($k > 0){
                                                $delete_file = $general_info["d_ftp_folder"].$v["file"];
                                                ftp_delete($ftp, $delete_file);
                                        }
                                }
                        } else {
                                echo "There was a problem while renaming $old_file to $new_file\n";
                        }
/*
$backup_contents = ftp_nlist($ftp, $general_info["d_ftp_folder"]."backup/");
func_print_r($backup_contents, $all_files);

$contents = ftp_nlist($ftp, $general_info["d_ftp_folder"]);
func_print_r($contents);
*/

                        ftp_quit($ftp);

                    } else {
                        print("Could not open host. (Distributor: ".$general_info["manufacturer"] .")<br />");
                    }
                    #


                } // if ($handle)
                else {
                        $subj = "SUPPLIER FEED validation failed!!!";
                        $body = $general_info["manufacturer"] . ". File cannot be opened.";
                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                        return;
                }
        } // if ($file_is_found)
        else {
//                $subj = "SUPPLIER FEED validation failed!!!";
//                $body = $general_info["manufacturer"] . ". File cannot be found.";
//                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
//              return;
        }
}

function func_HONEST_GREEN_GENERAL_PRODUCT_FEED($product_feed_info){
        global $sql_tbl, $xcart_dir, $launch_time, $function_launch_time;
//	global $import_new_products, $import_new_and_update_existing_products;

	# File
	$productfeed_filename = "hgproductfeed.csv";

	$local_file_name = $product_feed_info["manufacturerid"]."_".$productfeed_filename;
	$local_file_full_path = $xcart_dir . "/files/main_product_feeds/" .$local_file_name;
	$local_file_full_path_for_checking_only = $xcart_dir . "/files/main_product_feeds/check_" .$local_file_name;

	$server_file_name = $productfeed_filename;
	$server_file_full_path = $product_feed_info["ftp_folder"].$productfeed_filename;
	###

	$count_updated_products = 0;
	$count_inserted_products = 0;

        $file_is_found_and_uploaded = false;
        $file_is_found = false;
        $current_time = time();


        if (function_exists("ftp_connect")) {
                $ftp = ftp_connect($product_feed_info["ftp_host"]);
                if ($ftp && @ftp_login($ftp, $product_feed_info["ftp_login"], $product_feed_info["ftp_password"])) {

                        ftp_pasv($ftp, true);

                        if (@ftp_get($ftp, $local_file_full_path, $server_file_full_path, FTP_BINARY)) {
                                $file_is_found = true;
                        }

                        ftp_quit($ftp);
                } else {
                        print("Could not open host. (Distributor: ".$product_feed_info["manufacturer"] .")<br />");
                }
        }


#
##
###
//        $file_is_found = true;
###
##
#


        if ($file_is_found){
                $lines_in_file = 0;
                $handle = @fopen($local_file_full_path, "r");
                while (($buffer = fgetcsv($handle, 20480, ",")) !== FALSE) {
			if (empty($buffer)) continue;

                        $lines_in_file++;
                        if ($lines_in_file % 100 == 0) {
                                func_flush(".");
                                if($lines_in_file % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }
                }
                fclose($handle);

#                if ($product_feed_info["last_products_count_in_file"] > 0){
#                        $lines_in_file_DIV_last_feed_rows_processed = $lines_in_file/$product_feed_info["last_products_count_in_file"];
#
#                        if ($lines_in_file_DIV_last_feed_rows_processed < $product_feed_info["d_validation_threshold"]){
#                                $subj = "SUPPLIER FEED validation failed!!!";
#                                $body = $product_feed_info["manufacturer"] . ". Count lines in new file: ".$lines_in_file."\n";
#                                $body .= "Last feed rows processed: ".$product_feed_info["last_products_count_in_file"]."\n";
#                                $body .= "processing stopped";
#                                func_send_simple_mail($product_feed_info["product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
#                                return;
#                        }
#                }

                db_query("UPDATE $sql_tbl[supplier_product_feeds] SET last_products_count_in_file='$lines_in_file' WHERE manufacturerid='$product_feed_info[manufacturerid]'");
	}

	if ($file_is_found){

                    $subj = "IMPORT ".$product_feed_info["manufacturer"]." PRODUCT FEED STARTED";
                    $body = $subj;
                    func_send_simple_mail($product_feed_info["product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");


		    $default_product_info = func_select_product($product_feed_info["default_productid"], 0, false, false, false, false, $product_feed_info["storefrontid"]);

//func_print_r($default_product_info);
//die("123");

//func_print_r($product_feed_info, $local_file_full_path);

		    $fp_for_checking_only = func_fopen($local_file_full_path_for_checking_only, 'w', true);

                    $handle = @fopen($local_file_full_path, "r");
                    while (($buffer = fgetcsv($handle, 20480, ",")) !== FALSE) {

			if (empty($buffer) || !is_array($buffer)) {
				continue;
			}

                        $line_number++;

                        if ($line_number % 10 == 0) {
                                func_flush(".");
                                if($line_number % 500 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }

//func_print_r($buffer);
                        if ($line_number > 1) {

				$CATEGORY = trim($buffer[0]);
				$SUBCATEGORY = trim($buffer[1]);
				$BRAND = trim($buffer[3]);
				$MINIMUM_ORDER_QTY = trim($buffer[4]);
				$UNFI_PRODUCT = trim($buffer[5]);
				$STATUS = trim($buffer[7]);
				$QTY_ON_HAND = trim($buffer[8]);
				$PRODUCT_NAME = trim($buffer[9]);
//				$ETAILER_PRICE_AFTER_DISCOUNTS = trim($buffer[11]);
				$ETAILER_PRICE_AFTER_DISCOUNTS = preg_replace("/[^0-9\.]/Ss","", $buffer[11]);
				$SRP = trim($buffer[12]);
				$SHORT_DESCRIPTION = trim($buffer[17]);
				$PRODUCT_FEATURES = trim($buffer[18]);
				$INGREDIENTS = trim($buffer[19]);
				$COLOR = trim($buffer[20]);
				$SCENT = trim($buffer[21]);
				$UPC = trim($buffer[22]);
				$LENGTH = trim($buffer[23]);
				$WIDTH = trim($buffer[24]);
				$HEIGHT = trim($buffer[25]);
				$WEIGHT = trim($buffer[26]);
				$COUNTRY_OF_ORIGIN = trim($buffer[27]);
				$IMAGE_URL = trim($buffer[28]);
				$MAP_POLICY_PRICE = trim($buffer[29]);
				$ORGANIC = trim($buffer[32]);
				$GLUTEN_FREE = trim($buffer[33]);
				$DAIRY_FREE = trim($buffer[34]);
				$YEAST_FREE = trim($buffer[35]);
				$WHEAT_FREE = trim($buffer[36]);
				$VEGAN = trim($buffer[37]);
				$KOSHER = trim($buffer[38]);
				$FAIR_TRADE = trim($buffer[39]);
				$PACK = trim($buffer[40]);
				$SIZE = trim($buffer[41]);

                                $SKU = $product_feed_info["code"] . "-" . strtoupper($UNFI_PRODUCT);


				fputs($fp_for_checking_only, $SKU."\n");


//func_print_r($COLOR, $SCENT, $ORGANIC, "---");

				$cost_to_us = $ETAILER_PRICE_AFTER_DISCOUNTS;
				$price = (1.15 * $cost_to_us + 0.3)/0.97;

//if ($SKU != "HGW-1161157") continue;
//func_print_r($buffer);
//die();

//func_print_r($cost_to_us, $price);

                                $product_info = func_query_first("SELECT productid, forsale, update_search_index, free_ship_zone, eta_date_mm_dd_yyyy, manufacturerid, shipping_freight, low_avail_limit, free_tax, discount_slope, discount_table, discount_avail FROM $sql_tbl[products] WHERE productcode='".addslashes($SKU)."'");

//func_print_r($product_info);
//die();

				$update_product_flag = false;
				$insert_product_flag = false;


                                if (!empty($product_info)){

					if ($product_feed_info["import_new_products"]){
						continue;
					}

					if ($product_feed_info["import_new_and_update_existing_products"]){
					# UPDATE
						$update_product_flag = true;
					        $count_updated_products++;

						$productid = $product_info["productid"];
						$forsale = $product_info["forsale"];
						$eta_date_mm_dd_yyyy = $product_info["eta_date_mm_dd_yyyy"];
						$eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($eta_date_mm_dd_yyyy, "m/d/Y");
						$manufacturerid = $product_info["manufacturerid"];
						$low_avail_limit = $product_info["low_avail_limit"];
						$free_tax = $product_info["free_tax"];
						$shipping_freight = $product_info["shipping_freight"];
						$free_ship_zone = $product_info["free_ship_zone"];
						$free_ship_text = $product_info["free_ship_text"];
						$discount_slope = $product_info["discount_slope"];
						$discount_table = $product_info["discount_table"];
						$discount_avail = $product_info["discount_avail"];

						db_query("UPDATE $sql_tbl[pricing] SET price='".abs($price)."' WHERE productid='$productid' AND quantity='1'");
					}
                                } else {
				# ADD
					$insert_product_flag = true;
        				$count_inserted_products++;

					$forsale = "N";
					
					$time = time();
					db_query("INSERT INTO $sql_tbl[products] (productcode, provider, original_provider, add_date, mod_date, source_sfid) VALUES ('$SKU', 'master', 'master','" . $time . "', '" . $time . "', '$product_feed_info[storefrontid]')");
					$productid = db_insert_id();
					
					db_query("INSERT INTO $sql_tbl[products_sf] (productid, sfid) VALUES ('$productid', '$product_feed_info[storefrontid]')");
					db_query("INSERT INTO $sql_tbl[pricing] (productid, quantity, price) VALUES ('$productid', '1', '".abs($price)."')");

			                $clean_url = func_clean_url_autogenerate('P', $productid, array('product' => $PRODUCT_NAME, 'productcode' => $SKU));
			                $clean_url_save_in_history = false;
			                db_query("DELETE FROM $sql_tbl[clean_urls] WHERE resource_type='P' AND resource_id='$productid'");
			                func_clean_url_add($clean_url, 'P', $productid);
#
## Category
###
                                        $sub_cat_id = "";
                                        $subcats_in_cat = func_query("SELECT categoryid, category FROM $sql_tbl[categories] WHERE parentid='$product_feed_info[default_parent_categoryid]'");
                                        if (!empty($subcats_in_cat)){
	                                        foreach ($subcats_in_cat as $k => $v){
        	                                        if (trim(strtoupper($v["category"])) == strtoupper($CATEGORY)){
                	                                        $sub_cat_id = $v["categoryid"];
                                                                break;
                                                        }
                                                }
					}

                                        if (empty($sub_cat_id)){

//func_print_r("{{{{{{{{{{{{{", "sub_cat:", $sub_cat, "previous_cat:", $previous_cat, "cat:", $cat, "}}}}}}}}}}}}}");
                                                db_query("INSERT INTO $sql_tbl[categories] (parentid, storefrontid, category, is_bold, order_by) VALUES ('$product_feed_info[default_parent_categoryid]', '$product_feed_info[storefrontid]', '".addslashes($CATEGORY)."', 'N', '10')");
                                                $sub_cat_id = db_insert_id();
						
						if (empty($product_feed_info["default_parent_categoryid"])){
							$parent_categoryid_path = $sub_cat_id;
						} else {
	                                                $parent_categoryid_path = func_query_first_cell("SELECT categoryid_path FROM $sql_tbl[categories] WHERE categoryid='$product_feed_info[default_parent_categoryid]'")."/".$sub_cat_id;
						}

                                                func_array2update("categories", array("categoryid_path" => $parent_categoryid_path), "categoryid = '$sub_cat_id'");

//func_print_r($sub_cat, $categoryid, $cat, $parent_categoryid_path);
//die();
                                                // Autogenerate clean URL.
                                                $clean_url = func_clean_url_autogenerate('C', $cat_id, array('category' => $CATEGORY));
                                                $clean_url_save_in_history = false;
                                                db_query("DELETE FROM $sql_tbl[clean_urls] WHERE resource_type='C' AND resource_id='$sub_cat_id'");
                                                func_clean_url_add($clean_url, 'C', $sub_cat_id);
                                        }

                                        $categoryid = "";
                                        $sub_subcats_in_cat = func_query("SELECT categoryid, category FROM $sql_tbl[categories] WHERE parentid='$sub_cat_id'");
                                        if (!empty($sub_subcats_in_cat)){
                                                foreach ($sub_subcats_in_cat as $k => $v){
                                                        if (trim(strtoupper($v["category"])) == strtoupper($SUBCATEGORY)){
                                                                $categoryid = $v["categoryid"];
                                                                break;
                                                        }
                                                }
                                        }

                                        if (empty($categoryid)){

//func_print_r("{{{{{{{{{{{{{", "sub_cat:", $sub_cat, "previous_cat:", $previous_cat, "cat:", $cat, "}}}}}}}}}}}}}");
                                                db_query("INSERT INTO $sql_tbl[categories] (parentid, storefrontid, category, is_bold, order_by) VALUES ('$sub_cat_id', '$product_feed_info[storefrontid]', '".addslashes($SUBCATEGORY)."', 'N', '10')");
                                                $categoryid = db_insert_id();
                                                $parent_categoryid_path = func_query_first_cell("SELECT categoryid_path FROM $sql_tbl[categories] WHERE categoryid='$sub_cat_id'")."/".$categoryid;
                                                func_array2update("categories", array("categoryid_path" => $parent_categoryid_path), "categoryid = '$categoryid'");

//func_print_r($sub_cat, $categoryid, $cat, $parent_categoryid_path);
//die();
                                                // Autogenerate clean URL.
                                                $clean_url = func_clean_url_autogenerate('C', $categoryid, array('category' => $SUBCATEGORY));
                                                $clean_url_save_in_history = false;
                                                db_query("DELETE FROM $sql_tbl[clean_urls] WHERE resource_type='C' AND resource_id='$categoryid'");
                                                func_clean_url_add($clean_url, 'C', $categoryid);
                                        }

                                        db_query("DELETE FROM $sql_tbl[products_categories] WHERE productid='$productid' AND main='Y'");
                                        db_query("INSERT INTO $sql_tbl[products_categories] (categoryid, productid, main) VALUES ('$categoryid', '$productid', 'Y')");
###
##
#
					$eta_date_mm_dd_yyyy = "";
                                        if (strtolower($STATUS) == "available"){
                                                $eta_date_mm_dd_yyyy = "";
                                        } elseif (strtolower($STATUS) == "out of stock"){
                                                $eta = time() + 60*60*24*25;
                                                $eta_date_mm_dd_yyyy = date("m/d/Y", $eta);
                                        }

					$manufacturerid = $product_feed_info["manufacturerid"];

                                        # Update taxes
                                        db_query("DELETE FROM $sql_tbl[product_taxes] WHERE productid='$productid'");

                                        $product_taxes = func_query("SELECT * FROM $sql_tbl[product_taxes] WHERE productid='$default_product_info[productid]'");
                                        if (!empty($product_taxes) && is_array($product_taxes)) {
                                                foreach ($product_taxes as $k=>$v) {
                                                        if (intval($v["taxid"]) > 0) {
                                                                $query_data_t = array(
                                                                        "productid" => $productid,
                                                                        "taxid" => intval($v["taxid"])
                                                                );
                                                                func_array2insert("product_taxes", $query_data_t, true);
                                                        }
                                                }
                                        }

					$low_avail_limit = $default_product_info["low_avail_limit"];
					$free_tax = $default_product_info["free_tax"];
					$shipping_freight = $default_product_info["shipping_freight"];
					$free_ship_zone = $default_product_info["free_ship_zone"];
					$free_ship_text = $default_product_info["free_ship_text"];
					$discount_slope = $default_product_info["discount_slope"];
					$discount_table = $default_product_info["discount_table"];
					$discount_avail = $default_product_info["discount_avail"];
                                }

				if ($update_product_flag || $insert_product_flag){

					$brandid = func_query_first_cell("SELECT brandid FROM $sql_tbl[brands] WHERE brand='".addslashes($BRAND)."'");
					if (empty($brandid)){
						db_query("INSERT INTO $sql_tbl[brands] (brand, orderby) VALUES('".addslashes($BRAND)."', '10')");
						$brandid = db_insert_id();
						db_query("INSERT INTO $sql_tbl[brands_sf] (brandid, sfid) VALUES('$brandid', '$product_feed_info[storefrontid]')");
				                // Autogenerate clean URL.
			                        $clean_url = func_clean_url_autogenerate('M', $brandid, array('brand' => $BRAND));
			                        $clean_url_save_in_history = false;
				                db_query("DELETE FROM $sql_tbl[clean_urls] WHERE resource_type='M' AND resource_id='$brandid'");
			                        func_clean_url_add($clean_url, 'M', $brandid);
					}

					$fulldescr = $SHORT_DESCRIPTION;
					if (!empty($PRODUCT_FEATURES)) $fulldescr .= '<br /><br /> Product Features: <br /><br />'.$PRODUCT_FEATURES;
					if (!empty($INGREDIENTS)) $fulldescr .= '<br /><br /> Ingredients: <br /><br />'.$INGREDIENTS;
					if (!empty($COUNTRY_OF_ORIGIN)) $fulldescr .= '<br /><br /> Country of origin: '.$COUNTRY_OF_ORIGIN;
					if (!empty($SIZE)) $fulldescr .= '<br /><br /> Size: '.$SIZE;
					if (!empty($COLOR)) $fulldescr .= '<br /><br /> Color: '.$COLOR;
					if (!empty($SCENT)) $fulldescr .= '<br /><br /> Scent: '.$SCENT;
					if (!empty($ORGANIC)) $fulldescr .= '<br /><br />'.$ORGANIC;
					if (!empty($GLUTEN_FREE)) $fulldescr .= '<br />'.$GLUTEN_FREE;
					if (!empty($DAIRY_FREE)) $fulldescr .= '<br />'.$DAIRY_FREE;
					if (!empty($YEAST_FREE)) $fulldescr .= '<br />'.$YEAST_FREE;
					if (!empty($WHEAT_FREE)) $fulldescr .= '<br />'.$WHEAT_FREE;
					if (!empty($VEGAN)) $fulldescr .= '<br />'.$VEGAN;
					if (!empty($KOSHER)) $fulldescr .= '<br />'.$KOSHER;
					if (!empty($FAIR_TRADE)) $fulldescr .= '<br />'.$FAIR_TRADE;

					$fulldescr = iconv("cp1252", "utf-8", $fulldescr);

#
##
###
					$product_froogle = $PRODUCT_NAME;
					$strlen_product_froogle = strlen($product_froogle);
					if ($strlen_product_froogle > 70){
						if (strpos($product_froogle, ':') !== false) {

							$product_froogle_arr = explode(":", $product_froogle);
							$str_len_product_froogle_arr_0 = strlen($product_froogle_arr[0]);
							$k_str_len_product_froogle = $str_len_product_froogle_arr_0 / $strlen_product_froogle;

							if ($k_str_len_product_froogle < 0.4){
								unset($product_froogle_arr[0]);
								$product_froogle = implode(":", $product_froogle_arr);
								$product_froogle = trim($product_froogle);
							}

						}
					}
					$product_froogle = addslashes($product_froogle);
###
##
#					

/*
					$update_search_index = "Y";
					if ($forsale == "N" && $product_info["update_search_index"] == "N"){
						$update_search_index = "D";
					}
*/

					$eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($eta_date_mm_dd_yyyy, "seconds");

			                $query_data = array(
			                        "forsale" => $forsale,
			                        "product" => addslashes($PRODUCT_NAME),
						"product_froogle" => $product_froogle,
			                        "eta_date_mm_dd_yyyy" => $eta_date_mm_dd_yyyy,
						"manufacturerid" => $manufacturerid,
						"brandid" => $brandid,
			                        "fulldescr" => addslashes($fulldescr),
						"upc" => addslashes($UPC),
						"list_price" => $SRP,
						"cost_to_us" => $cost_to_us,
						"free_tax" => $free_tax,
						"low_avail_limit" => $low_avail_limit,
						"weight" => $WEIGHT,
						"dim_x" => $LENGTH,
						"dim_y" => $WIDTH,
						"dim_z" => $HEIGHT,
						"shipping_freight" => $shipping_freight,
						"free_ship_zone" => $free_ship_zone,
						"free_ship_text" => $free_ship_text,
						"discount_slope" => $discount_slope,
						"discount_table" => $discount_table,
						"discount_avail" => $discount_avail
//						"update_search_index" => $update_search_index
			                );

                                        if (strtolower($STATUS) == "available"){
                                                $query_data["r_avail"] = $QTY_ON_HAND;
                                        } elseif (strtolower($STATUS) == "out of stock"){
                                                $query_data["r_avail"] = 0;
                                        }

					if ($MAP_POLICY_PRICE == "Y"){
						$query_data["new_map_price"] = $SRP; 
					}

					if (!empty($MINIMUM_ORDER_QTY)){
                                                $query_data["min_amount"] = $MINIMUM_ORDER_QTY;
					} else {
						$query_data["min_amount"] = $default_product_info["min_amount"];
					}

					if ($PACK > 1){
						$query_data["min_amount"] = $PACK;
						$query_data["mult_order_quantity"] = "Y";
					} else {
						$query_data["mult_order_quantity"] = $default_product_info["mult_order_quantity"];
					}

					func_array2update("products", $query_data, "productid = '$productid'");

#
##
###
					if (!empty($COLOR)){
                                                #Color (f_id = 11)
                                                $f_id = 11;
#####
						if (substr($COLOR, -1) == "." || substr($COLOR, -1) == ","){
							$COLOR = substr($COLOR, 0, -1);
						}


                                                $delimetr = "&";
                                                $COLOR = str_replace("/", $delimetr, $COLOR);

                                                $COLOR_arr = array();
                                                $tmp_color_arr =  explode($delimetr, $COLOR);
                                                if (!empty($tmp_color_arr) && is_array($tmp_color_arr)){
                                                        foreach ($tmp_color_arr as $new_color){

                                                                $new_color = trim($new_color);
                                                                if (!empty($new_color)){
                                                                        $COLOR_arr[] = $new_color;
                                                                }
                                                        }
                                                }
/*
						
						$color_explode_arr = array("/", "&");
						$COLOR_arr = array();
						foreach ($color_explode_arr as $delim){
							$tmp_color_arr =  explode($delim, $COLOR);
							if (!empty($tmp_color_arr) && is_array($tmp_color_arr)){
								foreach ($tmp_color_arr as $new_color){
									$COLOR_arr[] = trim($new_color);
								}
							}
						}

						if (!empty($COLOR_arr)){
							foreach ($color_explode_arr as $delim){
								foreach ($COLOR_arr as $k_color => $tmp_color){
									if (strpos($tmp_color, $delim) !== false){
										unset($COLOR_arr[$k_color]);
									}
								}
							}
						}
*/

						if (!empty($COLOR_arr)){
							$COLOR_arr = array_unique($COLOR_arr);
							$COLOR_arr = array_values($COLOR_arr);

							foreach ($COLOR_arr as $COLOR){
								$fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($COLOR)."'");
								if (empty($fv_id)){
									db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($COLOR)."')");
									$fv_id = db_insert_id();
								}

								$fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
								if (empty($fp_id)){
									db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
								}
							}
						}
#####
					}

                                        if (!empty($SCENT)){
                                                #SCENT (f_id = 13)
                                                $f_id = 13;
                                                $fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($SCENT)."'");
                                                if (empty($fv_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($SCENT)."')");
                                                        $fv_id = db_insert_id();
                                                }

                                                $fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
                                                if (empty($fp_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
                                                }
                                        }

                                        if (!empty($ORGANIC)){
                                                #ORGANIC (f_id = 12)
                                                $f_id = 12;
                                                $fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($ORGANIC)."'");
                                                if (empty($fv_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($ORGANIC)."')");
                                                        $fv_id = db_insert_id();
                                                }

                                                $fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
                                                if (empty($fp_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
                                                }
                                        }

                                        if (!empty($GLUTEN_FREE)){
                                                #ORGANIC (f_id = 12) 
                                                $f_id = 12;
                                                $fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($GLUTEN_FREE)."'");
                                                if (empty($fv_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($GLUTEN_FREE)."')");
                                                        $fv_id = db_insert_id();
                                                }

                                                $fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
                                                if (empty($fp_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
                                                }
                                        }

                                        if (!empty($DAIRY_FREE)){
                                                #ORGANIC (f_id = 12) 
                                                $f_id = 12;
                                                $fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($DAIRY_FREE)."'");
                                                if (empty($fv_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($DAIRY_FREE)."')");
                                                        $fv_id = db_insert_id();
                                                }

                                                $fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
                                                if (empty($fp_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
                                                }
                                        }

                                        if (!empty($YEAST_FREE)){
                                                #ORGANIC (f_id = 12) 
                                                $f_id = 12;
                                                $fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($YEAST_FREE)."'");
                                                if (empty($fv_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($YEAST_FREE)."')");
                                                        $fv_id = db_insert_id();
                                                }

                                                $fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
                                                if (empty($fp_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
                                                }
                                        }

                                        if (!empty($WHEAT_FREE)){
                                                #ORGANIC (f_id = 12) 
                                                $f_id = 12;
                                                $fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($WHEAT_FREE)."'");
                                                if (empty($fv_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($WHEAT_FREE)."')");
                                                        $fv_id = db_insert_id();
                                                }

                                                $fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
                                                if (empty($fp_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
                                                }
                                        }

                                        if (!empty($VEGAN)){
                                                #ORGANIC (f_id = 12) 
                                                $f_id = 12;
                                                $fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($VEGAN)."'");
                                                if (empty($fv_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($VEGAN)."')");
                                                        $fv_id = db_insert_id();
                                                }

                                                $fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
                                                if (empty($fp_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
                                                }
                                        }

                                        if (!empty($KOSHER)){
                                                #ORGANIC (f_id = 12) 
                                                $f_id = 12;
                                                $fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($KOSHER)."'");
                                                if (empty($fv_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($KOSHER)."')");
                                                        $fv_id = db_insert_id();
                                                }

                                                $fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
                                                if (empty($fp_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
                                                }
                                        }
###
##
#
					if ($insert_product_flag){
	                                        func_build_quick_flags($productid);
					}

                                        func_build_quick_prices($productid);


                                        if (!empty($IMAGE_URL)){

                                                func_delete_image($productid, "D", false);

                                                $ext = pathinfo($IMAGE_URL, PATHINFO_EXTENSION);
                                                $image_file_name = $SKU."_1.".$ext;
                                                $image_file_path = "./images/D/".$image_file_name;
                                                if (@copy($IMAGE_URL, $image_file_path)){

                                                        $img_info = getimagesize($image_file_path);

                                                        $image_data['id'] = $productid;
                                                        $image_data['date'] = time();
                                                        $image_data['image_path'] = $image_file_path;
                                                        $image_data['image_type'] = $img_info["mime"];
                                                        $image_data['image_x'] = $img_info[0];
                                                        $image_data['image_y'] = $img_info[1];
                                                        $image_data['image_size'] = filesize($image_file_path);
                                                        $image_data['alt'] = addslashes($PRODUCT_NAME);
                                                        $image_data['avail'] = 'Y';
                                                        $image_data['orderby'] = '10';

//                                                      $image = func_file_get($image_file_path, true);
//                                                      $image_data['md5'] = md5($image);

                                                        $image_id = func_array2insert('images_D', $image_data);

                                                        $image_width_x_height = $image_data['image_x'] * $image_data['image_y'];
                                                        if ($image_width_x_height > 0 && $image_width_x_height < 4500000) {
//                                                                func_delete_image($productid, "T", true);
                                                                func_delete_image($productid, "T",false);

                                                                if (func_generate_image($productid, 'D', 'T', false, false, $image_id)) {
                                                                        func_save_product_thumb_image($productid, 'T');
                                                                }

//                                                                func_delete_image($productid, "P", true);
                                                                func_delete_image($productid, "P", false);
                                                                if (func_generate_image($productid, 'D', 'P', false, false, $image_id)) {
                                                                        func_save_product_thumb_image($productid, 'P');
                                                                }
                                                        }
                                                }
                                        }

				}

//die($SKU);
                        } else {
//                                $NEW_PRODUCTS_header = implode(", ", $buffer);
                        }

                    }
                    fclose($handle);
		    fclose($fp_for_checking_only);

		    $last_imported_updated_products_count = $count_inserted_products."/".$count_updated_products;
                    db_query("UPDATE $sql_tbl[supplier_product_feeds] SET last_import_date='".$launch_time."', last_imported_updated_products_count='$last_imported_updated_products_count' WHERE manufacturerid='$product_feed_info[manufacturerid]'");

                    $subj = "IMPORT ".$product_feed_info["manufacturer"]." PRODUCT FEED FINISHED";
                    $body = "IMPORT ".$product_feed_info["manufacturer"]." PRODUCT FEED FINISHED";
                    $body .= "Products imported/updated: ".$last_imported_updated_products_count."\n";
                    $function_time = time() - $function_launch_time;
                    $function_time = $function_time/(60);
                    $function_time = round($function_time,1);
                    $body .= "Duration: ".$function_time." Mins\n";

		    func_backprocess_log("supplier feeds", $body);
//                    func_send_simple_mail($product_feed_info["product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
	}
}

function func_GENERAL_HGW_INVENTORY_FEED($manufacturerid){
        global $sql_tbl, $xcart_dir, $launch_time, $function_launch_time;

        $file_is_found_and_uploaded = false;
        $file_is_found = false;
        $count_updated_products = 0;
        $count_marked_as_out_of_stock_products = 0;
        $count_marked_as_in_stock_products = 0;

        if (function_exists("ftp_connect")) {

                $general_info = func_query_first("SELECT manufacturer, code, d_ftp_host, d_ftp_login, d_ftp_password, d_ftp_folder, d_product_management_team_email, d_last_feed_rows_processed, d_validation_threshold FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");

                $manufacturer_code = trim($general_info["code"]);

                $ftp = ftp_connect($general_info["d_ftp_host"]);
                if ($ftp && @ftp_login($ftp, $general_info["d_ftp_login"], $general_info["d_ftp_password"])) {

                        ftp_pasv($ftp, true);

                        $contents = ftp_nlist($ftp, $general_info["d_ftp_folder"]);

                        if (!empty($contents) && is_array($contents)){
                                $all_files = array();
                                foreach ($contents as $k => $v){
                                        if (strpos($v, 'phi_onhand_') !== false && strpos($v, '.csv') !== false ){

                                                $tmp_file_arr  = explode(".csv", $v);
                                                $tmp_date_arr = explode("phi_onhand_", $tmp_file_arr[0]);
                                                $tmp_date = $tmp_date_arr[1];

                                                if (!empty($tmp_date)){
                                                        $year = substr($tmp_date, 0, 4);
                                                        $month = substr($tmp_date, 4, 2);
                                                        $day = substr($tmp_date, 6, 2);

                                                        $tmp_mktime = mktime(0, 0, 0, $month, $day, $year);
                                                        $all_files[$k]["file"] = $v;
                                                        $all_files[$k]["time"] = $tmp_mktime;
                                                }
                                        }
                                }

                                if (!empty($all_files)){
                                        $all_files = my_array_sort($all_files, "time", SORT_DESC);
                                        $all_files = array_values($all_files);

                                        $server_file_name = $all_files[0]["file"];
                                        $server_file_name_full_path = $general_info["d_ftp_folder"] . $server_file_name;

                                        $local_file_name = $manufacturerid."_".$server_file_name;
                                        $local_file_name_full_path = $xcart_dir . "/files/product_feeds/" . $local_file_name;
                                }
                        }

                        if (!empty($local_file_name_full_path) && !empty($server_file_name_full_path)){
                                if (@ftp_get($ftp, $local_file_name_full_path, $server_file_name_full_path, FTP_BINARY)) {
                                        $file_is_found = true;
                                }
                        }

                        ftp_quit($ftp);

                } else {
                        print("Could not open host. (Distributor: ".$general_info["manufacturer"] .")<br />");
                }
        }

        if ($file_is_found){

                $handle = @fopen($local_file_name_full_path, "r");
                if ($handle) {

                    # check for lines
//                  $lines_in_file = count(file($local_file_name_full_path));
###
                    $lines_in_file = 0;
                    while (($buffer = fgetcsv($handle, 20480, ",")) !== FALSE) {
                        $lines_in_file++;
                        if ($lines_in_file % 100 == 0) {
                                func_flush(".");
                                if($lines_in_file % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }
                    }
                    fclose($handle);
###

                    if ($general_info["d_last_feed_rows_processed"] > 0){
                                $lines_in_file_DIV_last_feed_rows_processed = $lines_in_file/$general_info["d_last_feed_rows_processed"];

                                if ($lines_in_file_DIV_last_feed_rows_processed < $general_info["d_validation_threshold"]){
                                        $subj = "SUPPLIER INVENTORY FEED validation failed!!!";
                                        $body = $general_info["manufacturer"] . ". Count lines in new file: ".$lines_in_file."\n";
                                        $body .= "Last feed rows processed: ".$general_info["d_last_feed_rows_processed"]."\n";
                                        $body .= "processing stopped";
                                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                                        fclose($handle);
                                        return;
                                }
                    }
                    db_query("UPDATE $sql_tbl[manufacturers] SET d_last_feed_rows_processed='$lines_in_file' WHERE manufacturerid='$manufacturerid'");
                    #


                    $line_number = 0;
                    $NEW_PRODUCTS = array();
                    $discontinued_products = array();
                    $all_feed_productcodes = array();

                    print "<br />".$general_info["manufacturer"]."<br />";
                    print "First iteration:<br />";

                    $handle = @fopen($local_file_name_full_path, "r");
                    while (($buffer = fgetcsv($handle, 10240, ",")) !== FALSE) {
                        $line_number++;

                        if ($line_number % 100 == 0) {
                                func_flush(".");
                                if($line_number % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }

                        if ($line_number > 1) {

                                $SKU = trim($buffer[0]);
				$SKU = str_replace('"','',$SKU);
				$SKU = str_replace('=','',$SKU);
                                $SKU = strtoupper($SKU);
                                $AVAIL = trim($buffer[7]);
//                                $FEED_ETA = trim($buffer[4]);
                                $FEED_STATUS = trim($buffer[8]);
                                $FEED_STATUS = strtolower($FEED_STATUS);

                                if ($AVAIL == ""){
                                        $AVAIL = 0;
                                }

                                $feed_productcode = $manufacturer_code . "-" . $SKU;
                                $all_feed_productcodes[] = $feed_productcode;

                                $product_info_arr = func_query_first("SELECT productid, productcode, update_search_index, forsale, avail, r_avail, eta_date_mm_dd_yyyy FROM $sql_tbl[products] WHERE productcode='".addslashes($feed_productcode)."'");

                                if (!empty($product_info_arr)){

                                        $productcode = $product_info_arr["productcode"];
                                        $productid = $product_info_arr["productid"];

                                        $current_forsale = $product_info_arr["forsale"];
                                        $current_avail = $product_info_arr["r_avail"];
                                        $current_eta_date_mm_dd_yyyy = $product_info_arr["eta_date_mm_dd_yyyy"];
					$current_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($current_eta_date_mm_dd_yyyy, "m/d/Y");

                                        $new_forsale = $current_forsale;
                                        $new_avail = $current_avail;
                                        $new_eta_date_mm_dd_yyyy = $current_eta_date_mm_dd_yyyy;
                                        $new_eta_date_mm_dd_yyyy_time = "";

                                        $product_is_updated = false;
                                        $marked_as_out_of_stock_products = false;
                                        $marked_as_in_stock_products = false;

                                        if ($FEED_STATUS != "active"){
                                                $new_forsale = "Y";
                                                $new_avail = 0;
                                                $new_eta_date_mm_dd_yyyy_time = $current_time + 60*60*24*35;
                                        } elseif ($FEED_STATUS == "active") {
                                                $new_forsale = "Y";
						$new_avail = $AVAIL;
						$new_eta_date_mm_dd_yyyy = "";
                                        }

                                        if (!empty($new_eta_date_mm_dd_yyyy_time)){
                                                $new_eta_date_mm_dd_yyyy = date("m/d/Y", $new_eta_date_mm_dd_yyyy_time);
                                        }

                                        if ($new_avail == "0"){
                                                if ($current_avail > 0){
                                                        $marked_as_out_of_stock_products = true;
                                                }
                                        } else {
                                                if ($current_avail == 0){
                                                        $marked_as_in_stock_products = true;
                                                }
                                        }

                                        if ($new_forsale != $current_forsale || $new_eta_date_mm_dd_yyyy != $current_eta_date_mm_dd_yyyy || $current_avail != $new_avail){

/*
                                                        $update_search_index = 'Y';
                                                        if ($new_forsale == 'N' && $product_info_arr["update_search_index"] == "N"){
                                                                $update_search_index = 'D';
                                                        }
*/
//                                                        db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale', update_search_index='$update_search_index' WHERE productid='$productid'");
							$new_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($new_eta_date_mm_dd_yyyy, "seconds");
                                                        db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale' WHERE productid='$productid'");
                                                        $product_is_updated = true;
                                        }

                                        if ($product_is_updated){
                                                $file_is_found_and_uploaded = true;
                                                $count_updated_products++;
                                        }

                                        if ($marked_as_out_of_stock_products){
                                                $count_marked_as_out_of_stock_products++;
                                        }

                                        if ($marked_as_in_stock_products){
                                                $count_marked_as_in_stock_products++;
                                        }

                                } else {
                                        $NEW_PRODUCTS[] = implode(", ", $buffer);
                                }
                        } else {
                                $NEW_PRODUCTS_header = implode(", ", $buffer);
                        }

                    }
                    fclose($handle);

                    if (!empty($all_feed_productcodes) && is_array($all_feed_productcodes)){

                        $count_products = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[products] WHERE productcode LIKE '".$manufacturer_code."-%' AND forsale='Y'");

                        if ($count_products > 0){
                                $manufacturer_code_products = db_query("SELECT productid, productcode, forsale, update_search_index FROM $sql_tbl[products] WHERE productcode LIKE '".$manufacturer_code."-%' AND forsale='Y'");

                                $line_number = 0;
                                print "<br />Second iteration:<br />";
                                while ($product = db_fetch_array($manufacturer_code_products)) {

                                        $line_number++;
                                        if ($line_number % 100 == 0) {
                                                func_flush(".");
                                                if($line_number % 5000 == 0) {
                                                        func_flush("<br />\n");
                                                }

                                                func_flush();
                                        }

                                        $productcode = strtoupper(trim($product["productcode"]));

                                        if (!in_array($productcode, $all_feed_productcodes) && $product["forsale"] != "N") {
                                                $file_is_found_and_uploaded = true;
                                                $discontinued_products[] = $product;
/*
                                                $update_search_index = $product["update_search_index"];
                                                if ($update_search_index == "N"){
                                                        $update_search_index = "D";
                                                }
*/
//                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N', update_search_index='$update_search_index' WHERE productid='".$product["productid"]."'");
                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N' WHERE productid='".$product["productid"]."'");
                                        }
                                }
                        }
                    }

                    db_query("UPDATE $sql_tbl[manufacturers] SET d_most_recent_feed_updation_date='".$launch_time."' WHERE manufacturerid='$manufacturerid'");


                    $count_discontinued_products = count($discontinued_products);
                    if (!empty($discontinued_products) && is_array($discontinued_products)){

                        $subj = $general_info["manufacturer"]." INVENTORY FEED UPDATE - discontinued products";
                        $body = "Discontinued products count: ".$count_discontinued_products."\n\n";

                        $body .= "<table border='1'>\n<tr><td>ProductCode</td><td>Link to SF backend</td></tr>\n";
                        foreach ($discontinued_products as $k => $v){
                                $store_url = "www.artistsupplysource.com";
                                $body .= "<tr><td> ".$v["productcode"]." </td><td> <a href='http://".$store_url."/admin/product_modify.php?productid=".$v["productid"]."'>http://".$store_url."/admin/product.php?productid=".$v["productid"]."</a> </td></tr>\n";
                        }
                        $body .= "</table>";

                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                    }

                    $count_NEW_PRODUCTS = count($NEW_PRODUCTS);
                    if (!empty($NEW_PRODUCTS) && is_array($NEW_PRODUCTS)){
                        $subj = $general_info["manufacturer"]." INVENTORY FEED UPDATE - new products";
                        $body = "Expected new products count: ".$count_NEW_PRODUCTS."\n\n";

                        $body .= $NEW_PRODUCTS_header."\n";
                        foreach ($NEW_PRODUCTS as $k => $v){
                                $body .= $v."\n";
                        }

                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                    }

                    $count_all_feed_productcodes = count($all_feed_productcodes);
                    $sum_updated_products = $count_marked_as_out_of_stock_products + $count_marked_as_in_stock_products + $count_discontinued_products;

                    $subj = $general_info["manufacturer"]." INVENTORY FEED UPDATE - summary";
                    $body = $general_info["manufacturer"]." INVENTORY FEED UPDATE - summary";
                    $body .= "products in storefront: ".$count_products."\n";
                    $body .= "products in feed: ".$count_all_feed_productcodes."\n";
                    $body .= "updated products: ".$sum_updated_products."\n";
                    $body .= "marked as `out of stock` products: ".$count_marked_as_out_of_stock_products."\n";
                    $body .= "marked as `in stock` products: ".$count_marked_as_in_stock_products."\n";
                    $body .= "new products: ".$count_NEW_PRODUCTS."\n";
                    $body .= "discontinued products: ".$count_discontinued_products."\n";
                    $function_time = time() - $function_launch_time;
                    $function_time = $function_time/(60);
                    $function_time = round($function_time,1);
                    $body .= "Duration: ".$function_time." Mins\n";

		    func_backprocess_log("supplier feeds", $body);
//                    func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

                } // if ($handle)
                else {
                        fclose($handle);

                        $subj = "SUPPLIER INVENTORY FEED validation failed!!!";
                        $body = $general_info["manufacturer"] . ". File cannot be opened.";
                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                        return;
                }
        } // if ($file_is_found)
        else {
//                $subj = "SUPPLIER FEED validation failed!!!";
//                $body = $general_info["manufacturer"] . ". File cannot be found.";
//                func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
//              return;
        }
}



function func_BUYSEASONS_GENERAL_PRODUCT_FEED($product_feed_info){
 global $sql_tbl, $xcart_dir, $launch_time, $function_launch_time;

 $default_product_info = func_select_product($product_feed_info["default_productid"], 0, false, false, false, false, $product_feed_info["storefrontid"]);

 $current_time = time();
// $dir_path_to_file_on_server = $product_feed_info["ftp_folder"];
// $dir_path_to_file_on_server = $product_feed_info["ftp_host"].$product_feed_info["ftp_folder"];
 $dir_path_to_file_on_server = "https://www.buyseasonsdirect.com/resources/docs/";
 $dir_path_to_local_file = $xcart_dir . "/files/main_product_feeds/";
 $any_file_is_found = false;
 $product_feed_started_latter_send = false;

 $import_info[0]["file"] = "CatalogFeed";
 $import_info[1]["file"] = "PartySupplies";

 for ($i=0;$i<=1;$i++){

//  if (function_exists("ftp_connect")) {
//    $ftp = ftp_connect($product_feed_info["ftp_host"]);
//    if ($ftp && @ftp_login($ftp, $product_feed_info["ftp_login"], $product_feed_info["ftp_password"])) {
//        ftp_pasv($ftp, true);

        $count_updated_products = 0;
        $count_inserted_products = 0;

        $file_is_found = false;

	$file_name_prefix = $import_info[$i]["file"];

 	    for ($j=0;$j<=10;$j++){

		$file_time = $current_time - ($j*60*60*24);
		$date_prefix = date("Ymd", $file_time);

		$productfeed_filename_zip = $file_name_prefix . $date_prefix . ".zip";
		$productfeed_filename_psv = $file_name_prefix . $date_prefix . ".psv";
		$local_file_name_zip = $product_feed_info["manufacturerid"]."_".$productfeed_filename_zip;
		$local_file_name_psv = $product_feed_info["manufacturerid"]."_".$productfeed_filename_psv;
		$local_file_full_path_zip = $dir_path_to_local_file .$local_file_name_zip;
		$local_file_full_path_psv = $dir_path_to_local_file .$local_file_name_psv;

		$server_file_full_path_zip = $dir_path_to_file_on_server.$productfeed_filename_zip;
		$server_file_full_path_psv = $dir_path_to_file_on_server.$productfeed_filename_psv;

//                if (@ftp_get($ftp, $local_file_full_path_psv, $server_file_full_path_psv, FTP_BINARY)) {
//	                $file_is_found = true;
//			$any_file_is_found = true;
//			$import_info[$i]["local_file_full_path_psv"] = $local_file_full_path_psv;
//			break;
//                }

	        if(@copy($server_file_full_path_zip, $local_file_full_path_zip)){
			$zip = new ZipArchive;
			$res = $zip->open($local_file_full_path_zip);

			if ($res === TRUE) {
				  // extract it to the path we determined above
				$zip->extractTo($dir_path_to_local_file);
				$zip->close();

				$extracted_file_full_path = $dir_path_to_local_file.$productfeed_filename_psv;

				if (@copy($extracted_file_full_path, $local_file_full_path_psv)){
					unlink($local_file_full_path_zip);
					unlink($extracted_file_full_path);
		                        $file_is_found = true;
        		                $any_file_is_found = true;
					$import_info[$i]["local_file_full_path_psv"] = $local_file_full_path_psv;
                		        break;
				}
			}
		}
        }

	if ($file_is_found){
		$import_info[$i]["file_is_found"] = "Y";

		if (!$product_feed_started_latter_send){
			$subj = "IMPORT ".$product_feed_info["manufacturer"]." PRODUCT FEED STARTED";
			$body = $subj;
			func_send_simple_mail($product_feed_info["product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
			$product_feed_started_latter_send = true;
		}

		func_flush(".");
                $line_number = 0;
                $handle = @fopen($local_file_full_path_psv, "r");
                while (($buffer = fgetcsv($handle, 20480, "|")) !== FALSE) {
                        if (empty($buffer)) continue;

                        $line_number++;
                        if ($line_number % 10 == 0) {
                                func_flush(".");
                                if($line_number % 500 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }

//func_print_r($buffer);
                        if ($line_number > 1) {
//die();

				$SKU = trim($buffer[0]);
				$Prod_ID = trim($buffer[1]);
				$Name = trim($buffer[2]);
				$Generic_Desc = trim($buffer[3]);
				$Weight = trim($buffer[4]);
				$Length = trim($buffer[5]);
				$Width = trim($buffer[6]);
				$Height = trim($buffer[7]);
				$Category = trim($buffer[8]);
				$Size_Chart_Reference = trim($buffer[9]);
				$Manufacturer = trim($buffer[10]);
//				$Wholesale_Price = trim($buffer[11]);
				$Wholesale_Price = preg_replace("/[^0-9\.]/Ss","", $buffer[11]);
				$Color = trim($buffer[12]);
				$Display_Size = trim($buffer[13]);
				$Material = trim($buffer[14]);
				$Country_of_Origin = trim($buffer[15]);
				$Care_Instruction = trim($buffer[16]);
				$Status = trim($buffer[17]);
				$Category2 = trim($buffer[18]);
				$Celebration = trim($buffer[19]);
				$Accessory1 = trim($buffer[20]);
				$Accessory2 = trim($buffer[21]);
				$Accessory3 = trim($buffer[22]);
				$CrossSell1 = trim($buffer[23]);
				$CrossSell2 = trim($buffer[24]);
				$CrossSell3 = trim($buffer[25]);
				$CrossSell4 = trim($buffer[26]);
				$UPC = trim($buffer[27]);
				$Created = trim($buffer[28]);
				$Shipping_surchage = trim($buffer[29]);

				if ($i == "0"){
					$Gender = trim($buffer[30]);
					$Age_Group = trim($buffer[31]);
					$Variant_Name = trim($buffer[32]);
				} elseif ($i == "1"){
					$Variant_Name = trim($buffer[30]);
				}

				$SKU = $product_feed_info["code"] . "-" . strtoupper($SKU);

#
##
###
//if ($SKU != "BSE-146594")
//	continue;
###
##
#
				if (!(strtolower($Status) == "active" || strtolower($Status) == "activeoos")) continue;

                                $cost_to_us = $Wholesale_Price;
                                $price = (1.15 * $cost_to_us + 0.3)/0.97;

				$product_info = func_query_first("SELECT productid, forsale, free_ship_zone, update_search_index, eta_date_mm_dd_yyyy, manufacturerid, shipping_freight, low_avail_limit, free_tax, discount_slope, discount_table, discount_avail, avail, r_avail FROM $sql_tbl[products] WHERE productcode='".addslashes($SKU)."'");

                                $update_product_flag = false;
                                $insert_product_flag = false;

                                if (!empty($product_info)){

                                        if ($product_feed_info["import_new_products"]){
                                                continue;
                                        }

                                        if ($product_feed_info["import_new_and_update_existing_products"]){
                                        # UPDATE
                                                $update_product_flag = true;
                                                $count_updated_products++;
						$import_info[$i]["count_updated_products"] = $count_updated_products;

                                                $productid = $product_info["productid"];
                                                $forsale = $product_info["forsale"];
                                                $eta_date_mm_dd_yyyy = $product_info["eta_date_mm_dd_yyyy"];
						$eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($eta_date_mm_dd_yyyy, "seconds");
                                                $manufacturerid = $product_info["manufacturerid"];
                                                $low_avail_limit = $product_info["low_avail_limit"];
                                                $free_tax = $product_info["free_tax"];
                                                $shipping_freight = $product_info["shipping_freight"];
                                                $free_ship_zone = $product_info["free_ship_zone"];
                                                $free_ship_text = $product_info["free_ship_text"];
                                                $discount_slope = $product_info["discount_slope"];
                                                $discount_table = $product_info["discount_table"];
                                                $discount_avail = $product_info["discount_avail"];
                                                $avail = $product_info["r_avail"];

                                                db_query("UPDATE $sql_tbl[pricing] SET price='".abs($price)."' WHERE productid='$productid' AND quantity='1'");
                                        }
                                } else {
                                # ADD
                                        $insert_product_flag = true;
                                        $count_inserted_products++;
					$import_info[$i]["count_inserted_products"] = $count_inserted_products;



                                        $forsale = "N"; // N <--------------------------




                                        $time = time();
                                        db_query("INSERT INTO $sql_tbl[products] (productcode, provider, original_provider, add_date, mod_date, source_sfid) VALUES ('$SKU', 'master', 'master','" . $time . "', '" . $time . "', '$product_feed_info[storefrontid]')");
                                        $productid = db_insert_id();

                                        db_query("INSERT INTO $sql_tbl[products_sf] (productid, sfid) VALUES ('$productid', '$product_feed_info[storefrontid]')");
                                        db_query("INSERT INTO $sql_tbl[pricing] (productid, quantity, price) VALUES ('$productid', '1', '".abs($price)."')");

                                        $clean_url = func_clean_url_autogenerate('P', $productid, array('product' => $Variant_Name, 'productcode' => $SKU));
                                        $clean_url_save_in_history = false;
                                        db_query("DELETE FROM $sql_tbl[clean_urls] WHERE resource_type='P' AND resource_id='$productid'");
                                        func_clean_url_add($clean_url, 'P', $productid);
#
## Category
###
                                        $sub_cat_id = "";
                                        $subcats_in_cat = func_query("SELECT categoryid, category FROM $sql_tbl[categories] WHERE parentid='$product_feed_info[default_parent_categoryid]'");
                                        if (!empty($subcats_in_cat)){
                                                foreach ($subcats_in_cat as $k => $v){
                                                        if (trim(strtoupper($v["category"])) == strtoupper($Category)){
                                                                $sub_cat_id = $v["categoryid"];
                                                                break;
                                                        }
                                                }
                                        }

                                        if (empty($sub_cat_id)){

//func_print_r("{{{{{{{{{{{{{", "sub_cat:", $sub_cat, "previous_cat:", $previous_cat, "cat:", $cat, "}}}}}}}}}}}}}");
                                                db_query("INSERT INTO $sql_tbl[categories] (parentid, storefrontid, category, is_bold, order_by) VALUES ('$product_feed_info[default_parent_categoryid]', '$product_feed_info[storefrontid]', '".addslashes($Category)."', 'N', '10')");
                                                $sub_cat_id = db_insert_id();

                                                if (empty($product_feed_info["default_parent_categoryid"])){
                                                        $parent_categoryid_path = $sub_cat_id;
                                                } else {
                                                        $parent_categoryid_path = func_query_first_cell("SELECT categoryid_path FROM $sql_tbl[categories] WHERE categoryid='$product_feed_info[default_parent_categoryid]'")."/".$sub_cat_id;
                                                }

                                                func_array2update("categories", array("categoryid_path" => $parent_categoryid_path), "categoryid = '$sub_cat_id'");

//func_print_r($sub_cat, $categoryid, $cat, $parent_categoryid_path);
//die();
                                                // Autogenerate clean URL.
                                                $clean_url = func_clean_url_autogenerate('C', $cat_id, array('category' => $Category));
                                                $clean_url_save_in_history = false;
                                                db_query("DELETE FROM $sql_tbl[clean_urls] WHERE resource_type='C' AND resource_id='$sub_cat_id'");
                                                func_clean_url_add($clean_url, 'C', $sub_cat_id);
                                        }

					$Category2 = ""; ###

					if (!empty($Category2)){

	                                        $categoryid = "";
        	                                $sub_subcats_in_cat = func_query("SELECT categoryid, category FROM $sql_tbl[categories] WHERE parentid='$sub_cat_id'");
                	                        if (!empty($sub_subcats_in_cat)){
                        	                        foreach ($sub_subcats_in_cat as $k => $v){
                                	                        if (trim(strtoupper($v["category"])) == strtoupper($Category2)){
                                        	                        $categoryid = $v["categoryid"];
                                                	                break;
                                                        	}
	                                                }
        	                                }

                	                        if (empty($categoryid)){

//func_print_r("{{{{{{{{{{{{{", "sub_cat:", $sub_cat, "previous_cat:", $previous_cat, "cat:", $cat, "}}}}}}}}}}}}}");
                        	                        db_query("INSERT INTO $sql_tbl[categories] (parentid, storefrontid, category, is_bold, order_by) VALUES ('$sub_cat_id', '$product_feed_info[storefrontid]', '".addslashes($Category2)."', 'N', '10')");
                                	                $categoryid = db_insert_id();
                                        	        $parent_categoryid_path = func_query_first_cell("SELECT categoryid_path FROM $sql_tbl[categories] WHERE categoryid='$sub_cat_id'")."/".$categoryid;
                                                	func_array2update("categories", array("categoryid_path" => $parent_categoryid_path), "categoryid = '$categoryid'");

//func_print_r($sub_cat, $categoryid, $cat, $parent_categoryid_path);
//die();
	                                                // Autogenerate clean URL.
        	                                        $clean_url = func_clean_url_autogenerate('C', $categoryid, array('category' => $Category2));
                	                                $clean_url_save_in_history = false;
                        	                        db_query("DELETE FROM $sql_tbl[clean_urls] WHERE resource_type='C' AND resource_id='$categoryid'");
                                	                func_clean_url_add($clean_url, 'C', $categoryid);
                                        	}
					} else {
						$categoryid = $sub_cat_id;
					}

                                        db_query("DELETE FROM $sql_tbl[products_categories] WHERE productid='$productid' AND main='Y'");
                                        db_query("INSERT INTO $sql_tbl[products_categories] (categoryid, productid, main) VALUES ('$categoryid', '$productid', 'Y')");
###
##
#
                                        $eta_date_mm_dd_yyyy = "";

                                        $manufacturerid = $product_feed_info["manufacturerid"];

                                        # Update taxes
                                        db_query("DELETE FROM $sql_tbl[product_taxes] WHERE productid='$productid'");

                                        $product_taxes = func_query("SELECT * FROM $sql_tbl[product_taxes] WHERE productid='$default_product_info[productid]'");
                                        if (!empty($product_taxes) && is_array($product_taxes)) {
                                                foreach ($product_taxes as $k=>$v) {
                                                        if (intval($v["taxid"]) > 0) {
                                                                $query_data_t = array(
                                                                        "productid" => $productid,
                                                                        "taxid" => intval($v["taxid"])
                                                                );
                                                                func_array2insert("product_taxes", $query_data_t, true);
                                                        }
                                                }
                                        }

                                        $low_avail_limit = $default_product_info["low_avail_limit"];
                                        $free_tax = $default_product_info["free_tax"];

//                                        $shipping_freight = $default_product_info["shipping_freight"];
                                        $shipping_freight = $Shipping_surchage;

                                        $free_ship_zone = $default_product_info["free_ship_zone"];
                                        $free_ship_text = $default_product_info["free_ship_text"];
                                        $discount_slope = $default_product_info["discount_slope"];
                                        $discount_table = $default_product_info["discount_table"];
                                        $discount_avail = $default_product_info["discount_avail"];
                                        $avail = $default_product_info["r_avail"];
                                }



                                if ($update_product_flag || $insert_product_flag){

                                        $brandid = func_query_first_cell("SELECT brandid FROM $sql_tbl[brands] WHERE brand='".addslashes($Manufacturer)."'");
                                        if (empty($brandid)){
                                                db_query("INSERT INTO $sql_tbl[brands] (brand, orderby) VALUES('".addslashes($Manufacturer)."', '10')");
                                                $brandid = db_insert_id();
                                                db_query("INSERT INTO $sql_tbl[brands_sf] (brandid, sfid) VALUES('$brandid', '$product_feed_info[storefrontid]')");
                                                // Autogenerate clean URL.
                                                $clean_url = func_clean_url_autogenerate('M', $brandid, array('brand' => $Manufacturer));
                                                $clean_url_save_in_history = false;
                                                db_query("DELETE FROM $sql_tbl[clean_urls] WHERE resource_type='M' AND resource_id='$brandid'");
                                                func_clean_url_add($clean_url, 'M', $brandid);
                                        }

                                        $fulldescr = $Generic_Desc;
					if (empty($fulldescr)) $fulldescr = "no detailed description available <br/>";
                                        if (!empty($Color)) $fulldescr .= '<br /><br /> Color: '.$Color;
                                        if (!empty($Display_Size)) $fulldescr .= '<br /><br /> Display Size: '.$Display_Size;
                                        if (!empty($Material)) $fulldescr .= '<br /><br /> Material: '.$Material;
                                        if (!empty($Country_of_Origin)) $fulldescr .= '<br /><br /> Country of Origin: '.$Country_of_Origin;
                                        if (!empty($Care_Instruction)) $fulldescr .= '<br /><br /> Care Instruction: '.$Care_Instruction;
                                        if (!empty($Celebration)) $fulldescr .= '<br /><br /> Celebration: '.$Celebration;
                                        if (!empty($Gender)) $fulldescr .= '<br /><br /> Gender: '.$Gender;
                                        if (!empty($Age_Group)) $fulldescr .= '<br /><br /> Age Group: '.$Age_Group;

                                        $fulldescr = iconv("cp1252", "utf-8", $fulldescr);


#
##
###
                                        $product_froogle = $Variant_Name;
                                        $strlen_product_froogle = strlen($product_froogle);
                                        if ($strlen_product_froogle > 70){
                                                if (strpos($product_froogle, ':') !== false) {

                                                        $product_froogle_arr = explode(":", $product_froogle);
                                                        $str_len_product_froogle_arr_0 = strlen($product_froogle_arr[0]);
                                                        $k_str_len_product_froogle = $str_len_product_froogle_arr_0 / $strlen_product_froogle;

                                                        if ($k_str_len_product_froogle < 0.4){
                                                                unset($product_froogle_arr[0]);
                                                                $product_froogle = implode(":", $product_froogle_arr);
                                                                $product_froogle = trim($product_froogle);
                                                        }

                                                }
                                        }
                                        $product_froogle = addslashes($product_froogle);
###
##
#                                       
/*
                                        $update_search_index = "Y";
                                        if ($forsale == "N" && $product_info["update_search_index"] == "N"){
                                                $update_search_index = "D";
                                        }
*/

					$eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($eta_date_mm_dd_yyyy, "seconds");

                                        $query_data = array(
                                                "forsale" => $forsale,
                                                "product" => addslashes($Variant_Name),
						"product_froogle" => $product_froogle,
                                                "eta_date_mm_dd_yyyy" => $eta_date_mm_dd_yyyy,
                                                "manufacturerid" => $manufacturerid,
                                                "brandid" => $brandid,
                                                "fulldescr" => addslashes($fulldescr),
                                                "upc" => addslashes($UPC),
//                                                "list_price" => $SRP,
                                                "cost_to_us" => $cost_to_us,
                                                "free_tax" => $free_tax,
                                                "low_avail_limit" => $low_avail_limit,
                                                "weight" => $Weight,
                                                "dim_x" => $Length,
                                                "dim_y" => $Width,
                                                "dim_z" => $Height,
                                                "shipping_freight" => $shipping_freight,
                                                "free_ship_zone" => $free_ship_zone,
                                                "free_ship_text" => $free_ship_text,
                                                "discount_slope" => $discount_slope,
                                                "discount_table" => $discount_table,
                                                "discount_avail" => $discount_avail,
						"supplier_internal_product_id" => $Prod_ID,
						"min_amount" => $default_product_info["min_amount"],
						"mult_order_quantity" => $default_product_info["mult_order_quantity"],
						"r_avail" => $avail,
						"generate_similar_products" => "N"
//						"update_search_index" => $update_search_index
                                        );

                                        func_array2update("products", $query_data, "productid = '$productid'");

#
##
###

//$Color = "red, green/blue & white, red1, green1/blue1 & white1 & red2 green2/blue2 & white2";

                                        if (!empty($Color)){
                                                #Color (f_id = 14)
                                                $f_id = 14;

#####
						$COLOR = $Color;
                                                if (substr($COLOR, -1) == "." || substr($COLOR, -1) == ","){
                                                        $COLOR = substr($COLOR, 0, -1);
                                                }

						$delimetr = ",";
						$COLOR = str_replace("/", $delimetr, $COLOR);
						$COLOR = str_replace("&", $delimetr, $COLOR);

                                                $COLOR_arr = array();
                                                $tmp_color_arr =  explode($delimetr, $COLOR);
                                                if (!empty($tmp_color_arr) && is_array($tmp_color_arr)){
	                                                foreach ($tmp_color_arr as $new_color){
        	                                                $new_color = trim($new_color);
								if (!empty($new_color)){
	        	                                                $COLOR_arr[] = $new_color;
								}
                                                        }
                                                }

                                                if (!empty($COLOR_arr)){
                                                        $COLOR_arr = array_unique($COLOR_arr);
                                                        $COLOR_arr = array_values($COLOR_arr);

                                                        foreach ($COLOR_arr as $COLOR){
                                                                $fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($COLOR)."'");
                                                                if (empty($fv_id)){
                                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($COLOR)."')");
                                                                        $fv_id = db_insert_id();
                                                                }

                                                                $fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
                                                                if (empty($fp_id)){
                                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
                                                                }
                                                        }
                                                }
#####


/*
                                                $fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($Color)."'");
                                                if (empty($fv_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($Color)."')");
                                                        $fv_id = db_insert_id();
                                                }

                                                $fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
                                                if (empty($fp_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
                                                }
*/
                                        }

//func_print_r($productid);
//die("test");

                                        if (!empty($Display_Size)){
                                                #Display_Size (f_id = 15)
                                                $f_id = 15;
                                                $fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($Display_Size)."'");
                                                if (empty($fv_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($Display_Size)."')");
                                                        $fv_id = db_insert_id();
                                                }

                                                $fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
                                                if (empty($fp_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
                                                }
                                        }


                                        if (!empty($Material) && strlen($Material) < 60){
                                                #Material (f_id = 16)
                                                $f_id = 16;

//$Material = "red, green/blue & white, red1, Polyester,90%;Electronics,5%;Plastic,5% / green1;blue1 & white1 & red2; green2/blue2 & white2, 60% Polyester, 40% Nylon, (Dress) 5% Cotton, Leaves: Nylon; Base: Wire, Sage, Washed Blue."; 
#####
                                                $MATERIAL = $Material;
                                                if (substr($MATERIAL, -1) == "." || substr($MATERIAL, -1) == ","){
                                                        $MATERIAL = substr($MATERIAL, 0, -1);
                                                }

                                                $delimetr = ",";
                                                $MATERIAL = str_replace("/", $delimetr, $MATERIAL);
                                                $MATERIAL = str_replace("&", $delimetr, $MATERIAL);
                                                $MATERIAL = str_replace(";", $delimetr, $MATERIAL);

                                                $MATERIAL_arr = array();
                                                $tmp_material_arr =  explode($delimetr, $MATERIAL);
                                                if (!empty($tmp_material_arr) && is_array($tmp_material_arr)){
                                                        foreach ($tmp_material_arr as $new_material){
                                                                $new_material = preg_replace("/\(.*\)/", '', $new_material);
                                                                $new_material = preg_replace("/(^.*\%)/", '', $new_material);
                                                                $new_material = preg_replace("/(^.*\:)/", '', $new_material);
                                                                $new_material = trim($new_material);

								if (!empty($new_material)){
	                                                                $MATERIAL_arr[] = $new_material;
								}
                                                        }
                                                }

/*
                                                $material_explode_arr = array(";", ",");
                                                $MATERIAL_arr = array();
                                                foreach ($material_explode_arr as $delim){
                                                        $tmp_material_arr =  explode($delim, $MATERIAL);
                                                        if (!empty($tmp_material_arr) && is_array($tmp_material_arr)){
                                                                foreach ($tmp_material_arr as $new_material){
									$new_material = preg_replace("/\(.*\)/", '', $new_material);
									$new_material = preg_replace("/(^.*\%)/", '', $new_material);
									$new_material = preg_replace("/(^.*\:)/", '', $new_material);
                                                                        $new_material = trim($new_material);
                                                                        $MATERIAL_arr[] = $new_material;
                                                                }
                                                        }
                                                }

                                                if (!empty($MATERIAL_arr)){
                                                        foreach ($material_explode_arr as $delim){
                                                                foreach ($MATERIAL_arr as $k_material => $tmp_material){
                                                                        if (strpos($tmp_material, $delim) !== false){
                                                                                unset($MATERIAL_arr[$k_material]);
                                                                        }
                                                                }
                                                        }
                                                }
*/


                                                if (!empty($MATERIAL_arr)){
                                                        $MATERIAL_arr = array_unique($MATERIAL_arr);
                                                        $MATERIAL_arr = array_values($MATERIAL_arr);

							foreach ($MATERIAL_arr as $Material){

		                                                $fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($Material)."'");
                		                                if (empty($fv_id)){
                                		                        db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($Material)."')");
                                                		        $fv_id = db_insert_id();
		                                                }

                		                                $fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
                                		                if (empty($fp_id)){
                                                		        db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
		                                                }
                		                        }
						}
#####
					}


                                        if (!empty($Celebration)){
                                                #Celebration (f_id = 17)
                                                $f_id = 17;
                                                $fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($Celebration)."'");
                                                if (empty($fv_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($Celebration)."')");
                                                        $fv_id = db_insert_id();
                                                }

                                                $fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
                                                if (empty($fp_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
                                                }
                                        }

                                        if (!empty($Gender)){
                                                #Gender (f_id = 18)
                                                $f_id = 18;
                                                $fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($Gender)."'");
                                                if (empty($fv_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($Gender)."')");
                                                        $fv_id = db_insert_id();
                                                }

                                                $fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
                                                if (empty($fp_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
                                                }
                                        }

                                        if (!empty($Age_Group)){
                                                #Age_Group (f_id = 19)
                                                $f_id = 19;
                                                $fv_id = func_query_first_cell("SELECT fv_id FROM $sql_tbl[cidev_filter_values] WHERE f_id='$f_id' AND fv_name='".addslashes($Age_Group)."'");
                                                if (empty($fv_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_values] (f_id, fv_name) VALUES ('$f_id', '".addslashes($Age_Group)."')");
                                                        $fv_id = db_insert_id();
                                                }

                                                $fp_id = func_query_first_cell("SELECT fp_id FROM $sql_tbl[cidev_filter_products] WHERE fv_id='$fv_id' AND productid='$productid'");
                                                if (empty($fp_id)){
                                                        db_query("INSERT INTO $sql_tbl[cidev_filter_products] (fv_id, productid) VALUES ('$fv_id', '$productid')");
                                                }
                                        }
###
##
#

                                        if ($insert_product_flag){
                                                func_build_quick_flags($productid);
                                        }

                                        func_build_quick_prices($productid);

#
##
###
					if (!empty($Prod_ID)){
						$image_found = false;

						$ext = "jpg";
						$image_file_name = $Prod_ID.".".$ext;
						$IMAGE_URL = "https://img.buycostumes.com/mgen/merchandiser/".$image_file_name;
						$image_file_name = $SKU."_1.".$ext;	
						$image_file_path = $xcart_dir . "/images/D/".$image_file_name;

						if (url_exists($IMAGE_URL)){
							func_delete_image($productid, "D", false);
							if (@copy($IMAGE_URL, $image_file_path)){
								$image_found = true;
							}
						} else {

	                                                $ext = "tif";
        	                                        $image_file_name = $Prod_ID.".".$ext;
                	                                $IMAGE_URL = "https://img.buycostumes.com/mgen/merchandiser/".$image_file_name;
                        	                        $image_file_name = $SKU."_1.".$ext;
                                	                $image_file_path = $xcart_dir . "/images/D/".$image_file_name;

	                                                if (url_exists($IMAGE_URL)){
								func_delete_image($productid, "D", false);
        	                                                if (@copy($IMAGE_URL, $image_file_path)){
                	                                                $image_found = true;
                        	                                }
                                	                }
						}

						if ($image_found){
                                                        $img_info = getimagesize($image_file_path);

                                                        $image_data['id'] = $productid;
                                                        $image_data['date'] = time();
                                                        $image_data['image_path'] = $image_file_path;
                                                        $image_data['image_type'] = $img_info["mime"];
                                                        $image_data['image_x'] = $img_info[0];
                                                        $image_data['image_y'] = $img_info[1];
                                                        $image_data['alt'] = addslashes($PRODUCT_NAME);
                                                        $image_data['avail'] = 'Y';
                                                        $image_data['orderby'] = '10';

							$gen_new_D_img = false;
							if ($image_data['image_y'] >= $image_data['image_x'] && $image_data['image_y'] > 800){
								$new_image_y = 800;
								$new_image_x = ($image_data['image_x']/$image_data['image_y'])*$new_image_y;
								$new_image_x = intval($new_image_x);
								$gen_new_D_img = true;
							} elseif ($image_data['image_y'] < $image_data['image_x'] && $image_data['image_x'] > 620){
                                                                $new_image_x = 620;
                                                                $new_image_y = ($image_data['image_y']/$image_data['image_x'])*$new_image_x;
								$new_image_y = intval($new_image_y);
								$gen_new_D_img = true;
                                                        }

							if ($gen_new_D_img){

								$IMAGE_URL .= "?is=".$new_image_x.",".$new_image_y.",0xffffff";
								@copy($IMAGE_URL, $image_file_path);
							
								$res = func_resize_image($image_data["image_path"], $new_image_x, $new_image_y);
								if (!empty($res["file_path"])){
									if (@copy($res["file_path"], $image_file_path)){
       				                                                $image_data['image_x'] = $res["image_x"];
        	        		                                        $image_data['image_y'] = $res["image_y"];
										$image_data['image_type'] = $res["image_type"];
									}
									unset($res);
								}
							}

							$image_data['image_size'] = filesize($image_file_path);

                                                        $image_id = func_array2insert('images_D', $image_data);

//                                                      $image = func_file_get($image_file_path, true);
//                                                      $image_data['md5'] = md5($image);


                                                        $image_width_x_height = $image_data['image_x'] * $image_data['image_y'];
                                                        if ($image_width_x_height > 0 && $image_width_x_height < 4500000) {
                                                                func_delete_image($productid, "T", true);

                                                                if (func_generate_image($productid, 'D', 'T', false, false, $image_id)) {
                                                                        func_save_product_thumb_image($productid, 'T');
                                                                }

                                                                func_delete_image($productid, "P", true);
                                                                if (func_generate_image($productid, 'D', 'P', false, false, $image_id)) {
                                                                        func_save_product_thumb_image($productid, 'P');
                                                                }
                                                        }

						}

//func_print_r($is_url_exists, $IMAGE_URL, $image_file_path, $productid);
//die();


					}
###
##
#

				}

//func_print_r($productid);
//die("test");

                        } else {
//                                $NEW_PRODUCTS_header = implode(", ", $buffer);
                        }
                }
                fclose($handle);
                $import_info[$i]["lines_in_file"] = $line_number;

	} else {
		$import_info[$i]["file_is_found"] = "N";
	}

//	ftp_quit($ftp);
//   } else {
//                print("Could not open host. (Distributor: ".$product_feed_info["manufacturer"] .")<br />");
//   }
//  }
 }

/*
#############
$any_file_is_found = true;

$import_info[0]["file"] = "CatalogFeed";
$import_info[0]["file_is_found"] = "Y";
$import_info[0]["lines_in_file"] = "8918";
$import_info[0]["local_file_full_path_psv"] = "/disk2/test.stores/dev1/files/main_product_feeds/219_CatalogFeed20140428.psv";

$import_info[1]["file"] = "PartySupplies";
$import_info[1]["file_is_found"] = "Y";
$import_info[1]["lines_in_file"] = "8929";
$import_info[1]["local_file_full_path_psv"] = "/disk2/test.stores/dev1/files/main_product_feeds/219_PartySupplies20140428.psv";
#############
*/

 if ($any_file_is_found){


#
##
###
    foreach ($import_info as $k => $v){
	if ($v["file_is_found"] == "Y"){
		func_flush("<br />\n");

                $line_number = 0;
                $handle = @fopen($v["local_file_full_path_psv"], "r");
                while (($buffer = fgetcsv($handle, 20480, "|")) !== FALSE) {
                        if (empty($buffer)) continue;

                        $line_number++;
                        if ($line_number % 10 == 0) {
                                func_flush(".");
                                if($line_number % 1000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }

//func_print_r($buffer);
                        if ($line_number > 1) {
                                $SKU = trim($buffer[0]);
                                $Accessory1 = trim($buffer[20]);
                                $Accessory2 = trim($buffer[21]);
                                $Accessory3 = trim($buffer[22]);
                                $CrossSell1 = trim($buffer[23]);
                                $CrossSell2 = trim($buffer[24]);
                                $CrossSell3 = trim($buffer[25]);
                                $CrossSell4 = trim($buffer[26]);
				$Status = trim($buffer[17]);
                                $SKU = $product_feed_info["code"] . "-" . strtoupper($SKU);

                                if (!(strtolower($Status) == "active" || strtolower($Status) == "activeoos")) continue;

				$product_info = func_query_first("SELECT productid FROM $sql_tbl[products] WHERE productcode='".addslashes($SKU)."'");

				if (!empty($product_info)){


                                        if (!empty($Accessory1) || !empty($Accessory2) || !empty($Accessory3)){

                                                $related_productids_arr = array();

                                                if (!empty($Accessory1)){
                                                        $tmp_product_id = func_query_first_cell("SELECT productid FROM $sql_tbl[products] WHERE supplier_internal_product_id='$Accessory1'");
                                                        if (!empty($tmp_product_id)) $related_productids_arr[] = $tmp_product_id;
                                                }

                                                if (!empty($Accessory2)){
                                                        $tmp_product_id = func_query_first_cell("SELECT productid FROM $sql_tbl[products] WHERE supplier_internal_product_id='$Accessory2'");
                                                        if (!empty($tmp_product_id)) $related_productids_arr[] = $tmp_product_id;
                                                }

                                                if (!empty($Accessory3)){
                                                        $tmp_product_id = func_query_first_cell("SELECT productid FROM $sql_tbl[products] WHERE supplier_internal_product_id='$Accessory3'");
                                                        if (!empty($tmp_product_id)) $related_productids_arr[] = $tmp_product_id;
                                                }

                                                if (!empty($related_productids_arr)){
							db_query("DELETE FROM $sql_tbl[product_links] WHERE productid2='$product_info[productid]'");
							foreach ($related_productids_arr as $kr => $vr){
								db_query("INSERT INTO $sql_tbl[product_links] (productid1, productid2) VALUES ('$vr', '$product_info[productid]')");
							}
//func_print_r($related_productids_arr, $product_info);
//die();
                                                }

                                                unset($related_productids_arr);
                                        }



					if (!empty($CrossSell1) || !empty($CrossSell2) || !empty($CrossSell3) || !empty($CrossSell4)){

						$similar_productids_arr = array();

						if (!empty($CrossSell1)){
							$tmp_product_id = func_query_first_cell("SELECT productid FROM $sql_tbl[products] WHERE supplier_internal_product_id='$CrossSell1'");
							if (!empty($tmp_product_id)) $similar_productids_arr[] = $tmp_product_id;
						}

                                                if (!empty($CrossSell2)){
                                                        $tmp_product_id = func_query_first_cell("SELECT productid FROM $sql_tbl[products] WHERE supplier_internal_product_id='$CrossSell2'");
                                                        if (!empty($tmp_product_id)) $similar_productids_arr[] = $tmp_product_id;
                                                }

                                                if (!empty($CrossSell3)){
                                                        $tmp_product_id = func_query_first_cell("SELECT productid FROM $sql_tbl[products] WHERE supplier_internal_product_id='$CrossSell3'");
                                                        if (!empty($tmp_product_id)) $similar_productids_arr[] = $tmp_product_id;
                                                }

                                                if (!empty($CrossSell4)){
                                                        $tmp_product_id = func_query_first_cell("SELECT productid FROM $sql_tbl[products] WHERE supplier_internal_product_id='$CrossSell4'");
                                                        if (!empty($tmp_product_id)) $similar_productids_arr[] = $tmp_product_id;
                                                }

						if (!empty($similar_productids_arr)){

//							if (!empty($similar_productids_arr[3])) unset($similar_productids_arr[3]);

							$similar_productids = implode(",", $similar_productids_arr);

							db_query("UPDATE $sql_tbl[products] SET similar_productids='$similar_productids', similar_time='".time()."' WHERE productid='$product_info[productid]'");
						}

						unset($similar_productids_arr);
					}

				}
			}
		}
	}
    }
###
##
#



//func_print_r($import_info);

    # Send combined message
    $subj = "IMPORT ".$product_feed_info["manufacturer"]." PRODUCT FEED FINISHED";
    $body = "IMPORT ".$product_feed_info["manufacturer"]." PRODUCT FEED FINISHED";
    $count_inserted_products = 0;
    $count_updated_products = 0;
    foreach ($import_info as $k => $v){
        if ($v["file_is_found"] == "Y"){
		$body .= $v["file"].": Products imported/updated: " . $v["count_inserted_products"]."/".$v["count_updated_products"] ."\n";
		$count_updated_products += $v["count_updated_products"];
		$count_inserted_products += $v["count_inserted_products"];
	}
    }

    $last_imported_updated_products_count = $count_inserted_products."/".$count_updated_products;
    db_query("UPDATE $sql_tbl[supplier_product_feeds] SET last_import_date='".$launch_time."', last_imported_updated_products_count='$last_imported_updated_products_count' WHERE manufacturerid='$product_feed_info[manufacturerid]'");

    $function_time = time() - $function_launch_time;
    $function_time = $function_time/(60);
    $function_time = round($function_time,1);
    $body .= "Duration: ".$function_time." Mins\n";

    func_backprocess_log("supplier feeds", $body);
//    func_send_simple_mail($product_feed_info["product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

 }
}

/*
function url_exists($url) {
    $hdrs = @get_headers($url);
    return is_array($hdrs) ? preg_match('/^HTTP\\/\\d+\\.\\d+\\s+2\\d\\d\\s+.*$/',$hdrs[0]) : false;
} 
*/

#################

function func_GENERAL_BSE_INVENTORY_FEED($manufacturerid){
        global $sql_tbl, $xcart_dir, $launch_time, $function_launch_time;

        $file_is_found_and_uploaded = false;
        $file_is_found = false;
        $count_updated_products = 0;
        $count_marked_as_out_of_stock_products = 0;
        $count_marked_as_in_stock_products = 0;

/*
        if (function_exists("ftp_connect")) {

                $general_info = func_query_first("SELECT manufacturer, code, d_ftp_host, d_ftp_login, d_ftp_password, d_ftp_folder, d_product_management_team_email, d_last_feed_rows_processed, d_validation_threshold FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");

                $manufacturer_code = trim($general_info["code"]);

                $ftp = ftp_connect($general_info["d_ftp_host"]);
                if ($ftp && @ftp_login($ftp, $general_info["d_ftp_login"], $general_info["d_ftp_password"])) {

                        ftp_pasv($ftp, true);

                        $contents = ftp_nlist($ftp, $general_info["d_ftp_folder"]);

                        if (!empty($contents) && is_array($contents)){
                                $all_files = array();
                                foreach ($contents as $k => $v){
                                        if (strpos($v, 'FullInventory.') !== false && strpos($v, '.xml') !== false ){

                                                $tmp_file_arr  = explode(".xml", $v);
                                                $tmp_date_arr = explode("FullInventory.", $tmp_file_arr[0]);
                                                $tmp_date = $tmp_date_arr[1];

                                                if (!empty($tmp_date)){
                                                        $year = substr($tmp_date, 0, 4);
                                                        $month = substr($tmp_date, 4, 2);
                                                        $day = substr($tmp_date, 6, 2);

                                                        $tmp_mktime = mktime(0, 0, 0, $month, $day, $year);
                                                        $all_files[$k]["file"] = $v;
                                                        $all_files[$k]["time"] = $tmp_mktime;
                                                }
                                        }
                                }

                                if (!empty($all_files)){
                                        $all_files = my_array_sort($all_files, "time", SORT_DESC);
                                        $all_files = array_values($all_files);

                                        $server_file_name = $all_files[0]["file"];
                                        $server_file_name_full_path = $general_info["d_ftp_folder"] . $server_file_name;

                                        $local_file_name = $manufacturerid."_".$server_file_name;
                                        $local_file_name_full_path = $xcart_dir . "/files/product_feeds/" . $local_file_name;
                                }
                        }

                        if (!empty($local_file_name_full_path) && !empty($server_file_name_full_path)){
                                if (@ftp_get($ftp, $local_file_name_full_path, $server_file_name_full_path, FTP_BINARY)) {
                                        $file_is_found = true;
                                }
                        }

                        ftp_quit($ftp);

                } else {
                        print("Could not open host. (Distributor: ".$general_info["manufacturer"] .")<br />");
                }
        }

	if ($file_is_found){
		$handle = fopen($local_file_name_full_path, "r");
		$contents = fread($handle, filesize($local_file_name_full_path));
		fclose($handle);

		$contents_arr = preg_match_all("|<InventoryItem>(.*)</InventoryItem>|Uis", $contents, $out, PREG_PATTERN_ORDER);

		if (!empty($out[0]) && is_array($out[0])){
			foreach ($out[0] as $k => $v){

				$BuySeasonsSKU_arr = preg_match("|<BuySeasonsSKU>(.*)</BuySeasonsSKU>|sei", $v, $arr);
				$BuySeasonsSKU = $arr[1];

                                $Quantity_arr = preg_match("|<Quantity>(.*)</Quantity>|sei", $v, $arr);
                                $Quantity = $arr[1];

                                $Status_arr = preg_match("|<Status>(.*)</Status>|sei", $v, $arr);
                                $Status = $arr[1];
			}
		}
	}
*/


        $general_info = func_query_first("SELECT manufacturer, code, d_ftp_host, d_ftp_login, d_ftp_password, d_ftp_folder, d_product_management_team_email, d_last_feed_rows_processed, d_validation_threshold FROM $sql_tbl[manufacturers] WHERE manufacturerid='$manufacturerid'");

        $manufacturer_code = trim($general_info["code"]);

	$server_file_name = "inventoryfeed.csv";
        $server_file_name_full_path = "https://www.buyseasonsdirect.com/resources/" . $server_file_name;

        $local_file_name = $manufacturerid."_".$server_file_name;
        $local_file_name_full_path = $xcart_dir . "/files/product_feeds/" . $local_file_name;

	if (@copy($server_file_name_full_path, $local_file_name_full_path)){
		$file_is_found = true;
	}

//$file_is_found = true;

	if ($file_is_found){
                $handle = @fopen($local_file_name_full_path, "r");
                if ($handle) {

                    # check for lines
//                  $lines_in_file = count(file($local_file_name_full_path));
###
                    $lines_in_file = 0;
                    while (($buffer = fgetcsv($handle, 20480, ",")) !== FALSE) {
                        $lines_in_file++;
                        if ($lines_in_file % 100 == 0) {
                                func_flush(".");
                                if($lines_in_file % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }
                    }
                    fclose($handle);
###

                    if ($general_info["d_last_feed_rows_processed"] > 0){
                                $lines_in_file_DIV_last_feed_rows_processed = $lines_in_file/$general_info["d_last_feed_rows_processed"];

                                if ($lines_in_file_DIV_last_feed_rows_processed < $general_info["d_validation_threshold"]){
                                        $subj = "SUPPLIER INVENTORY FEED validation failed!!!";
                                        $body = $general_info["manufacturer"] . ". Count lines in new file: ".$lines_in_file."\n";
                                        $body .= "Last feed rows processed: ".$general_info["d_last_feed_rows_processed"]."\n";
                                        $body .= "processing stopped";
                                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                                        fclose($handle);
                                        return;
                                }
                    }
                    db_query("UPDATE $sql_tbl[manufacturers] SET d_last_feed_rows_processed='$lines_in_file' WHERE manufacturerid='$manufacturerid'");
                    #


                    $line_number = 0;
                    $NEW_PRODUCTS = array();
                    $discontinued_products = array();
                    $all_feed_productcodes = array();

                    print "<br />".$general_info["manufacturer"]."<br />";
                    print "First iteration:<br />";

                    $handle = @fopen($local_file_name_full_path, "r");
                    while (($buffer = fgetcsv($handle, 10240, ",")) !== FALSE) {
                        $line_number++;

                        if ($line_number % 100 == 0) {
                                func_flush(".");
                                if($line_number % 5000 == 0) {
                                        func_flush("<br />\n");
                                }

                                func_flush();
                        }

                        if ($line_number == "1") {
				$NEW_PRODUCTS_header = "BuySeasonsSKU, Quantity, Status";
			}

                        if ($line_number >= 1) {
                                $SKU = trim($buffer[0]);
                                $SKU = str_replace('"','',$SKU);
                                $SKU = str_replace('=','',$SKU);
                                $SKU = strtoupper($SKU);
                                $AVAIL = trim($buffer[1]);
//                                $FEED_ETA = trim($buffer[4]);
                                $FEED_STATUS = trim($buffer[2]);
                                $FEED_STATUS = strtolower($FEED_STATUS);

                                if ($AVAIL == ""){
                                        $AVAIL = 0;
                                }

                                $feed_productcode = $manufacturer_code . "-" . $SKU;
                                $all_feed_productcodes[] = $feed_productcode;

                                $product_info_arr = func_query_first("SELECT productid, productcode, update_search_index, forsale, avail, r_avail, eta_date_mm_dd_yyyy FROM $sql_tbl[products] WHERE productcode='".addslashes($feed_productcode)."'");

                                if (!empty($product_info_arr)){

                                        $productcode = $product_info_arr["productcode"];
                                        $productid = $product_info_arr["productid"];

                                        $current_forsale = $product_info_arr["forsale"];
                                        $current_avail = $product_info_arr["r_avail"];
                                        $current_eta_date_mm_dd_yyyy = $product_info_arr["eta_date_mm_dd_yyyy"];
					$current_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($current_eta_date_mm_dd_yyyy, "m/d/Y");

                                        $new_forsale = $current_forsale;
                                        $new_avail = $current_avail;
                                        $new_eta_date_mm_dd_yyyy = $current_eta_date_mm_dd_yyyy;
                                        $new_eta_date_mm_dd_yyyy_time = "";

                                        $product_is_updated = false;
                                        $marked_as_out_of_stock_products = false;
                                        $marked_as_in_stock_products = false;


                                        if ($FEED_STATUS == "discontinued"){
                                                $new_forsale = "N";
                                                $new_avail = 0;
						$new_eta_date_mm_dd_yyyy = "";

                                                if ($current_forsale != "N"){
                                                        $discontinued_products[] = $product_info_arr;
                                                }

                                        } 
					elseif ($FEED_STATUS == "active") {
                                                $new_forsale = "Y";

						if ($AVAIL == 0){
							$new_avail = 0;
							$new_eta_date_mm_dd_yyyy_time = $current_time + 60*60*24*35;
						} 
						elseif ($AVAIL > 0){
	                                                $new_avail = $AVAIL;
        	                                        $new_eta_date_mm_dd_yyyy = "";
						}
                                        }
					elseif ($FEED_STATUS == "inactive") {
						$new_forsale = "Y";
						$new_avail = 0;
						$new_eta_date_mm_dd_yyyy_time = $current_time + 60*60*24*35;
					}

                                        if (!empty($new_eta_date_mm_dd_yyyy_time)){
                                                $new_eta_date_mm_dd_yyyy = date("m/d/Y", $new_eta_date_mm_dd_yyyy_time);
                                        }


                                        if ($new_avail == "0"){
                                                if ($current_avail > 0){
                                                        $marked_as_out_of_stock_products = true;
                                                }
                                        } else {
                                                if ($current_avail == 0){
                                                        $marked_as_in_stock_products = true;
                                                }
                                        }

                                        if ($new_forsale != $current_forsale || $new_eta_date_mm_dd_yyyy != $current_eta_date_mm_dd_yyyy || $current_avail != $new_avail){
/*
                                                        $update_search_index = 'Y';
                                                        if ($new_forsale == 'N' && $product_info_arr["update_search_index"] == "N"){
                                                                $update_search_index = 'D';
                                                        }
*/
//                                                        db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale', update_search_index='$update_search_index' WHERE productid='$productid'");
							$new_eta_date_mm_dd_yyyy = func_convert_date_mm_dd_yyyy($new_eta_date_mm_dd_yyyy, "seconds");
                                                        db_query("UPDATE $sql_tbl[products] SET r_avail='$new_avail', eta_date_mm_dd_yyyy='$new_eta_date_mm_dd_yyyy', forsale='$new_forsale' WHERE productid='$productid'");
                                                        $product_is_updated = true;
                                        }

                                        if ($product_is_updated){
                                                $file_is_found_and_uploaded = true;
                                                $count_updated_products++;
                                        }

                                        if ($marked_as_out_of_stock_products){
                                                $count_marked_as_out_of_stock_products++;
                                        }

                                        if ($marked_as_in_stock_products){
                                                $count_marked_as_in_stock_products++;
                                        }

                                } else {
					if ($FEED_STATUS != "discontinued"){
	                                        $NEW_PRODUCTS[] = implode(", ", $buffer);
					}
                                }
                        } else {
//                                $NEW_PRODUCTS_header = implode(", ", $buffer);
                        }

                    }
                    fclose($handle);

                    if (!empty($all_feed_productcodes) && is_array($all_feed_productcodes)){

                        $count_products = func_query_first_cell("SELECT COUNT(*) FROM $sql_tbl[products] WHERE productcode LIKE '".$manufacturer_code."-%' AND forsale='Y'");

                        if ($count_products > 0){
                                $manufacturer_code_products = db_query("SELECT productid, productcode, forsale, update_search_index FROM $sql_tbl[products] WHERE productcode LIKE '".$manufacturer_code."-%' AND forsale='Y'");

                                $line_number = 0;
                                print "<br />Second iteration:<br />";
                                while ($product = db_fetch_array($manufacturer_code_products)) {

                                        $line_number++;
                                        if ($line_number % 100 == 0) {
                                                func_flush(".");
                                                if($line_number % 5000 == 0) {
                                                        func_flush("<br />\n");
                                                }

                                                func_flush();
                                        }

                                        $productcode = strtoupper(trim($product["productcode"]));

                                        if (!in_array($productcode, $all_feed_productcodes) && $product["forsale"] != "N") {
                                                $file_is_found_and_uploaded = true;
                                                $discontinued_products[] = $product;
/*
                                                $update_search_index = $product["update_search_index"];
                                                if ($update_search_index == "N"){
                                                        $update_search_index = "D";
                                                }
*/
//                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N', update_search_index='$update_search_index' WHERE productid='".$product["productid"]."'");
                                                db_query("UPDATE $sql_tbl[products] SET r_avail='0', forsale='N' WHERE productid='".$product["productid"]."'");
                                        }
                                }
                        }
                    }

                    db_query("UPDATE $sql_tbl[manufacturers] SET d_most_recent_feed_updation_date='".$launch_time."' WHERE manufacturerid='$manufacturerid'");


                    $count_discontinued_products = count($discontinued_products);
                    if (!empty($discontinued_products) && is_array($discontinued_products)){

                        $subj = $general_info["manufacturer"]." INVENTORY FEED UPDATE - discontinued products";
                        $body = "Discontinued products count: ".$count_discontinued_products."\n\n";

                        $body .= "<table border='1'>\n<tr><td>ProductCode</td><td>Link to SF backend</td></tr>\n";
                        foreach ($discontinued_products as $k => $v){
                                $store_url = "www.artistsupplysource.com";
                                $body .= "<tr><td> ".$v["productcode"]." </td><td> <a href='http://".$store_url."/admin/product_modify.php?productid=".$v["productid"]."'>http://".$store_url."/admin/product.php?productid=".$v["productid"]."</a> </td></tr>\n";
                        }
                        $body .= "</table>";

                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                    }

                    $count_NEW_PRODUCTS = count($NEW_PRODUCTS);
                    if (!empty($NEW_PRODUCTS) && is_array($NEW_PRODUCTS)){
                        $subj = $general_info["manufacturer"]." INVENTORY FEED UPDATE - new products";
                        $body = "Expected new products count: ".$count_NEW_PRODUCTS."\n\n";

                        $body .= $NEW_PRODUCTS_header."\n";
                        foreach ($NEW_PRODUCTS as $k => $v){
                                $body .= $v."\n";
                        }

                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                    }

                    $count_all_feed_productcodes = count($all_feed_productcodes);
                    $sum_updated_products = $count_marked_as_out_of_stock_products + $count_marked_as_in_stock_products + $count_discontinued_products;

                    $subj = $general_info["manufacturer"]." INVENTORY FEED UPDATE - summary";
                    $body = $general_info["manufacturer"]." INVENTORY FEED UPDATE - summary";
                    $body .= "products in storefront: ".$count_products."\n";
                    $body .= "products in feed: ".$count_all_feed_productcodes."\n";
                    $body .= "updated products: ".$sum_updated_products."\n";
                    $body .= "marked as `out of stock` products: ".$count_marked_as_out_of_stock_products."\n";
                    $body .= "marked as `in stock` products: ".$count_marked_as_in_stock_products."\n";
                    $body .= "new products: ".$count_NEW_PRODUCTS."\n";
                    $body .= "discontinued products: ".$count_discontinued_products."\n";
                    $function_time = time() - $function_launch_time;
                    $function_time = $function_time/(60);
                    $function_time = round($function_time,1);
                    $body .= "Duration: ".$function_time." Mins\n";

		    func_backprocess_log("supplier feeds", $body);
//                    func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");

                } // if ($handle)
                else {
                        $subj = "SUPPLIER INVENTORY FEED validation failed!!!";
                        $body = $general_info["manufacturer"] . ". File cannot be opened.";
                        func_send_simple_mail($general_info["d_product_management_team_email"], $subj, $body, "supplier.feeds@s3stores.com");
                        return;
                }
	}
}
