<?php
#
# This function joins order_id's and urlencodes 'em
#
function func_get_urlencoded_orderids ($orderids) {
	if (!is_array($orderids))
		return '';

	return urlencode(join (",", $orderids));
}

function func_check_webinput($check_php = 1) {
	global $config, $sql_tbl, $_SERVER;

	static $pfiles = array (
		'cc_epdq.php' => 'cc_epdq_result.php',
		'cc_smartpag.php' => 'cc_smartpag_final.php',
		'cc_payzip.php' => 'cc_payzip_result.php',
		'cc_payflow_link.php' => 'cc_payflow_link_result.php',
		'cc_hsbc.php' => 'cc_hsbc_result.php',
		'cc_ogoneweb.php' => 'cc_ogoneweb_result.php',
		'cc_pswbill.php' => 'cc_pswbill_result.php',
		'cc_triple.php' => 'cc_triple_result.php',
		'cc_paybox.php' => 'cc_paybox_result.php',
		'cc_pp3.php' => array (
			'ebank_ok.php',
			'ebank_nok.php'
		),
		'cc_pi.php' => 'cc_pi_result.php'
	);

	$allow_php = array();
	$list = func_query("SELECT c.processor FROM $sql_tbl[ccprocessors] c, $sql_tbl[payment_methods] m WHERE m.active='Y' AND m.paymentid=c.paymentid AND c.background<>'Y'");

	if ($list) {
		foreach($list as $v) {
			$file = $v['processor'];
			if (!empty($pfiles[$file])) {
				if (is_array($pfiles[$file]))
					$allow_php = func_array_merge($allow_php, $pfiles[$file]);
				else
					$allow_php[] = $pfiles[$file];
			}
			else {
				$allow_php[] = $file;
			}
		}
	}

	$ip = $_SERVER["REMOTE_ADDR"];
	$allow_ip = $config["Security"]["allow_ips"];

	$not_found = true;
	if ($check_php && !empty($allow_php)) {
		for ($i = 0; $i < count($allow_php); $i++)
			$allow_php[$i] = preg_quote($allow_php[$i]);
		$script = $_SERVER["PHP_SELF"];
		$re_allow = "!(".implode("|",$allow_php).")$!S";
		$not_found = !preg_match($re_allow, $script);
	}

	if ($not_found) {
		x_log_flag('log_payment_processing_errors', 'PAYMENTS', "The script '".$_SERVER["PHP_SELF"]."' is not an entry point for a payment system!", true);
		header("Location: ../");
		die("Access denied");
	}

	if ($allow_ip) {
		$not_found = true;
		$a = explode(",",$allow_ip);
		foreach ($a as $v) {
			list($aip, $amsk) = explode("/",trim($v));

			# Cannot use 0x100000000 instead 4294967296
			$amsk = 4294967296 - ($amsk ? pow(2,(32-$amsk)) : 1);

			if ((ip2long($ip) & $amsk) == ip2long($aip)) {
				$not_found = false;
				break;
			}
		}

		return ($not_found ? "err" : "pass");
	}

	return "pass";
}

#
# Display payment page footer
#
function func_payment_footer() {
	global $smarty;

	if (defined("DISP_PAYMENT_FOOTER"))
		return false;

	$fn = $smarty->template_dir."/customer/main/payment_wait_end.tpl";
	$fp = @fopen($fn, "r");
	if ($fp) {
		$data = fread($fp, filesize($fn));
		fclose($fp);

		$data = preg_replace("/\{\*.*\*\}/Us", "", $data);
		$data = preg_replace("/\{\/?literal\}/Us", "", $data);

		echo $data;
	}
    
	define("DISP_PAYMENT_FOOTER", true);
}

#
# Generated auto-submit form
#
function func_create_payment_form($url, $fields, $name, $method = "POST") {
	global $smarty;

	$charset = "";
	if (!empty($smarty))
		$charset = $smarty->get_template_vars("default_charset");
	if (empty($charset))
		$charset = "iso-8859-1";

	$method = strtoupper($method);
	if (in_array($method, array("POST", "GET")))
		$method = "POST";

	$button_title = func_get_langvar_by_name("lbl_submit", array(), false, true);
	$script_note = func_get_langvar_by_name("txt_script_payment_note", array("payment" => $name), false, true);
	$noscript_note = func_get_langvar_by_name("txt_noscript_payment_note", array("payment" => $name, "button" => $button_title), false, true);
	?>
<form action="<?php echo $url; ?>" method="<?php echo $method; ?>" name="process">
<?php
	foreach($fields as $fn => $fv) {
?>	<input type="hidden" name="<?php echo $fn; ?>" value="<?php echo htmlspecialchars($fv); ?>" />
<?php
	}
?>
<table class="WebBasedPayment" cellspacing="0">
<tr>
	<td id="text_box">
<noscript>
<?php echo $noscript_note; ?><br />
<input type="submit" value="<?php echo $button_title; ?>">
</noscript>
	</td>
</tr>
</table>
</form>
<script type="text/javascript">
<!--
if (document.getElementById('text_box'))
	document.getElementById('text_box').innerHTML = "<?php echo strtr($script_note, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/')); ?>";
document.process.submit();
-->
</script>
	<?php
}

#
# Check IP
#
function func_is_valid_ip($ip) {
	return (bool)preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", trim($ip));
}

#
# Get valid IP
#
function func_get_valid_ip($ip) {
	return func_is_valid_ip($ip) ? $ip : '127.0.0.1';
}

#
# Check payment activity
#
function func_is_active_payment($php_script) {
	global $sql_tbl;

	$cnt = func_query_first_cell("SELECT COUNT($sql_tbl[ccprocessors].processor) FROM $sql_tbl[ccprocessors], $sql_tbl[payment_methods] WHERE $sql_tbl[ccprocessors].processor = '".addslashes($php_script)."' AND $sql_tbl[ccprocessors].paymentid = $sql_tbl[payment_methods].paymentid AND $sql_tbl[payment_methods].active = 'Y'");
	return ($cnt > 0);

}
