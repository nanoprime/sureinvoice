<?php
/**
* @package SPLIB
* @version $Id: Hour.php,v 1.6 2003/09/23 19:39:13 harry Exp $
*/
/**
* Define include path and include required files
*/
require_once('Calendar.php');
/**
* Represents an Hour and builds Minutes
* <code>
* $hour = new Hour(2003,8,13,14); // 2pm on 13th Aug 2003
* $hour->build();
* while ( $minute= $hour->fetch() ) {
*   echo ( $minute->thisMinute() );
* }
* </code>
* @package SPLIB
*/
class Hour extends Calendar {
    /**
    * Constructs Hour
    * @param int year e.g. 2003
    * @param int month e.g. 5
    * @param int day e.g. 11
    * @param int hour e.g. 13
    * @access public
    */
    function Hour($y, $m, $d, $h)
    {
        Calendar::Calendar($y, $m, $d, $h);
    }

    /**
    * Builds the Minutes in the Hour
    * @param array of Minute objects representing selected dates (optional)
    * @return boolean
    * @access public
    */
    function build($sDates=array())
    {
        require_once('Minute.php');
        for ( $i = 0; $i < 60; $i++ ) {
            $this->children[$i]=
                new Minute($this->year, $this->month, $this->day,
                           $this->hour, $i);

            foreach ( $sDates as $sDate ) {
                if ( $this->year == $sDate->thisYear()
                        && $this->month == $sDate->thisMonth()
                            && $this->day == $sDate->thisDay()
                                && $this->hour == $sDate->thisHour()
                                    && $i == $sDate->thisMinute())
                    $this->children[$i]->setSelected();
            }
        }
        return true;
    }
}
?>