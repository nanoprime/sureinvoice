#!/bin/bash

DB_NAME=sureinvoice
cd `dirname $0`;
mysqldump --compatible=mysql323 -Q --no-data --add-drop-table $DB_NAME>sureinvoice.sql
