<?php

# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");


global $CONFIG;

$gatewaymodule = "rms"; # Enter your gateway module name here replacing template

$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback

# Get Returned Variables

$_POST['treq'] = 1;
$nbcb = $_POST['nbcb'];

 $transid = $_POST['tranID'];
 $orderid = $_POST['orderid'];	
 $status = $_POST['status'];
 $domain = $_POST['domain'];
 $amount = $_POST['amount'];
 $currency = $_POST['currency'];
 $appcode = $_POST['appcode'];
 $paydate = $_POST['paydate'];
 $skey = $_POST['skey'];
 $cust_name = $_POST['cust_name'];
 $cust_email = $_POST['email'];
 $passwd = $GATEWAY['secretkey'];

// Check if the current $skey is the same as the one stored in the session
session_start();
if (isset($_SESSION['prev_skey']) && $_SESSION['prev_skey'] === $skey) {
    die("Duplicate response. Request terminated.");
} else {
    $_SESSION['prev_skey'] = $skey;
}
 
  $key0 = md5($tranID.$orderid.$status.$domain.$amount.$currency);
  $key1 = md5($paydate.$domain.$key0.$appcode.$passwd);

  if ( $skey != $key1 ) $status = -1;

 $viewinvoice = $CONFIG['SystemURL']."/viewinvoice.php?id=".$orderid;
 $clientarea = $CONFIG['SystemURL']."/clientarea.php?action=invoices";

$invoiceid = checkCbInvoiceID($orderid,$GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing

//checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does


if ($status=="00") {
    # Successful
    
    $checkResult = select_query("tblaccounts", "COUNT(*)", array("transid" => $transid));
    $checkData = mysql_fetch_array($checkResult);
	
    if ($checkData[0]) {
	header("Location: ".$viewinvoice);
	exit();
    }
    
    // --- BEGIN: Currency Conversion Fix ---
    // Get the original WHMCS invoice total and user ID
    $paymentAmount = $amount;
    $invoiceResult = select_query("tblinvoices", "userid, total", array("id" => $invoiceid));
    $invoiceData = mysql_fetch_array($invoiceResult);
    
    if ($invoiceData) {
        $invoiceTotal = $invoiceData['total'];
        $userid = $invoiceData['userid'];
        
        // Find the client's currency configuration
        $clientResult = select_query("tblclients", "currency", array("id" => $userid));
        $clientData = mysql_fetch_array($clientResult);
        
        if ($clientData) {
            $currencyResult = select_query("tblcurrencies", "code", array("id" => $clientData['currency']));
            $currencyData = mysql_fetch_array($currencyResult);
            
            // Compare WHMCS invoice currency code vs Fiuu's returned currency
            if ($currencyData && strtoupper($currencyData['code']) != strtoupper($currency)) {
                // Fiuu converted the amount (e.g., from USD to MYR). 
                // Since Fiuu confirms it was fully authorized ("00"), 
                // we bypass the MYR amount and mark the exact original invoice balance as paid.
                $paymentAmount = $invoiceTotal;
            }
        }
    }
    // --- END: Currency Conversion Fix ---
    
    // Initialize $fee explicitly to prevent PHP uninitialized variable warnings 
    $fee = 0;
    addInvoicePayment($invoiceid, $transid, $paymentAmount, $fee, $gatewaymodule);
    logTransaction($GATEWAY["name"], $_POST, "Successful");
    header('Location: '.$viewinvoice);
	
		
} else {
	# Unsuccessful
    logTransaction($GATEWAY["name"],$_POST,"Unsuccessful");
    header('Location: '.$clientarea);
}

?>
