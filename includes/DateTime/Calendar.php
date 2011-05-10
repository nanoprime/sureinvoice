<?php
/**
* @package SPLIB
* @version $Id: Calendar.php,v 1.9 2003/09/23 19:39:11 harry Exp $
*/
/**
* Define include path and include required files
*/
require_once('TimeUnitValidator.php');
/**
* Base class for Calendar API. This class should not be instantiated
* directly.<br />
* <b>Note:</b> Requires TimeUnitValidator class to be available.
* @abstract
* @package SPLIB
*/
class Calendar
{
    /**
    * Instance of TimeUnitValidator
    * @access private
    * @var object
    */
    var $validator;

    /**
    * Year for this calendar object e.g. 2003
    * @access private
    * @var int
    */
    var $year;

    /**
    * Month for this calendar object e.g. 9
    * @access private
    * @var int
    */
    var $month;

    /**
    * Day of month for this calendar object e.g. 23
    * @access private
    * @var int
    */
    var $day;

    /**
    * Hour of day for this calendar object e.g. 13
    * @access private
    * @var int
    */
    var $hour;

    /**
    * Minute of hour this calendar object e.g. 46
    * @access private
    * @var int
    */
    var $minute;

    /**
    * Second of minute this calendar object e.g. 34
    * @access private
    * @var int
    */
    var $second;

    /**
    * Marks this calendar object as selected (e.g. 'today')
    * @access private
    * @var boolean
    */
    var $selected = false;

    /**
    * Collection of child calendar objects created from subclasses
    * of Calendar. Type depends on the object which created them.
    * @access private
    * @var array
    */
    var $children = array();

    /**
    * Constructs the Calendar
    * @param int year
    * @param int month
    * @param int day
    * @param int hour
    * @param int minute
    * @param int second
    * @access protected
    */
    function Calendar($y = 2000, $m = 1, $d = 1, $h = 0, $i = 0, $s = 0)
    {
        $this->validator=new TimeUnitValidator();
        if (false===($this->year=$this->validator->validYear((int)$y)))
            trigger_error('Calendar::Calendar '.$this->validator->getError());
        if (false===($this->month=$this->validator->validMonth((int)$m)))
            trigger_error('Calendar::Calendar '.$this->validator->getError());
        if (false===($this->day=$this->validator->validDay((int)$d)))
            trigger_error('Calendar::Calendar '.$this->validator->getError());
        if (false===($this->hour=$this->validator->validHour((int)$h)))
            trigger_error('Calendar::Calendar '.$this->validator->getError());
        if (false===($this->minute=$this->validator->validMinute((int)$i)))
            trigger_error('Calendar::Calendar '.$this->validator->getError());
        if (false===($this->second=$this->validator->validSecond((int)$s)))
            trigger_error('Calendar::Calendar '.$this->validator->getError());
    }

    /**
     * Defines the calendar by a Unix timestamp, replacing values
     * passed to the constructor
     * @param int Unix timestamp
     * @return boolean
     * @access public
     */
    function setTimestamp($ts)
    {
        if ( !$this->validator->validTimestamp($ts) ) {
            trigger_error('Calendar::setTimestamp '.
                $this->validator->getError());
            return false;
        }
        $this->year = date('Y', $ts);
        $this->month = date('m', $ts);
        $this->day = date('d', $ts);
        $this->hour = date('H', $ts);
        $this->minute = date('i', $ts);
        $this->second = date('s', $ts);
        return true;
    }

    /**
     * Returns a timestamp from the current date / time values
     * @return int Unix timestamp
     * @access public
     */
    function getTimestamp()
    {
        return mktime($this->hour, $this->minute ,$this->second,
                      $this->month ,$this->day ,$this->year);
    }

    /**
    * Defines calendar object as selected (e.g. for today)
    * Generally used by other Calendar subclass objects when
    * calling the build() method
    * @param boolean state whether Calendar subclass
    * object is selected or not (optional)
    * @return void
    * @access public
    */
    function setSelected($state = true)
    {
        $this->selected = $state;
    }

    /**
    * True if the calendar subclass object is selected (e.g. today)
    * <code>
    * $day = $month->fetch();
    * if ( $day->isSelected() )
    *   echo ( '<b>'.$day->thisDay().'</b>' );
    * else
    *   echo ( $day->thisDay() );
    * </code>
    * @return boolean
    * @access public
    */
    function isSelected()
    {
        return $this->selected;
    }

    /**
    * Abstract method for building the children of a calendar object
    * @param array containing Calendar objects to select (optional)
    * @return boolean
    * @abstract
    */
    function build($sDates = array())
    {
        trigger_error('Calendar::build is abstract');
        return false;
    }

    /**
    * Iterator method for fetching child Calendar subclass objects
    * (e.g. a minute from an hour object). On reaching the end of
    * the collection, returns false and resets the collection for
    * further iteratations.
    * @return mixed either an object subclass of Calendar or false
    * @access public
    */
    function fetch()
    {
        $child=each($this->children);
        if ($child) {
            return $child['value'];
        } else {
            reset($this->children);
            return false;
        }
    }

    /**
    * Fetches all child from the current collection of children
    * @return array
    * @access public
    */
    function fetchAll()
    {
        return $this->children;
    }

    /**
    * Get the number Calendar subclass objects stored in the internal
    * collection.
    * <code>
    * $month = new Month(2003,8,15);
    * $month->build();
    * echo ( $month->size() ); // Displays 31 for 31 Day objects
    * </code>
    * @return int
    * @access public
    */
    function size()
    {
        return count($this->children);
    }

