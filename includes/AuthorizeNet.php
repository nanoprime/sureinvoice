<?
/////////////////////////////////////////////////////////////
//
//  AuthorizenetClass
//  file: authorizenet.class.php
//  last modified: 7/28/2001
//  prerequisites: PHP 4.0.2+, CURL, SSL
//  version: 1.0
//    author: Austin Butler, Corndog Software
//  url: http://www.corndogsoftware.com/
//  license: GPL
//
//    Updated: 5/9/2003
//    Version: 1.1
//    Author: Matt Babineau
//    URL: http://www.criticalcode.com
//    Updates: Added compatibility for Auth.Net v3.1
//                     changed _processResult() function
//
/////////////////////////////////////////////////////////////

/*
// EXAMPLE USAGE
include("authorizenet.php");

$ac = new AuthorizenetClass();
$ac->setParameter("x_Login", "myaccount");
//$ac->setParameter("x_Test_Request", "TRUE");
$ac->setParameter("x_First_Name", "Austin");
$ac->setParameter("x_Last_Name", "Butler");
$ac->setParameter("x_Amount", "12.00");
$ac->setParameter("x_Card_Num", "4111111111111111");
$ac->setParameter("x_Card_Code", "456");
$ac->setParameter("x_Exp_Date", "072005");
$ac->setParameter("x_Invoice_Num", "12321398083");
$ac->setParameter("x_Address", "123 Fake St");
$ac->setParameter("x_City", "Hermosa Beach");
$ac->setParameter("x_State", "CA");
$ac->setParameter("x_Zip", "90001");

$result_code = $ac->process();    // 1 = accepted, 2 = declined, 3 = error
$result_array = $ac->getResults();    // return results array

foreach($result_array as $key => $value) {
    print "$key: $value<br>\n";
}
*/

class Authorizenet {
    var $vendor;    // Credit card processor name
    var $postURL;    // URL to post to

    // Set default values.  Changes these with $ac->setParameter(string key, mixed value)
    var $params = Array();
    var $results = Array();

    var $log_response = FALSE; // Set this to TRUE to enable loggin of responses
    var $response_log = "/some/path/to/file.log";
	var $_error_msg = '';
	
	var $username = '';
	var $password = '';
	var $testmode = FALSE;

    // Constructor: Defaults to authorizenet.  Also accepts planetpayment and quickcommerce
    function Authorizenet($username, $password, $test_mode = FALSE, $vendor = "authorizenet") {
        if (!curl_version()) die ("ERROR (AuthrorizenetClass): cURL not installed");
        $this->vendor = $vendor;
        $this->postURL = $this->vendorPostURLs[$vendor];
        $this->username = $username;
        $this->password = $password;
        if($test_mode === TRUE)
    		$this->testmode = TRUE;
        $this->_setDefaultParams();
    }

    // setPostURL(string url): Set the URL to post to.
    function setPostURL($url) {
        $this->postURL = $url;
    }

    // setParemeter(string key, string value):  Used to set each name/value pair to be sent
    function setParameter($key, $value) {
        $this->params[$key] = $value;
    }

