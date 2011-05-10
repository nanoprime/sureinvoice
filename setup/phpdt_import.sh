#!/bin/bash

cd `dirname $0`;
mysqladmin drop sureinvoice
mysqladmin create sureinvoice
mysql sureinvoice <sureinvoice.sql
mysql sureinvoice <sureinvoice_initial2_data.sql
php4 phpdt_to_sureinvoice.php
