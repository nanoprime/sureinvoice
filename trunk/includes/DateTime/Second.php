<?php
/**
* @package SPLIB
* @version $Id: Second.php,v 1.5 2003/09/23 19:39:14 harry Exp $
*/
/**
* Define include path and include required files
*/
require_once('Calendar.php');
/**
* Represents a Second<br />
* <b>Note:</b> Seconds do not build other objects
* so related methods are overridden to return false
* @package SPLIB
*/
class Second extends Calendar {
    /**
    * Constructs Second
    * @param int year e.g. 2003
    * @param int month e.g. 5
    * @param int day e.g. 11
    * @param int hour e.g. 13
    * @param int minute e.g. 31
    * @param int second e.g. 45
    */
    function Second($y, $m, $d, $h, $i, $s)
    {
        Calendar::Calendar($y, $m, $d, $h, $i, $s);
    }

    /**
    * Overwrite build
    * @return boolean false
    */
    function build()
    {
        return false;
    }

    /**
    * Overwrite fetch
    * @return boolean false
    */
    function fetch()
    {
        return false;
    }

    /**
    * Overwrite fetchAll
    * @return boolean false
    */
    function fetchAll()
    {
        return false;
    }

    /**
    * Overwrite size
    * @return boolean false
    */
    function size()
    {
        return false;
    }
}
?>