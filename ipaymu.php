<?php
/*
 * This is WHMCS module using IPAYMU payment gateway
 * Author : Faisal Reza 
 * URL : http://github.com/frezaf16/whmcsipaymu
 * Release Date: 2016.12.25
 * License : http://www.gnu.org/licenses/gpl.html
 */
/*
 * WHMCS - The Complete Client Management, Billing & Support Solution
 * Copyright (c) WHMCS Ltd. All Rights Reserved,
 * Email: info@whmcs.com
 * Website: http://www.whmcs.com
 */
/*
 * IPAYMU - Indonesian Payment Gateway
 * Website: https://ipaymu.com
 */
function ipaymu_config() {
	$configarray = array(
    "FriendlyName" => array("Type" => "System", "Value"=>"IPAYMU Module"),
    "ipaymu_username" => array("FriendlyName" => "Login ID", "Type" => "text", "Size" => "50", ),
	"ipaymu_apikey" => array("FriendlyName" => "API Key", "Type" => "text", "Size" => "50", ),
	"ipaymu_log" => array("FriendlyName" => "Gateway Log", "Type" => "yesno", "Description" => "Pilih untuk aktifkan log di Gateway Log", ),
	"ipaymu_callbackfile" => array("FriendlyName" => "Callback filename", "Type" => "text", "Size" => "20", "Description" => "Nama file di folder modules/gateways/callback", "Value"=>"ipaymu.php", ),
	"paypal_enabled" => array("FriendlyName" => "Module Paypal", "Type" => "yesno", "Description" => "Pilih untuk aktifkan. Anda harus aktifkan juga modul paypal di IPAYMU", ),
	"paypal_email" => array("FriendlyName" => "Paypal Email", "Type" => "text", "Size" => "20", ),
	"paypal_curconvert" => array("FriendlyName" => "Kurs USD", "Type" => "text", "Size" => "20", "Description" =>"Jika menggunaan satu kurs mata uang.",),
	/*"transmethod" => array("FriendlyName" => "Transaction Method", "Type" => "dropdown", "Options" => "Option1,Value2,Method3", ),
	 "instructions" => array("FriendlyName" => "Payment Instructions", "Type" => "textarea", "Rows" => "5", "Description" => "Do this then do that etc...", ),
	 "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Tick this to test", ),*/
	);
	return $configarray;
}
function ipaymu_link($params) {
	# Gateway Specific Variables
	$gatewayipaymuusername = $params['ipaymu_username'];
	$gatewayipaymuapikey = $params['ipaymu_apikey'];
	$gatewayipaymulog = $params['ipaymu_log'];
	$gatewayipaymucallbackfile = $params['ipaymu_callbackfile'];
	$gatewaypaypalenabled = $params['paypal_enabled'];
	$gatewaypaypalemail = $params['paypal_email'];
	$gatewaypaypalcurconvert = $params['paypal_curconvert'];
	# Invoice Variables
	$invoiceid = $params['invoiceid'];
	$description = $params["description"];
	$amount = $params['amount']; # Format: ##.##
	$currency = $params['currency']; # Currency Code
	# Client Variables
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$email = $params['clientdetails']['email'];
	$address1 = $params['clientdetails']['address1'];
	$address2 = $params['clientdetails']['address2'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = $params['clientdetails']['postcode'];
	$country = $params['clientdetails']['country'];
	$phone = $params['clientdetails']['phonenumber'];
	# System Variables
	$companyname = $params['companyname'];
	$systemurl = $params['systemurl'];
	$currency = $params['currency'];
	if(isset($params['convertto'])){
		$command = "getcurrencies";
		$adminuser =  $params["username"];
		$values["code"] = "USD";
		$results = (localAPI($command,$values,$adminuser));
		$current_currency_rate = 1;
		if($results['result']=='success'){
			for($i=0;$i<$results['totalresults'];$i++){
				if($results['currencies']['currency'][$i]['code']==$currency){
					$current_currency_rate = $results['currencies']['currency'][$i]['rate'];
				}elseif($results['currencies']['currency'][$i]['code']==$values["code"]){
					$usd_currency_rate = $results['currencies']['currency'][$i]['rate'];
				}
			}
		}
		$price_usd = ($amount / $current_currency_rate) * $usd_currency_rate;
	}else{
		$price_usd = $amount / $gatewaypaypalcurconvert;
	}
	# Enter your code submit to the gateway...
	$data = array(
		'api_key'=>$gatewayipaymuapikey,
		'product'=>'INVOICE #'.$invoiceid,
		'price'=>$amount,
		'comments'=>$description,
		'url_return'=>$systemurl.'/modules/gateways/callback/'.$gatewayipaymucallbackfile.'?method=return&id='.$invoiceid.'&log='.$gatewayipaymulog,
		'url_notify'=>$systemurl.'/modules/gateways/callback/'.$gatewayipaymucallbackfile.'?method=notify&id='.$invoiceid.'&total='.$amount.'&apikey='.$gatewayipaymuapikey.'&log='.$gatewayipaymulog,
		'url_cancel'=>$systemurl.'/modules/gateways/callback/'.$gatewayipaymucallbackfile.'?method=cancel&id='.$invoiceid.'&log='.$gatewayipaymulog,
		'paypal_enabled'=>$gatewaypaypalenabled,
		'paypal_email'=>$gatewaypaypalemail,
		'price_usd'=>$price_usd,
		'invoice_id'=>$invoiceid,
	);
	$result = ipaymu_generateurl($data);
	if($result['status']==TRUE){
		if($gatewaypaypalenabled)
		$code = "<a href='".$result["rawdata"]."'><img src='https://my.ipaymu.com/images/buttons/shopcart/01.png' alt='Bayar Sekarang' title='Bayar Sekarang' ></a>";
		else
		$code = "<a href='".$result["rawdata"]."'><img src='https://my.ipaymu.com/images/buttons/shopcart/02.png' alt='Bayar Sekarang' title='Bayar Sekarang' ></a>";
	}else{
		$code = "<p>".$result['rawdata']."</p>";
	}
	return $code;
}
function ipaymu_generateurl($data){
	// URL Payment IPAYMU
	$url = 'https://my.ipaymu.com/payment.htm';
	// Prepare Parameters
	$parameters = array(
            'key'      => $data['api_key'], // API Key Merchant / Penjual
            'action'   => 'payment',
            'product'  => $data['product'],
            'price'    => $data['price'], // Total Harga
            'quantity' => 1,
            'comments' => $data['comments'], // Optional           
            'ureturn'  => $data['url_return'],
            'unotify'  => $data['url_notify'],
            'ucancel'  => $data['url_cancel'],
            'format'   => 'json' // Format: xml / json. Default: xml 
	);
	/* Jika menggunakan Opsi Paypal
	 * ----------------------------------------------- */
	if($data['paypal_enabled']){
		$parameters = array_merge($parameters, array(
            'paypal_email'   => $data['paypal_email'],
            'paypal_price'   => number_format($data['price_usd'], 2), // Total harga dalam kurs USD
            'invoice_number' => $data['invoice_id'], // Optional
		));
	}
	/* ----------------------------------------------- */
	//print_r($parameters);
	$request = ipaymu_curl($url, $parameters);
	if($request['status']){
		$result = json_decode($request['rawdata'], true);
		if( isset($result['url']) )
		return array('status'=>TRUE, 'rawdata'=>$result['url']);
		else
		return array('status'=>FALSE, 'rawdata'=>"Request Error ". $result['Status'] .": ". $result['Keterangan']);
	}else{
		return array('status'=>FALSE, 'rawdata'=>$request['rawdata']);
	}
}
function ipaymu_cektransaksi($params, $trx_id){
	$gatewayipaymuapikey = $params['ipaymu_apikey'];
	$url = 'https://my.ipaymu.com/api/CekTransaksi.php';
	$parameters = array(
	'key'=>$gatewayipaymuapikey,
	'id'=>$trx_id,
	'format'=>'json',
	);
	$request = ipaymu_curl($url, $parameters);
	if($request['status']){
		return json_decode($request['rawdata'], true);
	}else{
		return FALSE;
	}
}
function ipaymu_curl($url, $parameters){
	$params_string = http_build_query($parameters);
	//open connection
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, count($parameters));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	//execute post
	$request = curl_exec($ch);
	if ( $request === false ) {
		$result = array('status'=>FALSE, 'rawdata'=> 'Curl Error: ' . curl_error($ch) );
	}else{
		$result = array('status'=>TRUE, 'rawdata'=> $request );
	}
	curl_close($ch);
	return $result;
}
/*
 * ipaymu_refund << ignored
 *
 */
