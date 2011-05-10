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
require_once("common.php");
require_once("SI_Invoice.php");
require_once("ros_pdf/class.ezpdf.php");

/**
 *  Class to generate pdf invoice.
 *
 */
class SI_PDFInvoice {

	/**	Invoice number
   	 *
	 *	@var string Invoice Number
	 */
	var $number;

	/**	invoice timestamp
   	 *
	 *	@var int timestamp, defaults to time()
	 */
	var $timestamp;

	/**	Invoice terms
   	 *
	 *	@var string Invoice terms
	 */
	var $terms;

	/**	object to hold the created pdf
   	 *
	 *	@var object
	 */
	var $pdf;
	
	/**	company information, must include the following keys unless
   	 *  otherwise noted. Optional keys will be used if present and
   	 *  not empty
   	 *
   	 *  name = Company name
   	 *  address1 = First address line for the company, optional
   	 *  address2 = Second address line for company, optional
   	 *  address3 = Third address line for company, optional, usually city, state & zip
   	 *  phone1 = First company phone number
   	 *  phone2 = Second company phone number
   	 *  fax = Company fax number
   	 *  email = Company email address
   	 *  url = Company URL
   	 *  logo = Company Logo file, Only JPEG is supported
   	 *  logo_height = Logo file height, required if logo is provide
   	 *  logo_width = Logo file width, required if logo is provided
   	 *
 	 *	@var array
	 */
	var $company;

	/**	client information, must include the a name key, all other fields are used
   	 *  if present and not empty
   	 *
   	 *  name = The Client's Full Name
   	 *  address1 = First address line for a client
   	 *  address2 = Second address line for  a client
   	 *  address3 = Third address line for a client, usually city, state & zip
   	 *  phone1 = First client phone number
   	 *  phone2 = Second client phone number
   	 *  fax = Client fax number
   	 *
	 *	@var array
	 */
	var $client;
	
	/**	An array containing another array for each line item in the
   	 *  invoice, each line item must include the following keys except total
   	 *
   	 *  quantity - The quantity of this line
   	 *  description - Textual description for this line
   	 *  unit_price - The unit price for this line
   	 *  total - The total cost for this line, defaults to round(quantity * unit_price, 2)
   	 *
	 *	@var array
	 */
	var $lines;

	/**	Textual message describing the last error
   	 *
	 *	@var string Current error message
	 */
	var $error;

	/**	Total invoice amount, this is calculated based on line totals
   	 *
	 *	@var float Total amount
	 */
	var $total;

	/**	SubTotal invoice amount, this is calculated based on line subtotals
   	 *
	 *	@var float Sub-Total amount
	 */
	var $subtotal;

	/**	
	 * Total tax amount, this is calculated based on line tax amounts
   	 *
	 *	@var float Tax amount
	 */
	var $tax_amount;

	/**	
	 * Total amount of payments made on this invoice
   	 *
	 *	@var float Payment total amount
	 */
	var $payment_total;

	/**
	 *	Constructor take argument of the invoice number and timestamp
   	 *
	 *	@param number The invoice number
	 *	@param company Array containing company information @see company
	 *	@param client Array containing client information @see client
	 *	@param lines Array containing invoice line items @see lines
	 *	@param timestamp The invoice timestamp, defaults to time()
	 */
	function SI_PDFInvoice($number, $company, $client, $lines, $terms, $timestamp = 0, $payment_total = 0.00) {

		if(empty($number))
		  $this->error = "Invalid invoice number supplied";
		
		if(!is_array($company))
		  $this->error = "You must provide an array for the company information";
		
		if(!is_array($client))
		  $this->error = "You must provide an array for the client information";
		
		if(!is_array($lines))
		  $this->error = "You must provide an array for the line items";
		
		foreach($lines as $line){
			if(!isset($line['quantity']) ||
			   !isset($line['description']) ||
			   !isset($line['unit_price'])){
				$this->error = "Invalid line structure";
				break;
			}
		
			if(!isset($line['subtotal']))
				$line['subtotal'] = number_format(round($line['quantity'] * $line['unit_price'], 2), 2);
				
			if(!isset($line['tax_amount']))
				$line['tax_amount'] = number_format(round(($line['tax_rate'] / 100) * $line['subtotal']));
			
			if(!isset($line['total']))
				$line['total'] = number_format(round($line['tax_amount'] + $line['subtotal']));
			
		}
		
		if(intval($timestamp) <= 0)
			$timestamp = time();
		
		$this->number = $number;
		$this->company = $company;
		$this->client = $client;
		$this->lines = $lines;
		$this->terms = $terms;
		$this->timestamp = $timestamp;
		$this->payment_total = $payment_total;
		
		if(empty($error)){
			$this->pdf = new Cezpdf("letter","portrait");
			$this->pdf_init();
		}
	}
	
