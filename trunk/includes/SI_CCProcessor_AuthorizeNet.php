<?php
/**
 *
 * Copyright (C) 2003-2011 Cory Powers
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 */

class SI_CCProcessor_AuthorizeNet extends SI_CCProcessor {
	var $params = array();
	
	var $results = array();
	
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
        	
	function SI_CCProcessor_AuthorizeNet(){
		parent::SI_CCProcessor();
		
		$this->addConfigOption('username', 'Authorize.Net Username', 'Your Authorize.Net username to be used for processing credit cards', 'string');
		$this->addConfigOption('password', 'Authorize.Net Password', 'Your Authorize.Net password', 'string');
		$this->addConfigOption('test_mode', 'Test Mode', 'Enable or disable test mode for Authorize.Net', 'bool');
	}

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
		$this->params["x_Login"] = $this->getConfigValue('username');
		$this->params["x_Password"] = $this->getConfigValue('password');
		if($this->getConfigValue('test_mode')){
			$this->params["x_Test_Request"] = TRUE;
		}
     }

	function process($params){
		$this->params = array();
		$this->_setDefaultParams();
		
		if(isset($params['phone']) && !empty($params['phone'])){
			$params['phone'] = preg_replace("/^[\s\(]*(\d{3})[\)\-\s]*(\d{3})[\s\-]*(\d{4}).*$/", "($1) $2-$3", $params['phone']);
			$this->params['x_Phone'] = $params['phone'];
		}

		if((!isset($params['first_name']) || empty($params['first_name'])) || 
		   (!isset($params['last_name']) || empty($params['last_name'])) || 
		   (!isset($params['address']) || empty($params['address'])) || 
		   (!isset($params['city']) || empty($params['city']))){
			$this->error = "Error: Name and address must be provided!\n";
			return FALSE;
		}else{
			$this->params['x_First_Name'] = $params['first_name'];
			$this->params['x_Last_Name'] = $params['last_name'];
			$this->params['x_Address'] = $params['address'];
			$this->params['x_City'] = $params['city'];
		}

		if((isset($params['country']) && $params['country'] == "US") && 
		   (!isset($params['state']) || empty($params['state']) || 
		   !isset($params['zip']) || empty($params['zip']))){
			$this->error = "Error: State and zip code must be provided for United States!\n";
			return FALSE;
		}else{
			$this->params['x_Zip'] = $params['zip'];
			$this->params['x_State'] = $params['state'];
			$this->params['x_Country'] = $params['country'];		
		}

		if(isset($params['id'])){
			$this->params['x_Cust_ID'] = $params['id'];
		}else{
			$this->params['x_Cust_ID'] = '';
		}
		
		if((!isset($params['cc_number']) || empty($params['cc_number'])) || 
		   (!isset($params['cc_expiration_month']) || empty($params['cc_expiration_month'])) ||
		   (!isset($params['cc_expiration_year']) || empty($params['cc_expiration_year']))){ 
			$this->error = "Error: Credit card number and expiration must be provided";
			return FALSE;
		}else{
			$this->params['x_Card_Num'] = $params['cc_number'];
			$this->params['x_Exp_Date'] = $params['cc_expiration_month'];
			if(strlen($params['cc_expiration_year']) > 2 ){
				$this->params['x_Exp_Date'] .= substr($params['cc_expiration_year'], 2);
			}else{
				$this->params['x_Exp_Date'] .= $params['cc_expiration_year'];
			}
		}

		if(intval($params['cc_expiration_month']) < 1 || intval($params['cc_expiration_month']) > 12){
			$this->error = "Invalid expiration date (".$params['cc_expiration_month'].") specified!\n";
			return FALSE;
		}
		
		$params['amount'] = doubleval($params['amount']);
		if(!$params['amount']){
			$this->error = "Invalid amount (".$params['amount'].") specified!\n";
			return FALSE;
		}
		$this->params['x_Amount'] = number_format($params['amount'], 2);

		// Process the card
		$query_string = '';
		foreach ($this->params as $key => $value){
		    $query_string .= "$key=".urlencode($value)."&";
		}
		$query_string = substr($query_string, 0, strlen($query_string)-1);    // remove the last ampersand
		
		if(!function_exists('curl_init')){
			$this->error = "Error: Curl module is missing!";
			return false;
		}
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, 'https://secure.authorize.net/gateway/transact.dll');
		curl_setopt ($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_POST, 1);
		if($this->getConfigValue('test_mode')){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		}
		
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $query_string);
		$result = curl_exec ($ch);
		
		if (!$result){
			$this->error = "Error running curl: ".curl_error($ch);
		 	return FALSE;
		}
		curl_close ($ch);
		
		var_dump($result);
		if(stristr($result, 'The merchant login ID or password is invalid')) {
			$result = strip_tags($result);
			$result = "3,0,0,".$result;    // Bogus string with the proper error message
		}
		   
		$resArray = explode(",", $result);
		$len = count($this->resultFields);
		$j = 0;
		for($i=0; $i< $len; $i++) {
			if ($this->resultFields[$i]){
				$this->results[$this->resultFields[$i]] = $resArray[$i];
			}else {
				$j++;
				$this->results["x_custom_$j"] = $resArray[$i];
			}
		}
		
		if($this->results['x_response_code'] != 1){
			$this->error = $this->results['x_response_reason_text'];
			return false;
		}
		
		return true;
	}
	
	function getAuthCode(){
		return $this->results['x_auth_code'];
	}
}

?>