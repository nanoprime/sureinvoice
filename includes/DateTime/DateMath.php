<?php
/**
* @package SPLIB
* @version $Id: DateMath.php,v 1.8 2003/12/09 06:06:56 kevin Exp $
*/
/**
* DateMath class for solving common date problems
* @package SPLIB
*/
class DateMath {
    /**
    * Unix timestamp
    * @access private
    * @var int
    */
    var $timeStamp;

    /**
    * DateMath constructor
    * If parameters not provided, DateMath uses current time
    * <code>
    * $dateMath = new DateMath(2003,8,22); // Uses 22nd August 2003
    * $dateMath = new DateMath(); // Uses current date and time
    * </code>
    * @param int year ( e.g. 2003 )
    * @param int month ( e.g. 8 for August )
    * @param int day of month ( e.g. 22 )
    * @param int hours ( e.g. 15 )
    * @param int minutes ( e.g. 5 )
    * @param int seconds ( e.g. 7 )
    */
    function DateMath($y = null, $m = null, $d = null, $h = null, $i = null, $s = null){
        $time = time();

        $y = is_numeric($y) ? $y : date('Y', $time);
        $m = is_numeric($m) ? $m : date('m', $time);
        $d = is_numeric($d) ? $d : date('d', $time);
        $h = is_numeric($h) ? $h : date('H', $time);
        $i = is_numeric($i) ? $i : date('i', $time);
        $s = is_numeric($s) ? $s : date('s', $time);
        $this->timeStamp=mktime($h,$i,$s,$m,$d,$y);
    }

    /**
    * For setting a Unix timestamp
    * @param int a Unix timestamp
    * @return void
    * @access public
    */
    function setTimeStamp ($timestamp) {
        $this->timeStamp=$timeStamp;
    }

    /**
    * Returns the day of the week
    * @param  boolean if true returned value will be numeric day of week
    * @return mixed e.g. Saturday or 6
    * @access public
    */
    function dayOfWeek ($numeric=false) {
        if ( $numeric ) {
            return date('w', $this->timeStamp);
        } else {
            return date('l', $this->timeStamp);
        }
    }

    /**
    * Returns the ISO 8601 week of the year
    * @return int numeric week of year e.g. 12
    * @access public
    */
    function weekOfYear () {
        return date ('W',$this->timeStamp);
    }

    /**
    * Provides the number of days in the month
    * @return int number of days in the month
    * @access public
    */
    function daysInMonth () {
        return date('t',$this->timeStamp);
    }

    /**
    * Determines whether current year is a leap year
    * @return boolean true if a leap year
    * @access public
    */
    function isLeapYear () {
        return date('L', $this->timeStamp);
    }

    /**
    * Returns the day of the year
    * @return int numeric day of year e.g. 81
    * @access public
    */
    function dayOfYear () {
        return date('z', $this->timeStamp) + 1;
    }

    /**
    * Returns the day of the week for the first of the month
    * @param  boolean if true returned value will be numeric day of week
    * @return string e.g. Monday
    * @access public
    */
    function firstDayInMonth ($numeric = false) {
        $firstDay = mktime(0,0,0,date('m',$this->timeStamp),1,
                           date('Y',$this->timeStamp));
        if ($numeric) {
            return date('w', $firstDay);
        } else {
            return date('l', $firstDay);
        }
    }

    /**
    * Provide the suffix for a number e.g. 22 is the 22nd
    * @static
    * @param  int some number
    * @return string e.g. 'nd' for 22nd
    * @static
    * @access public
    */
    function suffix ($num) {
        if ( $num < 11 || $num > 13 ) {
            $desc=array(0=>'th',1=>'st',2=>'nd',3=>'rd',4=>'th',
                        5=>'th',6=>'th',7=>'th',8=>'th',9=>'th');
            return $desc[$num % 10];
        } else {
            return 'th';
        }
    }
}
?>