#!/usr/bin/php4
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
require_once(realpath(dirname(__FILE__).'/../').'/includes/common.php');

$tax_rate_sql = "SELECT value as tax_rate FROM config WHERE name = 'tax_rate'";
$tax_result = $db_conn->query($tax_rate_sql);

if(!$tax_result){
	echo("Could not get tax_rate: ".$db_conn->getLastError()."\n");
	die(1);
}
$tax_rate = 0.00;
if($row=$tax_result->fetchArray(MYSQL_ASSOC)){
	$tax_rate = $row['tax_rate'];
}

echo("Using global tax rate of $tax_rate%\n");

$il_sql = "
SELECT il.id AS id, ROUND(il.unit_price * il.quantity, 2) AS subtotal,
IFNULL(cp.tax_rate, c.value) as tax_rate
FROM invoice_lines AS il
LEFT JOIN item_codes AS ic ON ic.id = il.item_code_id
LEFT JOIN invoices AS i ON i.id = il.invoice_id
LEFT JOIN company_prices AS cp ON cp.company_id = i.company_id
AND cp.item_code_id = il.item_code_id
LEFT JOIN config AS c ON c.name = 'tax_rate'
WHERE il.item_code_id >0
AND ic.taxable = 'Y'
";

$il_result = $db_conn->query($il_sql);

if(!$il_result){
	echo("Could not get tax_rate: ".$db_conn->getLastError()."\n");
	die(1);
}

while($row=$il_result->fetchArray(MYSQL_ASSOC)){
	$update_sql = "UPDATE invoice_lines SET tax_amount = ".round($row['subtotal'] * ($row['tax_rate'] / 100), 2)." WHERE id = ".$row['id'];
	if(!$db_conn->query($update_sql)){
		echo "Error updating invoice line {$row[id]}: ".$db_conn->getLastError()."\n";
		die(3);
	}
	echo "Updated invoice line ID {$row[id]}\n";
}

?>
