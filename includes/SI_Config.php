<?
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
class SI_Config{
	var $name, $value;

	var $error;

	function SI_Config(){
		$this->error = '';
		$this->name = '';
		$this->value = '';

	}

	function _populateData($values){
		if(is_array($values)){
			$this->name = $values[0];
			$this->value = $values[1];
		}
	}

	function escapeStrings(){
		global $db_conn;
		
		$vars = get_object_vars($this);
		foreach($vars as $key => $value){
			if(is_string($value)){
				$this->$key = $db_conn->escapeString($value);
			}
		}
	}

	function stripSlashes(){
		$vars = get_object_vars($this);
		foreach($vars as $key => $value){
			if(is_string($value)){
				$this->$key = stripcslashes($value);
			}
		}
	}

	function getLastError(){
		return $this->error;
	}

	function add(){
		global $db_conn;

		$this->escapeStrings();
		$result = $db_conn->query("INSERT INTO config (name, value)".
		  " VALUES('".$this->name."', '".$this->value."')");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Config::add() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function update(){
		global $db_conn;

		if(!isset($this->name)){
			$this->error = "SI_Config::update() : Config name not set\n";
			return FALSE;
		}

		$this->escapeStrings();
		$result = $db_conn->query("REPLACE config SET value = '".$this->value."'".
		  ", name = '".$this->name."'");
		$this->stripSlashes();
		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Config::update() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function delete($name = NULL){
		global $db_conn;

		if(!isset($name)){
			$name = $this->name;
		}

		if(!isset($name)){
			$this->error = "SI_Config::delete() : Config name not set\n";
			return FALSE;
		}

		$result = $db_conn->query("DELETE FROM config WHERE name = '$name'");

		if($result){
			return TRUE;
		}else{
			$this->error = "SI_Config::delete() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
	}

	function get($name = NULL){
		global $db_conn;

		if(!isset($name)){
			$name = $this->name;
		}

		if(!isset($name)){
			$this->error = "SI_Config::get() : Config name not set\n";
			return FALSE;
		}

		$result = $db_conn->query("SELECT  name, value".
	  " FROM config WHERE name = '$name'");
		if(!$result){
			$this->error = "SI_Config::get() : ".$db_conn->getLastError()."\n";
			return FALSE;
		}
		if($row=$result->fetchRow()){
			$this->_populateData($row);
		}else{
			$this->error = "SI_Config::get() : No data retrieved from query\n";
			return FALSE;
		}
		return TRUE;
	}

	function retrieveSet($clause = ''){
		global $db_conn;
		$result = $db_conn->query("SELECT  name, value".
		  " FROM config ".$clause);

		if(!empty($clause)){
			$clause = trim($clause);
			if(strlen($clause) > 5){
				if(strtolower(substr($clause, 0, 5)) != "where" && strtolower(substr($clause, 0, 5)) != "order")
					$clause = "WHERE ".$clause;
			}else{
				$clause = "WHERE ".$clause;
			}
		}

		if(!$result){
			$this->error = "SI_Config::retrieveSet(): ".$db_conn->getLastError()."\n";
			return FALSE;
		}

		while($row=$result->fetchRow()){
			$temp =& new SI_Config();
			$temp->_populateData($row);
			$temp->stripSlashes();
			$Config[] =& $temp;

		}
		return $Config;
	}
// BEGIN - Custom SI_Config methods
////////////////////////////////////////////////////////////
	function getAppConfig(){
		$config = new SI_Config();
		$config_items = $config->retrieveSet();
		if($config_items === FALSE || !is_array($config_items)){
			trigger_error("Could not retrieve configuration settings from database!", E_USER_ERROR);
			return FALSE;
		}
		
		$config_hash = array();
		foreach($config_items as $item){
			$config_hash[$item->name] = $item->value;
		}

		return $config_hash;
	}

	function getCurrencySymbolSelectTags($selected = null){
		$tags = "";

		$options = array( '$', '8364', '163', '165', '8355', '82', '76', '75', '78', '80' );

		foreach($options as $value){
			$sel_text = "";
			if(!is_null($selected) && $value == $selected){
				$sel_text = " SELECTED";
			}elseif(is_null($selected) && isset($GLOBALS['CONFIG']['currency_symbol']) && $GLOBALS['CONFIG']['currency_symbol'] == $value){				
				$sel_text = " SELECTED";
			}

			if($value == '$'){
				$tags .= "<OPTION VALUE=\"".$value."\"".$sel_text.">".$value."</OPTION>\n";				
			}else{
				$tags .= "<OPTION VALUE=\"".$value."\"".$sel_text.">&#".$value.";</OPTION>\n";
			}
		}
		return $tags;
	}
	
	function getCurrencyCodeSelectTags($selected = null){
		$tags = "";

		$currency_codes = array(
			"USD" => "USD United States Dollars",
			"CAD" => "CAD Canada Dollars",
			"GBP" => "GBP United Kingdom Pounds",
			"EUR" => "EUR Euro",
			"DEM" => "DEM Germany Deutsche Marks",
			"FRF" => "FRF France Francs",
			"JPY" => "JPY Japan Yen",
			
			"NLG" => "NLG Netherlands Guilders",
			"ITL" => "ITL Italy Lire",
			"CHF" => "CHF Switzerland Francs",
			"DZD" => "DZD Algeria Dinars",
			"ARS" => "ARS Argentina Pesos",
			"AWG" => "AWG Aruban Florin",			
			"AUD" => "AUD Australia Dollars",
			
			"ATS" => "ATS Austria Schillings",
			"BSD" => "BSD Bahamas Dollars",
			"BBD" => "BBD Barbados Dollars",
			"BEF" => "BEF Belgium Francs",
			"BMD" => "BMD Bermuda Dollars",
			"BRL" => "BRL Brazil Real",
			
			"BGL" => "BGL Bulgaria Leva",
			"CAD" => "CAD Canada Dollars",
			"CLP" => "CLP Chile Pesos",
			"CNY" => "CNY China Yuan",
			"RMB" => "RMB China Renminbi",
			"CYP" => "CYP Cyprus Pounds",
			
			"CZK" => "CZK Czech Republic Koruny",
			"DKK" => "DKK Denmark Kroner",
			"NLG" => "NLG Dutch (Netherlands) Guilders",
			"XCD" => "XCD Eastern Caribbean Dollars",
			"EGP" => "EGP Egypt Pounds",
			"EUR" => "EUR Euro",
			
			"FJD" => "FJD Fiji Dollars",
			"FIM" => "FIM Finland Markkaa",
			"FRF" => "FRF France Francs",
			"DEM" => "DEM Germany Deutsche Marks",
			"XAU" => "XAU Gold Ounces",
			"GRD" => "GRD Greece Drachmae",
			
			"HKD" => "HKD Hong Kong Dollars",
			"NLG" => "NLG Holland (Netherlands) Guilders",
			"HUF" => "HUF Hungary Forint",
			"ISK" => "ISK Iceland Kronur",
			"INR" => "INR India Rupees",
			"IDR" => "IDR Indonesia Rupiahs",
			
			"IEP" => "IEP Ireland Pounds",
			"ILS" => "ILS Israel New Shekels",
			"ITL" => "ITL Italy Lire",
			"JMD" => "JMD Jamaica Dollars",
			"JPY" => "JPY Japan Yen",
			"JOD" => "JOD Jordan Dinars",
			
			"KRW" => "KRW Korea (South) Won",
			"KWD" => "KWD Kuwaiti Dinar",
			"LBP" => "LBP Lebanon Pounds",
			"LUF" => "LUF Luxembourg Francs",
			"MYR" => "MYR Malaysia Ringgits",
			"MLT" => "MLT Maltese Lira",
			
			"MXN" => "MXN Mexico Pesos",
			"NAD" => "NAD Namibian Dollars",
			"NLG" => "NLG Netherlands Guilders",
			"NZD" => "NZD New Zealand Dollars",
			"NOK" => "NOK Norway Kroner",
			"PKR" => "PKR Pakistan Rupees",
			
			"XPD" => "XPD Palladium Ounces",
			"PYG" => "PYG Paraguayan Guaraníes",			
			"PHP" => "PHP Philippines Pesos",
			"XPT" => "XPT Platinum Ounces",
			"PLN" => "PLN Poland Zlotych",
			"PTE" => "PTE Portugal Escudos",
			
			"ROL" => "ROL Romania Lei",
			"RUR" => "RUR Russia Rubles",
			"SAR" => "SAR Saudi Arabia Riyals",
			"XAG" => "XAG Silver Ounces",
			"SGD" => "SGD Singapore Dollars",
			"SKK" => "SKK Slovakia Koruny",
			
			"ZAR" => "ZAR South Africa Rand",
			"KRW" => "KRW South Korea Won",
			"ESP" => "ESP Spain Pesetas",
			"XDR" => "XDR Special Drawing Rights (IMF)",
			"SDD" => "SDD Sudan Dinars",
			"SEK" => "SEK Sweden Kronor",
			
			"CHF" => "CHF Switzerland Francs",
			"TWD" => "TWD Taiwan New Dollars",
			"THB" => "THB Thailand Baht",
			"TTD" => "TTD Trinidad and Tobago Dollars",
			"TRL" => "TRL Turkey Liras",
			"AED" => "AED United Arab Emirates Dirham",			
			"GBP" => "GBP United Kingdom Pounds",
			"VEB" => "VEB Venezuela Bolivares",
			"ZMK" => "ZMK Zambia Kwacha",
			"EUR" => "EUR Euro",
			"XCD" => "XCD Eastern Caribbean Dollars",
			"XDR" => "XDR Special Drawing Right (IMF)",
			
			"XAG" => "XAG Silver Ounces",
			"XAU" => "XAU Gold Ounces",
			"XPD" => "XPD Palladium Ounces",
			"XPT" => "XPT Platinum Ounces",	
		);

		foreach($currency_codes as $code => $value){
			$sel_text = "";
			if(!is_null($selected) && $code == $selected){
				$sel_text = " SELECTED";
			}elseif(is_null($selected) && isset($GLOBALS['CONFIG']['currency_code']) && $GLOBALS['CONFIG']['currency_code'] == $code){				
				$sel_text = " SELECTED";
			}

			$tags .= "<OPTION VALUE=\"".$code."\"".$sel_text.">".$value."</OPTION>\n";
		}
		return $tags;
	}
	
	function disableItem($name){
		if(!isset($GLOBALS['SureInvoice'])){
			$GLOBALS['SureInvoice'] = array();
		}
		
		if(!isset($GLOBALS['SureInvoice']['config_settings'])){
			$GLOBALS['SureInvoice']['config_settings'] = array();
		}
		
		if(!isset($GLOBALS['SureInvoice']['config_settings']['read_only'])){
			$GLOBALS['SureInvoice']['config_settings']['read_only'] = array();
		}

		$GLOBALS['SureInvoice']['config_settings']['read_only'][$name] = true;
	}
	
	function canEdit($name){
		if(isset($GLOBALS['SureInvoice']['config_settings']['read_only'][$name]) && $GLOBALS['SureInvoice']['config_settings']['read_only'][$name] == true){
			return false;
		}
		
		return true;
	}
// END - Custom SI_Config methods 
////////////////////////////////////////////////////////////
}