    // process(): Submit to gateway
    function process() {
        if (!$this->params['x_Login'])
            die("Error (AuthorizenetClass): x_Login is a required field");
        if (!$this->params['x_Card_Num'])
            die("Error (AuthorizenetClass): x_Card_Num is a required field");
        if (!$this->params['x_Exp_Date'])
            die("Error (AuthorizenetClass): x_Exp_Date is a required field");

        $qString = "";
        while(list($key, $val) = each($this->params))
            $qString .= "$key=".urlencode($val)."&";
        $qString = substr($qString, 0, strlen($qString)-1);    // remove the last ampersand

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $this->postURL);
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $qString);
        $result = curl_exec ($ch);

        if (!$result){
        	$this->_error_msg = "Error running curl: ".curl_error($ch);
         	return 0;
        }
        curl_close ($ch);
            
        if($this->log_response){
            $string =	"[".date("m-d-Y H:i:s")."] ".$result."\n";
            @error_log($string, 3, $this->response_log);
        }
        return $this->_processResult($result);
    }

    // getResults(): Returns the results array
		function getResults() {
			return $this->results;
		}

    // reset(): Call before beginning a new transaction
	function reset() {
		$this->results = Array();
		$this->params = Array();
		$this->_setDefaultParams();
	}

	function getLastError(){
		return $this->_error_msg;
	}

    ///////////////////////////////////////////////////////
    // Internal Functions
    //

    // _processResults(string $results): Internal Function.  Creates the results array
    function _processResult($result) {
    	var_dump($result);
        if ($result == 'Invalid Merchant Login or Account Inactive') {
            $result = "3,0,0,".$result;    // Bogus string with the proper error message
        }
           
        $resArray = explode(",", $result);
		$len = count($this->resultFields);
        $j = 0;
        for($i=0; $i< $len; $i++) {
            if ($this->resultFields[$i])
                $this->results[$this->resultFields[$i]] = $resArray[$i];
            else {
                $j++;
                $this->results["x_custom_$j"] = $resArray[$i];
            }
        }
				
		if($this->results['x_response_code'] != 1){
			$this->_error_msg = $this->results['x_response_reason_text'];
		}
        return $this->results['x_response_code'];
    }
     
    // _setDefaultParams(): Internal Function.  Reset the params array.
    function _setDefaultParams() {
        $this->params["x_Version"] = "3.1";
        $this->params["x_Delim_Data"] = "TRUE";
        //$this->params["x_Echo_Data"] = "TRUE";
        //$this->params["x_ADC_URL"] = "FALSE";
        $this->params["x_Type"] = "AUTH_CAPTURE";
        $this->params["x_Method"] = "CC";
		//$this->params["x_Tran_Key"] = "somekey";
		$this->params["x_Delim_Char"] = ", ";
		$this->params["x_Encap_Char"] = "";
		$this->setParameter("x_Login", $this->username);
		$this->setParameter("x_Password", $this->password);
		if($this->testmode)
			$this->setParameter("x_Test_Request", TRUE);

     }
		 
	/**
	* AuthorizeNet::setAddress()
	* 
	* Set the address and name associated with this 
	* transaction. The name must be entered exactly as
	* it appears on the credit card. The address must
	* be the same as the billing address for the credit 
	* card.
	* 
	* @param string $id Customer ID (Internal use)
	* @param string $first_name First name
	* @param string $last_name Last name
	* @param string $phone Phone Number
	* @param string $address1 Address line 1
	* @param string $city City
	* @param string $state State
	* @param string $zip Zip Code
	* @return bool 
	*/
	function setAddress($id, $first_name, $last_name, $phone, $address, $city, $state, $zip, $country = "US"){
		if(!empty($phone))
			$phone = preg_replace("/^[\s\(]*(\d{3})[\)\-\s]*(\d{3})[\s\-]*(\d{4}).*$/", "($1) $2-$3", $phone);

		if(empty($first_name) || empty($last_name) || empty($address) ||
				empty($city)){
			$this->_error_msg = "Error: Name and address must be provided!\n";
			return FALSE;
		}

		if($country == "US" && (empty($state) || empty($zip))){
			$this->_error_msg = "Error: State and zip code must be provided for United States!\n";
			return FALSE;
		}

		$this->setParameter('x_Cust_ID', $id);
		$this->setParameter('x_First_Name', $first_name);
		$this->setParameter('x_Last_Name', $last_name);
		$this->setParameter('x_Address', $address);
		$this->setParameter('x_City', $city);
		$this->setParameter('x_State', $state);
		$this->setParameter('x_Zip', $zip);
		$this->setParameter('x_Country', $country);
		$this->setParameter('x_Phone', $phone);

		return TRUE;
	}

	/**
	* AuthorizeNet::setCC()
	* 
	* Method used to set the credit card information 
	* for this transaction.
	* 
	* @param string $number The credit card number
	* @param string $type The type of credit card, one of the following: VISA, MASTERCARD, AMEX or DISCOVER
	* @param string $exp The card expiration date in MMYY format, e.g. 0206 for Feb. 2006
	* @param string $amount The amount to charge to the card
	* @return bool 
	*/
	function setCC($number, $exp, $amount){
		if(empty($number) || empty($exp)){
			$this->_error_msg = "Empty parameter passed to setCC()\n";
			return FALSE;
		}

		if(strlen($exp) != 4 || !preg_match("/^\d{4}$/", $exp)){
			$this->_error_msg = "Invalid expiration date ($exp) specified!\n";
			return FALSE;
		}
		
		$amount = doubleval($amount);
		if(!$amount){
			$this->_error_msg = "Invalid amount ($amount) specified!\n";
			return FALSE;
		}
		$amount = number_format($amount, 2);

		$this->setParameter('x_Card_Num', $number);
		$this->setParameter('x_Exp_Date', $exp);
		$this->setParameter('x_Amount', $amount);
		return TRUE;
	}
		 
    ///////////////////////////////////////////////////////
    // Associative arrays containing matching data

    // holds vendor/url pairls
    var $vendorPostURLs = Array (
        'authorizenet'    =>    'https://secure.authorize.net/gateway/transact.dll',
        'planetpayment'    =>    'https://secure.planetpayment.com/gateway/transact.dll',
        'quickcommerce'    =>    'https://secure.quickcommerce.net/gateway/transact.dll');
         
    // Array of response names.  Used in the results array.
    var $resultFields = Array(
        "x_response_code",
        "x_response_subcode",
        "x_response_reason_code",
        "x_response_reason_text",
        "x_auth_code",
        "x_avs_code",
        "x_trans_id",
        "x_invoice_num",
        "x_description",
        "x_amount",
        "x_method",
        "x_type",
        "x_cust_id",
        "x_first_name",
        "x_last_name",
        "x_company",
        "x_address",
        "x_city",
        "x_state",
        "x_zip",
        "x_country",
        "x_phone",
        "x_fax",
        "x_email",
        "x_ship_to_first_name",
        "x_ship_to_last_name",
        "x_ship_to_company",
        "x_ship_to_address",
        "x_ship_to_city",
        "x_ship_to_state",
        "x_ship_to_zip",
        "x_ship_to_country",
        "x_tax",
        "x_duty",
        "x_freight",
        "x_tax_exempt",
        "x_po_num",
        "x_md5_hash",
        "x_card_code");
}
?>