	function pdf_init() {
		
		$this->pdf->ezSetMargins(50,70,50,50);
		$fontfile = dirname(__FILE__) . '/ros_pdf/fonts/Helvetica.afm';
		if (!file_exists($fontfile)) {
			$this->error = "Font file $fontfile does not exist\n";
			return FALSE;
		}
		$mainFont = $fontfile;
		$euro_diff = array(126=>'Euro'); // Replace ~ with euro symbol
		$this->pdf->selectFont($mainFont, array('encoding'=>'WinAnsiEncoding','differences'=>$euro_diff));

		// Add the company logo if provided
		if(isset($this->company['logo'])){
			if(!is_readable($this->company['logo'])){
				$this->error = "Could not read logo file ".$this->company['logo'];
				return FALSE;
			}
      
			if(!isset($this->company['logo_width']) || $this->company['logo_width'] <= 0){
				$this->error = "logo_width is not set or valid";
				return FALSE;
			}
			
			if(!isset($this->company['logo_height']) || $this->company['logo_height'] <= 0){
				$this->error = "logo_height is not set or valid";
				return FALSE;
			}

			$image = $this->company['logo'];
			$this->pdf->ezImage($image,$pad = 0,$width = 0,$resize = 'none',$just = 'left',$border = '');
			$this->pdf->ezSetDy($this->company['logo_height']);
		}

		// Add the company name
		$company_text = "";
		if(isset($this->company['name']) && !empty($this->company['name'])){
			$company_text .= $this->company['name'] . "\n";
		}
		
		if (!empty($company_text))
			$this->pdf->ezText($company_text,12,array('justification'=>'right'));

		// Add the company address and contact text
		$address_text = "";
		if(isset($this->company['address1']) && !empty($this->company['address1'])){
			$address_text .= $this->company['address1'] . "\n";
		}
		if(isset($this->company['address2']) && !empty($this->company['address2'])){
			$address_text .= $this->company['address2'] . "\n";
		}
		if(isset($this->company['address3']) && !empty($this->company['address3'])){
			$address_text .= $this->company['address3'] . "\n";
		}
		if(isset($this->company['phone1']) && !empty($this->company['phone1'])){
			$address_text .= $this->company['phone1'] . "\n";
		}
		if(isset($this->company['phone2']) && !empty($this->company['phone2'])){
			$address_text .= $this->company['phone2'] . "\n";
		}
		if(isset($this->company['fax']) && !empty($this->company['fax'])){
			$address_text .= "Fax: ".$this->company['fax'] . "\n";
		}
		if(isset($this->company['email']) && !empty($this->company['email'])){
			$address_text .= $this->company['email'] . "\n";
		}
		if(isset($this->company['url']) && !empty($this->company['url'])){
			$address_text .= $this->company['url'] . "\n";
		}
		if (!empty($address_text))
			$this->pdf->ezText($address_text,10,array('justification'=>'right'));

		$this->pdf->ezText("Invoice",20,array('justification'=>'left'));
		$this->pdf->ezSetDy(-10);
	}
	
	function setup_client() {
		$text = $this->client['name'] . "\n";
		if(isset($this->client['address1']) && !empty($this->client['address1'])){
			$text .= $this->client['address1'] . "\n";
		}
		if(isset($this->client['address2']) && !empty($this->client['address2'])){
			$text .= $this->client['address2'] . "\n";
		}
		if(isset($this->client['address3']) && !empty($this->client['address3'])){
			$text .= $this->client['address3'] . "\n";
		}
		if(isset($this->client['phone1']) && !empty($this->client['phone1'])){
			$text .= $this->client['phone1'] . "\n";
		}
		if(isset($this->client['phone2']) && !empty($this->client['phone2'])){
			$text .= $this->client['phone2'] . "\n";
		}
		if(isset($this->client['fax']) && !empty($this->client['fax'])){
			$text .= "Fax: ".$this->client['fax'] . "\n";
		}
		$billing_text['bill'] = array("Bill to:" => $text);
		
		$this->pdf->ezTable($billing_text,null,'',array('xPos' => 'left', 'xOrientation' => 'right', 'width' => '150'));
		$this->pdf->ezSetDy(-10);
	}
	
