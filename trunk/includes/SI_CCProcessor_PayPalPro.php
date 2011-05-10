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

class SI_CCProcessor_PayPalPro extends SI_CCProcessor {
	var $params = array();
	
	var $results = array();
	
	function SI_CCProcessor_PayPalPro(){
		parent::SI_CCProcessor();
		
		$this->addConfigOption('username', 'PayPal Username', 'Your PayPal API username to be used for processing credit cards', 'string');
		$this->addConfigOption('password', 'PayPal Password', 'Your PayPal API password', 'string');
		$this->addConfigOption('signature', 'PayPal Signature', 'Your PayPal API Signature', 'string');
		$this->addConfigOption('sandbox', 'Sandbox', 'True to use the PayPal sandbox servers', 'bool');
	}

	function _setDefaultParams() {
		$this->params["VERSION"] = "2.3";
		$this->params["USER"] = $this->getConfigValue('username');
		$this->params["PWD"] = $this->getConfigValue('password');
		$this->params["SIGNATURE"] = $this->getConfigValue('signature');
		$this->params["PAYMENTACTION"] = 'Sale';
		$this->params['IPADDRESS'] = $_SERVER['REMOTE_ADDR'];
		$this->params['METHOD'] = 'DoDirectPayment';
     }

	function process($params){
		$this->params = array();
		$this->_setDefaultParams();
		
		if((!isset($params['first_name']) || empty($params['first_name'])) || 
		   (!isset($params['last_name']) || empty($params['last_name'])) || 
		   (!isset($params['address']) || empty($params['address'])) || 
		   (!isset($params['city']) || empty($params['city']))){
			$this->error = "Error: Name and address must be provided!\n";
			return FALSE;
		}else{
			$this->params['FIRSTNAME'] = $params['first_name'];
			$this->params['LASTNAME'] = $params['last_name'];
			$this->params['STREET'] = $params['address'];
			$this->params['CITY'] = $params['city'];
		}

		if(!isset($params['country'])){
			$params['country'] = 'US';
		}
		if((isset($params['country']) && $params['country'] == "US") && 
		   (!isset($params['state']) || empty($params['state']) || 
		   !isset($params['zip']) || empty($params['zip']))){
			$this->error = "Error: State and zip code must be provided for United States!\n";
			return FALSE;
		}else{
			$this->params['ZIP'] = $params['zip'];
			$this->params['STATE'] = $params['state'];
			$this->params['COUNTRYCODE'] = $params['country'];		
		}

		if((!isset($params['cc_number']) || empty($params['cc_number'])) || 
		   (!isset($params['cc_expiration_month']) || empty($params['cc_expiration_month'])) ||
		   (!isset($params['cc_expiration_year']) || empty($params['cc_expiration_year']))){ 
			$this->error = "Error: Credit card number and expiration must be provided";
			return FALSE;
		}else{
			$this->params['ACCT'] = $params['cc_number'];
			$this->params['EXPDATE'] = $params['cc_expiration_month'].$params['cc_expiration_year'];
		}

		if(intval($params['cc_expiration_month']) < 1 || intval($params['cc_expiration_month']) > 12){
			$this->error = "Invalid expiration date (".$params['cc_expiration_month'].") specified!\n";
			return FALSE;
		}
		
		if(!isset($params['cc_type']) || empty($params['cc_type'])){
			$this->error = "Error: Credit Card Type required";
			return FALSE;
		}else{
			if($params['cc_type'] == 'American Express'){
				$params['cc_type'] = 'Amex';
			}
			$this->params['CREDITCARDTYPE'] = $params['cc_type'];
		}
		
		$params['amount'] = doubleval($params['amount']);
		if(!$params['amount']){
			$this->error = "Invalid amount (".$params['amount'].") specified!\n";
			return FALSE;
		}
		$this->params['AMT'] = number_format($params['amount'], 2);

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
		
		var_dump($this->params);
		$url = 'https://api-3t.paypal.com/nvp';
		if($this->getConfigValue('sandbox')){
			$url = 'https://api-3t.sandbox.paypal.com/nvp';
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 45);
		if($this->getConfigValue('sandbox')){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		}
		
		curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
		$result = curl_exec($ch);
		
		if (!$result){
			$this->error = "Error running curl: ".curl_error($ch);
		 	return FALSE;
		}
		curl_close($ch);
		
		var_dump($result);
		$result_entries = explode('&', $result);
		for($i=0; $i<count($result_entries); $i++){
			list($name, $value) = explode('=', $result_entries[$i]);
			$this->results[urldecode($name)] = urldecode($value);
		}
		var_dump($this->results);
		
		if(strtolower($this->results['ACK']) != 'success' && strtolower($this->results['ACK']) != 'successwithwarning'){
			$this->error = $this->results['L_LONGMESSAGE0'];
			return false;
		}
		
		return true;
	}
	
	function getAuthCode(){
		return $this->results['TRANSACTIONID'];
	}
}

?>