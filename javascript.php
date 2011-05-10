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
session_start();

$base_path = realpath(dirname(__FILE__).'/includes/javascript/');
/**
 * You must update the serial var when
 * a change is made to any of the underlying
 * js files
 */
$serial = 7;
$etag =  md5($_REQUEST['libs'].$serial);
$offset = 3600 * 24;	

if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag){
	header("HTTP/1.1 304 Not changed");
	header("Etag: $etag");
	header('Pragma: public');
	header("Cache-Control: max-age=$offset, must-revalidate");
	exit();
}
header("Content-Type: application/x-javascript");

$js_libs = array();
if(isset($_REQUEST['libs'])){
	if(strpos(',', $_REQUEST['libs']) >= 0){
		$js_libs = explode(',', $_REQUEST['libs']);
	}else{
		$js_libs[] = $_REQUEST['libs'];
	}
}

$suffix = '-min';
if(isset($_REQUEST['debug'])){
	$suffix = '';
}

$libraries = array(
	'yui.animation' => array('yui.yahoo.yahoo', 'yui.dom.dom', 'yui.event.event', 'yui.animation.animaton'),
	'yui.autocomplete' => array('yui.yahoo.yahoo', 'yui.dom.dom', 'yui.event.event', 'yui.connection.connection', 'yui.animation.animation', 'yui.autocomplete.autocomplete'),
	'yui.dom' => array('yui.yahoo.yahoo', 'yui.dom.dom'),
	'yui.event' => array('yui.yahoo.yahoo', 'yui.event.event'),
	'yui.calendar' => array('yui.yahoo.yahoo', 'yui.event.event', 'yui.dom.dom', 'yui.calendar.calendar'),
	'yui.container' => array('yui.yahoo.yahoo', 'yui.event.event', 'yui.dom.dom', 'yui.dragdrop.dragdrop', 'yui.connection.connection', 'yui.container.container_core', 'yui.container.container'),
	'yui.ext.DomHelper' => array('yui.yahoo.yahoo', 'yui.dom.dom', 'yui.ext.yutil', 'yui.ext.DomHelper'),
	'yui.ext.DomQuery' => array('yui.yahoo.yahoo', 'yui.dom.dom', 'yui.ext.yutil', 'yui.ext.Template', 'yui.ext.DomQuery'),
	'yui.ext.BasicDialog' => array('yui.yahoo.yahoo', 'yui.dom.dom', 'yui.utilities.utilities', 'yui.ext.yutil', 'yui.ext.widgets.BasicDialog', 'yui.ext.widgets.Button', 'yui.ext.Element', 'yui.ext.widgets.Resizable'),
	'yui.ext.EventManager' => array('yui.yahoo.yahoo', 'yui.dom.dom', 'yui.event.event', 'yui.ext.yutil', 'yui.ext.EventManager')
);

//ob_start();
$loaded = array();
header("Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT");
header("Etag: $etag");
header('Pragma: public');
header("Cache-Control: max-age=$offset, must-revalidate");
foreach($js_libs as $lib){
	if(isset($libraries[$lib])){
		foreach($libraries[$lib] as $lib2){
			if(isset($loaded[$lib2])) continue;
			$file_path = $base_path.'/'.str_replace('.', '/', $lib2).$suffix.'.js';
			//print("Loading file $file_path\n");
			if(file_exists($file_path)){
				$loaded[$lib2] = $lib2;
				readfile($file_path);
			}else{
				print("\n\nalert('Could not load $file_path');\n\n");
			}
		}
		
	}else{
		if(isset($loaded[$lib])) continue;
		$file_path = $base_path.'/'.str_replace('.', '/', $lib).$suffix.'.js';
		//print("Loading file $file_path\n");
		if(file_exists($file_path)){
			$loaded[$lib] = $lib;
			readfile($file_path);
		}else{
			print("\n\nalert('Could not load $file_path');\n\n");
		}
	}
}
//ob_end_flush();
?>