	function setup_invoice_details() {
		$this->pdf->ezSetDy(100);
		$billing_text['bill'] = array("Invoice Date" => date("m/d/Y", $this->timestamp), "Terms" => $this->terms, "Invoice Number" => $this->number);

		$this->pdf->ezTable($billing_text,null,'',array('xPos' => 'right', 'xOrientation' => 'left',
														'cols' =>
														array('Invoice Date'=>array("width" => "70"),
															'Terms'=>array("width" => "70"),
															'Invoice Number'=>array("width" => "70")
														)
													)
		);
		$this->pdf->ezSetDy(-80);
	}
	
	function setup_lineitems() {
		$line_text = array();
		$this->total = 0.00;
		foreach($this->lines as $line){
			if(!empty($line['item_code']) && !empty($line['quantity'])){
				$line_text[] = array("Item Code" => $line['item_code'], "Qty" => $line['quantity'], "Description" => $line['description'], "Unit Price" => $line['unit_price'], "Tax" => ($line['tax_amount'] == 0 ? 'N/A' : $line['tax_amount']), "Total" => number_format($line['total'],2));
				$this->subtotal += $line['subtotal'];
				$this->tax_amount += $line['tax_amount'];
				$this->total += $line['total'];
			}else{
				// Note line
				$line_text[] = array("Item Code" => $line['item_code'], "Qty" => $line['quantity'], "Description" => $line['description'], "Unit Price" => $line['unit_price'], "Tax" => '', "Total" => '');
			}
		}
		
		$this->pdf->ezTable($line_text,null,'',array('xPos' => 'left', 'xOrientation' => 'right',
				'fontSize' => 8, 
				'colGap' => 2, 
				'cols' =>
					array('Item Code'=>array("width" => "45"),
						'Qty'=>array("width" => "37"),
						'Description'=>array("width" => "284"),
						'Unit Price'=>array("width" => "55"),
						'Tax'=>array("width" => "42"),
						'Total'=>array("width" => "50")
					)
				)
		);
		$this->pdf->ezSetDy(-10);
	}

	function setup_total() {

		$total[] = array("Total" => "Subtotal", "Amount" => SureInvoice::getCurrencySymbolPDF().number_format($this->subtotal, 2));
		$total[] = array("Total" => "Tax Amount", "Amount" => SureInvoice::getCurrencySymbolPDF().number_format($this->tax_amount, 2));
		$total[] = array("Total" => "Total", "Amount" => SureInvoice::getCurrencySymbolPDF().number_format($this->total, 2));
		if($this->payment_total > 0){
			$total[] = array("Total" => "Total Payments", "Amount" => "-".SureInvoice::getCurrencySymbolPDF().number_format($this->payment_total, 2));
			$total[] = array("Total" => "Amount Due", "Amount" => SureInvoice::getCurrencySymbolPDF().number_format(($this->total - $this->payment_total), 2));
		}

		$this->pdf->ezTable($total,null,'',array('showHeadings' => 0, 'xPos' => 'left', 'xOrientation' => 'right',
				'fontSize' => 10, 
				'colGap' => 2, 
				'protectRows' => 3,
				'cols' =>
					array('Total'=>array("width" => "413",  "justification" => "right"),
						'Amount'=>array("width" => "100",  "justification" => "right")
					)
				)
		);
	}
	
	function setup_footer() {
		if(isset($this->company['note']) && !empty($this->company['note'])){
			$text = $this->company['note'];
			$this->pdf->ezSetDY(-10);
			$this->pdf->ezText($text,10,array('justification'=>'left'));
		}
	}
	
	function get_invoice_pdf() {
		if(!empty($this->error))
			return FALSE;

		$this->setup_client();
		$this->setup_invoice_details();
		$this->setup_lineitems();
		$this->setup_total();
		$this->setup_footer();

		if(empty($this->error))
			return $this->pdf->output();

		return FALSE;
	}
}

?>
