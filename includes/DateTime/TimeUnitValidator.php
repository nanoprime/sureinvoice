<?php
/**
* @package SPLIB
* @version $Id: TimeUnitValidator.php,v 1.7 2003/08/09 16:33:20 harry Exp $
*/
/**
* Provides validation for date / time units<br />
* This class is required by Calendar
* @package SPLIB
*/
class TimeUnitValidator {
    /**
    * Stores error messages
    * @access private
    * @var string
    */
    var $error;

    /**
    * Constructs TimeUnitValidator
    * @access public
    */
    function TimeUnitValidator()
    {
        $this->error = '';
    }

    /**
    * Checks to see if a year is valid (1969 < YYYY < 2039)
    * @param int year four digits
    * @return mixed either int or false
    * @access public
    **/
    function validYear($year)
    {
        if ( $year < 1970 || $year > 2038 ) {
            $this->error = 'Year '.$year.
                ' falls outside valid timestamp range';
            return false;
        }
        return $year;
    }

    /**
    * Checks to see if a month is valid (0 < MM < 13)
    * @param int month two digits
    * @return boolean
    * @access public
    **/
    function validMonth($month)
    {
        if ( $month < 1 || $month > 12 ) {
            $this->error = 'Month '.$month. ' must be a number from 1 to 12';
            return false;
        }
        return $month;
    }

    /**
    * Checks to see if a day is valid (0 < DD < 32)
    * @param int day two digits
    * @return boolean
    * @access public
    **/
    function validDay($day)
    {
        if ( $day < 1 || $day > 31 ) {
            $this->error = 'Day '.$day. ' must be a number from 1 to 31';
            return false;
        }
        return $day;
    }

    /**
    * Checks to see if a day of week is valid (0 < D < 8)
    * @param int day one digit
    * @return boolean
    * @access public
    **/
    function validDayOfWeek($day)
    {
        if ( $day < 1 || $day > 7 ) {
            $this->error = 'Day '.$day. ' must be a number from 1 to 7';
            return false;
        }
        return $day;
    }

    /**
    * Checks to see if an hour is valid (-1 < H < 24)
    * @param int hour two digits
    * @return boolean
    * @access public
    **/
    function validHour($hour)
    {
        if ( $hour < 0 || $hour > 23 ) {
            $this->error = 'Hour '.$hour.' must be a number from 0 to 23';
            return false;
        }
        return $hour;
    }

    /**
    * Checks to see if a minute is valid (-1 < M < 60)
    * @param int minute two digits
    * @return boolean
    * @access public
    **/
    function validMinute($minute)
    {
        if ( $minute < 0 || $minute > 59 ) {
            $this->error = 'Minute '.$minute.' must be a number from 0 to 59';
            return false;
        }
        return $minute;
    }

    /**
    * Checks to see if a second is valid (-1 < S < 60)
    * @param int second two digits
    * @return boolean
    * @access public
    **/
    function validSecond($second)
    {
        if ( !is_numeric($second) ) {
            $this->error = 'Second '.$second.' is not numeric';
            return false;
        }
        if ( $second < 0 || $second > 59 ) {
            $this->error = 'Second '.$second.' must be a number from 0 to 59';
            return false;
        }
        return $second;
    }

    /**
    * Checks to see if a Unix timestamp is valid
    * @param int Unix timestamp
    * @return boolean
    * @access public
    **/
    function validTimestamp($ts)
    {
        if ( !is_int($ts) ) {
            $this->error = 'Timestamp '.$ts.' is not an integer';
            return false;
        }
        if ( $ts < 0 || $ts > 2145913200  ) {
            $this->error = 'Timestamp '.$ts.
                ' must be a number from 0 to 2145913200';
            return false;
        }
        return $ts;
    }

    /**
    * Returns the error message is false received from validation method
    * @return string error message
    * @access public
    **/
    function getError()
    {
        if ( !empty ( $this->error ) )
            return $this->error;
        else
            return false;
    }
}
?>