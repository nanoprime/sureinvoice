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

function selected($var1, $var2){
	if(is_array($var1) && in_array($var2, $var1)){
		return ' selected ';
	}else if(is_array($var2) && in_array($var1, $var2)){
		return ' selected ';
	}else	if($var1 === $var2){
		return ' selected ';
	}
	return '';
}

function checked($var1, $var2){
	//var_dump($var1, "\nCHECKING\n", $var2);
	if(is_array($var1) && in_array($var2, $var1)){
		return ' checked ';
	}else if(is_array($var2) && in_array($var1, $var2)){
		print("var2 is an array and checked");
		return ' checked ';
	}else	if($var1 === $var2){
		return ' checked ';
	}
	return '';
}

// Method to get string with option tags 
// for each state.
//
// Params:	$state = abbreviation of state to be selected
//
// Returns: String or NULL on error
//
function getStateTags($state = 'AZ'){
	$stateAbrv = array("AL", "AK", "AZ", "AR", "CA", "CO", "CT", "DE", "DC",
		"FL", "GA", "HI", "ID", "IL", "IN", "IA", "KS", "KY", "LA", "ME", "MD",
		"MA", "MI", "MN", "MS", "MO", "MT", "NE", "NV", "NH", "NJ", "NM", "NY",
		"NC", "ND", "OH", "OK", "OR", "PA", "RI", "SC", "SD", "TN", "TX", "UT",
		"VT", "VA", "WA", "WV", "WI", "WY");
	$stateName = array("Alabama", "Alaska", "Arizona", "Arkansas", "California",
		"Colorado", "Connecticut", "Delaware", "Dist. Of Columbia", "Florida", "Georgia",
		"Hawaii", "Idaho", "Illinois", "Indiana", "Iowa", "Kansas", "Kentucky", "Louisiana",
		"Maine", "Maryland", "Massachusetts", "Michigan", "Minnesota", "Mississippi", "Missouri",
		"Montana", "Nebraska", "Nevada", "New Hampshire", "New Jersey", "New Mexico", "New York",
		"North Carolina", "North Dakota", "Ohio", "Oklahoma", "Oregon", "Pennsylvania", 
		"Rhode Island", "South Carolina", "South Dakota", "Tennessee", "Texas", "Utah", "Vermont",
		"Virginia", "Washington", "West Virginia", "Wisconsin", "Wyoming");

	for($i = 0; $i < count($stateAbrv); $i++){
		if($state == $stateAbrv[$i]){
			$states .= "<option value='".$stateAbrv[$i]."' selected>".$stateName[$i]."</option>\n";
		}else{
			$states .= "<option value='".$stateAbrv[$i]."'>".$stateName[$i]."</option>\n";
		}
	}
	return $states;
}

// Method to strip one paramter from
// the a query string
//
// Params:	$param = parameter name
//
// Returns: String or NULL on error
//
function stripGetParam($param, $param2 = ''){
	global $_GET;
	$string = "?";
	foreach($_GET as $key => $value){
		if($key != $param && $key != $param2){
			$string .= $key."=".$value."&";
		}
	}
	return $string;
}

// Method to convert an Array to a string
// of comma seperated values
//
// Params:   $array = Array
//
// Returns:  String on NULL on error
//
function arrayToString($array){
	if(!is_array($array)){
		return NULL;
	}
	
	$numValues = count($array);
	for($i = 0; $i < $numValues; $i++){
		if($i == ($numValues-1)){
			$string .= $array[$i];
		}else{
			$string .= $array[$i].", ";
		}
	}
	
	return $string;
	
}

// Method to display error messages
//
// Params:		$msg - Message to display
//						$class - CSS Class
//
// Returns:		Error String or Empty String
function displayError($msg, $class){
	if(!$msg){
		return '';
	}else{
		return "\n<DIV CLASS=\"$class\">".nl2br($msg)."</DIV>\n";
	}
}

// Method returns NULL if variable is unset or = ''
// This should only be used on number variables
//
// Params:		$var - Variable to test
//
// Returns:		'NULL' if variable is not set
//
function numberOrNull($var){
	if(isset($var) && $var != ''){
		return $var;
	}else{
		return 'NULL';
	}
}

// Method formats a length of time based on 
// the number of seconds provided in $seconds
// 
// Params:		$seconds - length of time in seconds
//
// Returns:		'NULL' if variable is not set
//
function formatLengthOfTime($seconds){
	$hours = 0;
	$minutes = 0;
	
	if($seconds >= 3600){
		$hours = intval($seconds / 3600);
		$seconds = intval($seconds - ($hours * 3600));
	}
  if($seconds >= 60){
		$minutes = intval($seconds / 60);
		$seconds = intval($seconds - ($minutes * 60));
	}
	
	$text = $hours.":".sprintf("%02d", $minutes);
	return $text;
}

// Method to generate a password using just lowercase letters
//
// Params:		$length - length of generated password
//
// Returns:		String
//
function make_password($length){
    $vowels = 'aeiouy';
    $consonants = 'bdghjlmnpqrstvwxz';
    $password = '';
    
    $alt = time() % 2;
    srand(time());

    for ($i = 0; $i < $length; $i++) {
        if ($alt == 1) {
            $password .= $consonants[(rand() % 17)];
            $alt = 0;
        } else {
            $password .= $vowels[(rand() % 6)];
            $alt = 1;
        }
    }
    return $password;
}

function getTSFromInput($date, $time = null){
	if(empty($date)){
		return 0;
	}
	list($month, $day, $year) = split('[/\-]', $date);
	$hour = 0;
	$min = 0;
	if(!is_null($time)){
		list($hour,$min) = split("[:\.]", $time);
	}
	return mktime($hour, $min, 0, $month, $day, $year);
}
?>