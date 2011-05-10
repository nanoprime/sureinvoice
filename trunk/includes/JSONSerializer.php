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

class JSONSerializer{

	/**
	 * Uses stdClass objects to create a complete 
	 * object hierarchy for the provided value. The
	 * result will still need to go through a call
	 * to json_encode before being sent.
	 * 
	 * Most notably this method handles creating the
	 * appropriate classes and filling in the data 
	 * fields.
	 *
	 * @param varies $value
	 * @return stdClass
	 */
	public static function serialize($value){
		$returnValue = new stdClass();
		if(is_object($value)){
			$returnValue = $value;
			$returnValue->__class__ = get_class($value);
		}elseif(is_array($value)){
			foreach($value as $key => $data){
				$returnValue->$key = JSONSerializer::serialize($data);
			}
		}else{
			$returnValue = $value;
		}
		
		return $returnValue;
	}
	
	public static function deserialize($object){
		$returnValue = new stdClass();
		if(isset($object->__class__)){
			if(!class_exists($object->__class__)){
				require_once(realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.$object->__class__.'.php');
			}
			$returnValue = new $object->__class__;
		}
		if(is_array($object)){
			foreach($object as $key => $value){
				$returnValue->$key = JSONSerializer::deserialize($value);
			}
		}elseif(is_object($object)){
			foreach(get_class_vars($object) as $key){
				$returnValue->$key = $object->$key;
			}
		}else{
			return $object;
		}
		
		return $returnValue;
	}
}

?>
