<?php

function rms_config() {
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"Fiuu Online Payment"),
     "merchantid" => array("FriendlyName" => "Fiuu Merchant ID", "Type" => "text", "Size" => "20", ),
     "verifykey" => array("FriendlyName" => "Fiuu Verify Key", "Type" => "text", "Size" => "20", ),
     "secretkey" => array("FriendlyName" => "Fiuu Secret Key", "Type" => "text", "Size" => "20", ),
	 "extended_vcode" => array("FriendlyName" => "Enable Fiuu Extended Vcode", "Type" => "checkbox" )    
    );
	return $configarray;
}

function rms_link($params) {
global $CONFIG;

	# Gateway Specific Variables
	$merchantid = $params['merchantid'];
	$verifykey = $params['verifykey'];
	$description = $params['description'];
	$enableExtendedVcode = $params['extended_vcode'];
	
	# Invoice Variables
	$invoiceid = $params['invoiceid'];
	$amount = $params['amount']; # Format: ##.##
    $currency = $params['currency']; # Currency Code
	
	# query select desc
	$sql = mysql_query("SELECT description FROM tblinvoiceitems WHERE invoiceid='$invoiceid'");
	$row = mysql_fetch_assoc($sql);
	$desc = $row['description'];

	# Client Variables
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$bill_name = $firstname." ".$lastname;
	
	$email = $params['clientdetails']['email'];
	$address1 = $params['clientdetails']['address1'];
	$address2 = $params['clientdetails']['address2'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = $params['clientdetails']['postcode'];
	$country = $params['clientdetails']['country'];
	$address = $address1." ".$address2." ".$city." ".$city." ".$state." ".$postcode." ".$country;
	$bill_desc = $description." ".$desc;
	$returnurl = $CONFIG['SystemURL']."/modules/gateways/return/fiuu_return.php";
	$callbackurl = $CONFIG['SystemURL']."/modules/gateways/callback/fiuu_callback.php";
	$notifyurl = $CONFIG['SystemURL']."/modules/gateways/notify/fiuu_notify.php";
	$phone = $params['clientdetails']['phonenumber'];
	
	$vkey = md5($amount.$merchantid.$invoiceid.$verifykey);
	if ($enableExtendedVcode == "on") {
		$vkey = md5($amount.$merchantid.$invoiceid.$verifykey.$currency);
	}

	# System Variables	
	# Enter your code submit to the gateway...

$code = '<form action="https://pay.fiuu.com/RMS/pay/'.$merchantid.'/" method="post" />
		 <input type=hidden name=instID value="'.$merchantid.'">
		 <input type=hidden name=orderid value="'.$invoiceid.'">
		 <input type=hidden name=amount value="'.$amount.'">
		 <input type=hidden name=cur value="'.$currency.'">
		 <input type=hidden name=bill_desc value="'.$bill_desc.'">
		 <input type=hidden name=bill_email value="'.$email.'">
		 <input type=hidden name=bill_name value="'.$bill_name.'">
		 <input type=hidden name=country value="'.$country.'">
		 <input type=hidden name=bill_mobile value="'.$phone.'">
		 <input type=hidden name=returnurl value="'.$returnurl.'">
		 <input type=hidden name=callbackurl value="'.$notifyurl.'">
		 <input type=hidden name=vcode value="'.$vkey.'">
		 <br>
		 <input src="./images/logo_rms.gif" name="submit" type="image">
		 </form>';
		 
	return $code;
}

?>
