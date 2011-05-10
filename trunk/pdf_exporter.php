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
require_once('includes/common.php');
require_once('includes/SI_Invoice.php');
require_once('includes/SI_Company.php');
require_once('includes/SI_PDFInvoice.php');

if(isset($_SERVER['REQUEST_METHOD'])){
	die("Script can not be accessed through a web client.");
}

$params = $_SERVER['argv'];
$script_name = array_shift($params);
$start_date = 0;
$end_date = 0;
$output_directory = realpath(dirname(__FILE__));
for($i = 0; $i < count($params); $i++){
	switch ($params[$i]){
		case '-s':
			$i++;
			$start_date = strtotime($params[$i]);
			break;
			
		case '-e':
			$i++;
			$end_date = strtotime($params[$i]);
			break;
			
		case '-d':
			$i++;
			$output_directory = $params[$i];
			break;
			
		case '-h':
			show_help();
			exit(1);
	}
}

function show_help(){
	global $script_name;
	print("\n$script_name [-s MM/DD/YYY] [-e MM/DD/YYYY] [-d /output/dir]\n");
	print("\t-s\tSpecify the start date\n");	
	print("\t-e\tSpecify the end date\n");	
	print("\t-d\tSpecify the output dir, defaults to current directory\n");
	print("\n");	
}

print("Writing output to $output_directory\n");
if(($start_date > 0 && $end_date == 0) ||
  ($end_date > 0 && $start_date == 0)){
	print("You must provide both a start and an end date\n");
	exit(2);
}

if($start_date > 0){
	print("Looking up invoices between ".date('m/d/Y', $start_date)." and ".date('m/d/Y', $end_date).".\n");
}

$invoice = new SI_Invoice();
$invoices = $invoice->retrieveSet("WHERE `timestamp` BETWEEN $start_date AND $end_date");
if($invoices === FALSE){
  print("Error retreiving list of invoices!\n");
  print($invoice->getLastError()."\n");
  exit(3);
}

print("Exporting ".count($invoices)." invoices.\n");
foreach($invoices as $invoice){
	if(!is_array($invoice->_lines) || count($invoice->_lines) == 0){
		print("Skipping invoice {$invoice->id}.\n");
		continue;
	}

	$pdf_file = $invoice->getPDF(FALSE);
	if($pdf_file === FALSE){
		print("Error creating PDF invoice.\n");
		print($invoice->getLastError()."\n");
		exit(4);	
	}

	$filename = "invoice_".$invoice->id.'.pdf';
	$fh = fopen($output_directory.DIRECTORY_SEPARATOR.$filename, 'w');
	if($fh == false){
		print("Error opening output file ".$output_directory.DIRECTORY_SEPARATOR.$filename." for writing.\n");
		exit(5);
	}
	fwrite($fh, $pdf_file);
	fclose($fh);
}

?>