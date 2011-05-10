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
 * SureInvoice version file for application installer
 */
$base_path = realpath(dirname(__FILE__).'/../');

$versions = new VersionSet();
$versions->collectData('db_user', 'Database Username', 'text', 'root');
$versions->collectData('db_password', 'Database Password', 'text', '');
$versions->collectData('db_server', 'Database Server', 'text', 'localhost');
$versions->collectData('db_port', 'Database Port', 'text', '3306');
$versions->collectData('db_database', 'Database Name', 'text', '');

// 0.0 Release (All Required Tests and Actions)
$version_0_0 = new Version('0.0');
$version_0_0->addTest('PHPVersionOver', array('4.3.0'));
$version_0_0->addTest('PHPExtension', array('mysql', 'curl'));
$version_0_0->addTest('WritableLocation', array($base_path.'/includes/global_config.php', $base_path.'/installer/tmp'));
$version_0_0->addTest('MysqlVersionOver', array(
'username_field' => 'db_user',
'password_field' => 'db_password',
'server_field' => 'db_server',
'port_field' => 'db_port',
'version' => '4.1.0'));
$version_0_0->addAction('AcceptText', array($base_path.'/LICENSE'));
$version_0_0->addAction('ReplaceString', array(
'message' => "Saved database configuration information!",
'files' => array($base_path.'/includes/global_config.php.dist' => $base_path.'/includes/global_config.php'),
'fields' => array(
	'INSTALL_DB_DATABASE' => 'db_database',
	'INSTALL_DB_SERVER' => 'db_server',
	'INSTALL_DB_USERNAME' => 'db_user',
	'INSTALL_DB_PASSWORD' => 'db_password'
	))
);
$versions->add($version_0_0);

// 0.2 Release
$version_0_2 = new Version('0.2');
$version_0_2->addAction('SQLFile', array(
'username_field' => 'db_user',
'password_field' => 'db_password',
'server_field' => 'db_server',
'port_field' => 'db_port',
'db_field' => 'db_database',
'files' => array($base_path.'/setup/sureinvoice.sql')));

$versions->add($version_0_2);

// 0.3 Release
$version_0_3 = new Version('0.3');
$version_0_3->addAction('SQLFile', array(
'username_field' => 'db_user',
'password_field' => 'db_password',
'server_field' => 'db_server',
'port_field' => 'db_port',
'db_field' => 'db_database',
'files' => array($base_path.'/setup/update-0.2-0.3.sql')));
$versions->add($version_0_3);

// 0.4 Release
$version_0_4 = new Version('0.4');
$version_0_4->addAction('SQLFile', array(
'username_field' => 'db_user',
'password_field' => 'db_password',
'server_field' => 'db_server',
'port_field' => 'db_port',
'db_field' => 'db_database',
'files' => array($base_path.'/setup/update-0.3-0.4.sql')));
$versions->add($version_0_4);

// 1.0 Release
$version_1_0 = new Version('1.0');
$version_1_0->addAction('SQLFile', array(
'username_field' => 'db_user',
'password_field' => 'db_password',
'server_field' => 'db_server',
'port_field' => 'db_port',
'db_field' => 'db_database',
'files' => array($base_path.'/setup/update-0.4-0.5.sql')));
$version_1_0->addAction('SQLOptions', array(
'username_field' => 'db_user',
'password_field' => 'db_password',
'server_field' => 'db_server',
'port_field' => 'db_port',
'db_field' => 'db_database',
'files' => array($base_path.'/setup/sureinvoice_initial_data.sql')));
$version_1_0->addAction('ReplaceString', array(
'message' => "Saved database configuration information!",
'files' => array($base_path.'/includes/global_config.php.dist' => $base_path.'/includes/global_config.php'),
'fields' => array(
	'INSTALL_DB_DATABASE' => 'db_database',
	'INSTALL_DB_SERVER' => 'db_server',
	'INSTALL_DB_USERNAME' => 'db_user',
	'INSTALL_DB_PASSWORD' => 'db_password'
	))
);
$versions->add($version_1_0);
?>