function ipaymu_refund_none($params) {
	# Gateway Specific Variables
	$gatewayusername = $params['username'];
	$gatewaytestmode = $params['testmode'];
	# Invoice Variables
	$transid = $params['transid']; # Transaction ID of Original Payment
	$amount = $params['amount']; # Format: ##.##
	$currency = $params['currency']; # Currency Code
	# Client Variables
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$email = $params['clientdetails']['email'];
	$address1 = $params['clientdetails']['address1'];
	$address2 = $params['clientdetails']['address2'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = $params['clientdetails']['postcode'];
	$country = $params['clientdetails']['country'];
	$phone = $params['clientdetails']['phonenumber'];
	# Card Details
	$cardtype = $params['cardtype'];
	$cardnumber = $params['cardnum'];
	$cardexpiry = $params['cardexp']; # Format: MMYY
	$cardstart = $params['cardstart']; # Format: MMYY
	$cardissuenum = $params['cardissuenum'];
	# Perform Refund Here & Generate $results Array, eg:
	$results = array();
	$results["status"] = "success";
	$results["transid"] = "12345";
	# Return Results
	if ($results["status"]=="success") {
		return array("status"=>"success","transid"=>$results["transid"],"rawdata"=>$results);
	} elseif ($gatewayresult=="declined") {
		return array("status"=>"declined","rawdata"=>$results);
	} else {
		return array("status"=>"error","rawdata"=>$results);
	}
}
?>
