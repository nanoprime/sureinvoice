#!/bin/bash

PHPDOC=phpdoc
cd `dirname $0`;

$PHPDOC -pp -ti "SureInvoice Documentation" -dn com.uversainc.sureinvoice -t ../docs/ -d ../ -i ../docs/* -o HTML:Smarty:PHP 
