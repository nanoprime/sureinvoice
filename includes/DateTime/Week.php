<?php
/**
* @package SPLIB
* @version $Id: Week.php,v 1.7 2003/09/23 19:39:14 harry Exp $
*/
/**
* Define include path and include required files
*/
require_once('DateMath.php');
require_once('Calendar.php');
/**
* Represents a Week and builds Days<br />
* <b>Note:</b> days may be "empty" if the Week
* is the first of last of the month.<br />
* <b>Note:</b> requires the DateMath class be declared<br />
* <b>Note:</b> the timestamp for a Week will the same as the first
* day in that week.
* <code>
* $week = new Week(2003,8,1); // First week in August 2003
* $week->build();
* while ( $day = $week->fetch() ) {
*   if ( $day->isEmpty() )
*       echo ( '&nbsp;' );
*   else
*       echo ( $day->thisDay() );
* }
* </code>
* @package SPLIB
*/
class Week extends Calendar
{
    /**
    * Stores the numeric value of this week relative to others in the month
    * @access private
    * @var object
    */
    var $thisWeek;

    /**
    * Stores an instance of DateMath
    * @access private
    * @var DateMath
    */
    var $dateMath;

    /**
    * First day of the week
    * @access private
    * @var string
    */
    var $firstDay;

    /**
    * The seven days of the week named
    * @access private
    * @var array
    */
    var $weekDayNames;

    /**
    * Days of the week ordered with $firstDay at the beginning
    * @access private
    * @var array
    */
    var $daysOfWeek = array();

    /**
    * Number of weeks in the month
    * @access private
    * @var array
    */
    var $numWeeks;

    /**
    * Constructs Week
    * @param int year e.g. 2003
    * @param int month e.g. 5
    * @param int week of month e.g. 3
    * @param string first day of the week e.g. Monday (optional)
    * @access public
    */
    function Week($y,$m,$w,$firstDay='Monday')
    {
        Calendar::Calendar($y,$m);
        $this->firstDay=$firstDay;
        $this->weekDayNames = array(
            'Monday', 'Tuesday', 'Wednesday', 'Thursday',
            'Friday', 'Saturday', 'Sunday');
        $this->dateMath = new DateMath($this->year,$this->month,1,0,0,0);
        $this->setFirstDay();
        $this->setNumWeeks();
        if ( $w < 1 || $w > $this->numWeeks ) {
            trigger_error('Week::Week week '.$w.' is invalid');
        } else {
            $this->thisWeek = $w;
        }
    }

    /**
    * Constructs $this->daysOfWeek based on $this->firstDay
    * @return void
    * @access private
    */
    function setFirstDay()
    {
        $endDays = array();
        $tmpDays = array();
        $begin = false;
        foreach ( $this->weekDayNames as $day ) {
            if ( $begin == true ) {
                $endDays[] = $day;
            } else if ( $day == $this->firstDay ) {
                $begin = true;
                $endDays[] = $day;
            } else {
                $tmpDays[] = $day;
            }
        }
        $this->daysOfWeek = array_merge($endDays, $tmpDays);
    }

    /**
    * Constructs $this->daysOfMonth
    * @return void
    * @access private
    */
    function setNumWeeks()
    {
        $daysInMonth = $this->dateMath->daysInMonth();
        $start=0;
        foreach ( $this->daysOfWeek as $dayOfWeek ) {
            if ( $this->dateMath->firstDayInMonth() == $dayOfWeek ) {
                break;
            }
            $start++;
        }
        $this->numWeeks = ceil(($daysInMonth + $start)/7);
    }

    /**
    * Builds Day objects for this Week
    * @param array of Day or Week objs representing selected dates (optional)
    * @return boolean
    * @access public
    */
    function build($sDates = array())
    {
        require_once(CALENDAR_PATH.'Day.php');
        foreach ( $this->daysOfWeek as $key => $dayOfWeek ) {
            if ( $this->dateMath->firstDayInMonth() == $dayOfWeek ) {
                $offset = $key;
                break;
            }
        }
        if ( $this->thisWeek == 1 ) {
            $start = false;
            $day = 1;
            for ( $i=1;$i<=7;$i++ ) {
                if ( !$start && $i > $offset ) {
                    $start = true;
                }
                if ( $start ) {
                    $this->children[$i] = 
                        new Day($this->year, $this->month, $day);
                    $day++;
                } else {
                    $this->children[$i] = 
                        new Day($this->year, $this->month, 1);
                    $this->children[$i]->setEmpty();
                }
                $this->selectDay($sDates,$i);
            }
        } else if ( $this->thisWeek == $this->numWeeks ) {
            $end = false;
            for ( $i=1;$i<=7;$i++ ) {
                $day = ($this->thisWeek - 1) * 7 + ($i-$offset);
                if ( !$end && $this->dateMath->daysInMonth() < $day ) {
                    $end = true;
                }
                if ( ! $end ) {
                    $this->children[$i] = 
                        new Day($this->year, $this->month, $day);
                } else {
                    $this->children[$i] = 
                        new Day($this->year, $this->month, 1);
                    $this->children[$i]->setEmpty();
                }
                $this->selectDay($sDates,$i);
            }
        } else {
            // Does not take into account offset...
            for ( $i=1;$i<=7;$i++ ) {
                $day = ($this->thisWeek - 1) * 7 + ($i-$offset);
                $this->children[$i] = 
                    new Day($this->year, $this->month, $day);
                $this->selectDay($sDates,$i);
            }
        }
        return true;
    }

    /**
    * Week::build() delegates Day selection to seperate method
    * @param array of Day or Week objs representing selected dates (optional)
    * @param int index of child object to test
    * @return boolean
    * @access private
    */
    function selectDay($sDates,$index) {
        foreach ( $sDates as $sDate ) {
            if ( $this->year == $sDate->thisYear()
                    && $this->month == $sDate->thisMonth()
                        && $this->children[$index]->thisDay() == $sDate->thisDay() )
                $this->children[$index]->setSelected();
        }
    }

    /**
    * Gets the numeric value of the previous week in the month
    * or false if this is the first week of the month
    * @return mixed
    * @access public
    */
    function lastWeek() {
        $lastWeek = $this->thisWeek - 1;
        return ( $lastWeek > 0 ? $lastWeek : false );
    }

    /**
    * Gets the numeric value of the week in the month
    * @return int
    * @access public
    */
    function thisWeek() {
        return $this->thisWeek;
    }

    /**
    * Gets the numeric value of the next week in the month
    * or false if this is the last week of the month
    * @return mixed
    * @access public
    */
    function nextWeek() {
        $nextWeek = $this->thisWeek + 1;
        return ( $nextWeek <= $this->numWeeks ? $nextWeek : false );
    }

    /**
     * Returns a timestamp from the current date / time values<br />
     * <b>Note:</b> overrides parent method as Week must determine
     * the first day in the collection.<br />
     * <b>Note:</b> calling this method also
     * calls the build() method, if it has not already called.
     * @return int Unix timestamp
     * @access public
     */
    function getTimestamp()
    {
        if ( !count ( $this->children ) > 0 )
            $this->build();
        $this->day = $this->children[1]->thisDay();
        return mktime($this->hour, $this->minute ,$this->second,
                      $this->month ,$this->day ,$this->year);
    }
}
?>