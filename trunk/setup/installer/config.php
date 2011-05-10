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
/*
 * Created on Aug 16, 2005
 *
 * Example config file for application installer
 */

/*
 * Application name setting
 */ 
$app_name = "Sureinvoice";

 /*
  * Directories that will be looked in for Test classes.
  * These directories will be traversed in the order they
  * are added here. This variable is optional and is intended
  * as a way to allow developers to use their own Test classes
  * and not have to pollute the installer dir with files
  * 
  * The Installer will always look in the $INSTALLER_BASE/tests
  * directory for Test classes last
  */
$test_dirs = array();


 /*
  * Directories that will be looked in for Action classes.
  * These directories will be traversed in the order they
  * are added here. This variable is optional and is intended
  * as a way to allow developers to use their own Actions and
  * not have to pollute the installer dir with files
  * 
  * The Installer will always look in the $INSTALLER_BASE/actions
  * directory for Actions last
  */
$action_dirs = array();

/*
 * The version file defines all the known versions of the application
 * and the Tests and Actions required to go from the previous version
 * to the defined version.
 */
$version_file = realpath(dirname(__FILE__)).'/versions.php';

/*
 * We need to ensure the class for determining the current version
 * is available here.
 */
require_once(realpath(dirname(__FILE__)).'/SureInvoiceDetection.php');
 
 /*
  * Define the class name for the version detection class. This
  * class must extend the VersionCheck class included with the
  * installer. The class must override the getCurrentVersion()
  * method from the VersionCheck class.
  */
$version_detection_class = 'SureInvoiceDetection';

/*
 * Writable directory for smarty compile dir
 */
$writable_dir = realpath(dirname(__FILE__)).'/tmp';

/*
 * Template directory
 */
$template_dir = realpath(dirname(__FILE__)).'/templates';

?>