    /**
    * Returns the value for last year
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 2002 or Unix timestamp
    * @access public
    */
    function lastYear($asTs = false)
    {
        if ( !$asTs )
            return $this->year-1;
        else
            return mktime(0, 0, 0, 1, 1, $this->year-1);
    }

    /**
    * Returns the value for this year
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 2003 or Unix timestamp
    * @access public
    */
    function thisYear($asTs = false)
    {
        if ( !$asTs )
            return $this->year;
        else
            return mktime(0, 0, 0, 1, 1, $this->year);
    }

    /**
    * Returns the value for next year
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 2004 or Unix timestamp
    * @access public
    */
    function nextYear($asTs = false)
    {
        if ( !$asTs )
            return $this->year+1;
        else
            return mktime(0, 0, 0, 1, 1, $this->year+1);
    }

    /**
    * Returns the value for last month
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 04 or Unix timestamp
    * @access public
     */
    function lastMonth($asTs = false)
    {
        $ts = mktime(0, 0, 0, $this->month-1, 1, $this->year);
        if ( $asTs )
            return $ts;
        else
            return date('m', $ts);
    }

    /**
    * Returns the value for this month
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 05 or Unix timestamp
    * @access public
    */
    function thisMonth($asTs = false)
    {
        $ts = mktime(0, 0, 0, $this->month, 1, $this->year);
        if ( $asTs )
            return $ts;
        else
            return date('m', $ts);
    }

    /**
    * Returns the value for next month
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 06 or Unix timestamp
    * @access public
    */
    function nextMonth($asTs = false)
    {
        $ts = mktime(0, 0, 0, $this->month+1, 1, $this->year);
        if ( $asTs )
            return $ts;
        else
            return date('m', $ts);
    }

    /**
    * Returns the value for the last day
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 10 or Unix timestamp
    * @access public
    */
    function lastDay($asTs = false) {
        $ts = mktime(0, 0, 0, $this->month, $this->day-1, $this->year);
        if ( $asTs )
            return $ts;
        else
            return date('d', $ts);
    }

    /**
    * Returns the value for this day
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 11 or Unix timestamp
    * @access public
    */
    function thisDay($asTs=false)
    {
        $ts = mktime(0, 0, 0, $this->month, $this->day, $this->year);
        if ( $asTs )
            return $ts;
        else
            return date('d', $ts);
    }

    /**
    * Returns the value for the next day
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 12 or Unix timestamp
    * @access public
    */
    function nextDay($asTs=false)
    {
        $ts = mktime(0,0,0,$this->month,$this->day+1,$this->year);
        if ( $asTs )
            return $ts;
        else
            return date('d',$ts);
    }

    /**
    * Returns the value for this last hour
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 13 or Unix timestamp
    * @access public
    */
    function lastHour($asTs=false)
    {
        $ts = mktime($this->hour-1, 0, 0, $this->month,
            $this->day, $this->year);
        if ( $asTs )
            return $ts;
        else
            return date('H', $ts);
    }

    /**
    * Returns the value for this hour
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 14 or Unix timestamp
    * @access public
    */
    function thisHour($asTs = false)
    {
        $ts = mktime($this->hour, 0, 0, $this->month,
            $this->day, $this->year);
        if ( $asTs )
            return $ts;
        else
            return date('H', $ts);
    }

    /**
    * Returns the value for the next hour
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 14 or Unix timestamp
    * @access public
    */
    function nextHour($asTs = false)
    {
        $ts = mktime($this->hour+1, 0, 0, $this->month,
            $this->day, $this->year);
        if ( $asTs )
            return $ts;
        else
            return date('H', $ts);
    }

    /**
    * Returns the value for the last minute
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 23 or Unix timestamp
    * @access public
    */
    function lastMinute($asTs = false)
    {
        $ts = mktime($this->hour, $this->minute-1, 0,
            $this->month, $this->day, $this->year);
        if ( $asTs )
            return $ts;
        else
            return date('i', $ts);
    }

    /**
    * Returns the value for this minute
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 24 or Unix timestamp
    * @access public
    */
    function thisMinute($asTs = false)
    {
        $ts = mktime($this->hour, $this->minute, 0,
            $this->month, $this->day, $this->year);
        if ( $asTs )
            return $ts;
        else
            return date('i', $ts);
    }

    /**
    * Returns the value for the next minute
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 25 or Unix timestamp
    * @access public
    */
    function nextMinute($asTs = false)
    {
        $ts = mktime($this->hour, $this->minute+1, 0,
            $this->month, $this->day, $this->year);
        if ( $asTs )
            return $ts;
        else
            return date('i', $ts);
    }

    /**
    * Returns the value for the last second
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 43 or Unix timestamp
    * @access public
    */
    function lastSecond($asTs = false)
    {
        $ts = mktime($this->hour, $this->minute, $this->second-1,
            $this->month, $this->day, $this->year);
        if ( $asTs )
            return $ts;
        else
            return date('s', $ts);
    }

    /**
    * Returns the value for this second
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 44 or Unix timestamp
    * @access public
    */
    function thisSecond($asTs = false)
    {
        $ts = mktime($this->hour, $this->minute, $this->second,
            $this->month, $this->day, $this->year);
        if ( $asTs )
            return $ts;
        else
            return date('s', $ts);
    }

    /**
    * Returns the value for the next second
    * @param boolean set to true to return a timestamp (optional)
    * @return int e.g. 45 or Unix timestamp
    * @access public
    */
    function nextSecond($asTs = false)
    {
        $ts=mktime($this->hour, $this->minute, $this->second+1,
            $this->month, $this->day, $this->year);
        if ( $asTs )
            return $ts;
        else
            return date('s', $ts);
    }
}
?>