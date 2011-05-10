<?php
/**
* @package SPLIB
* @version $Id: Minute.php,v 1.6 2003/09/23 19:39:13 harry Exp $
*/
/**
* Define include path and include required files
*/
require_once('Calendar.php');
/**
* Represents a Minute and builds Seconds
* <code>
* $minute = new Minute(2003,8,13,14,30); // 2:30pm on 13th Aug 2003
* $minute->build();
* while ( $second= $minute->fetch() ) {
*   echo ( $second->thisSecond() );
* }
* </code>
* @package SPLIB
*/
class Minute extends Calendar {
    /**
    * Constructs Minute
    * @param int year e.g. 2003
    * @param int month e.g. 5
    * @param int day e.g. 11
    * @param int hour e.g. 13
    * @param int minute e.g. 31
    * @access public
    */
    function Minute($y, $m, $d, $h, $i)
    {
        Calendar::Calendar($y, $m, $d, $h, $i);
    }

    /**
    * Builds the Seconds in the Minute
    * @param array of Second objects representing selected dates (optional)
    * @return boolean
    * @access public
    */
    function build($sDates=array())
    {
        require_once('Second.php');
        for ( $i = 0; $i < 60; $i++ ) {
            $this->children[$i]=new Second($this->year, $this->month,
                $this->day, $this->hour, $this->minute, $i);
            foreach ( $sDates as $sDate ) {
                if ( $this->year == $sDate->thisYear()
                        && $this->month == $sDate->thisMonth()
                            && $this->day == $sDate->thisDay()
                                && $this->hour == $sDate->thisHour()
                                    && $this->minute = $sDate->thisMinute()
                                        && $i == $sDate->thisSecond() )
                    $this->children[$i]->setSelected();
            }
        }
        return true;
    }
}
?>