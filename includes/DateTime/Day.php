<?php
/**
* @package SPLIB
* @version $Id: Day.php,v 1.7 2003/09/23 19:39:13 harry Exp $
*/
/**
* Define include path and include required files
*/
require_once('Calendar.php');
/**
* Represents a Day and builds Hours.<br />
* <b>Note:</b> Day objects can be "empty" when acting as placeholders
* for building a calendar. "Empty" days may be created when Month::buildWeekDays()
* or Week::build() are used.<br />
* An example built by a Month object
* <code>
* $month->buildWeekDays();
* while ( $day = $month->fetch() ) {
*   if ( !$day->isEmpty() ) {
*       echo ( $day->thisDay().' ' );
*   } else {
*       echo ( '&nbsp;' );
*   }
*   if ( $day->isEnd() ) {
        echo ( "<br />\n" );
*   }
* }
* </code>
* An example built by a Week object
* <code>
* $week->build();
* while ( $day = $week->fetch() ) {
*   if ( !$day->isEmpty() ) {
*       echo ( $day->thisDay().' ' );
*   } else {
*       echo ( '&nbsp;' );
*   }
* }
* </code>
* @package SPLIB
*/
class Day extends Calendar {
    /**
    * Stores the state of empty days when building weeks in months
    * @access private
    * @var boolean
    */
    var $empty = false;

    /**
    * Marks this calendar object as beginning of calendar block
    * @access private
    * @var boolean
    */
    var $first = false;

    /**
    * Marks this calendar object as end of calendar block
    * @access private
    * @var boolean
    */
    var $last = false;

    /**
    * Constructs Day
    * <code>
    * $day = new Day (2003,8,15); // 15th August 2003
    * </code>
    * @param int year e.g. 2003
    * @param int month e.g. 8
    * @param int day e.g. 15
    * @access public
    */
    function Day($y, $m, $d)
    {
        Calendar::Calendar($y, $m, $d);
    }

    /**
    * Sets the day to empty (used by Month
    * @param boolean state (optional)
    * @return void
    * @access private
    */
    function setEmpty($state=true)
    {
        $this->empty=$state;
    }

    /**
    * Returns true if day is empty
    * @return boolean
    * @access public
    */
    function isEmpty()
    {
        return $this->empty;
    }

    /**
    * Returns this day
    * <b>Note:</b> overrides parent method to prevent values
    * being returned for "Empty" days
    * @param boolean set to true to return a timestamp
    * @return int day of month e.g. 11
    * @access public
    */
    function thisDay($asTs = false)
    {
        if ( $this->empty )
            return false;
        else
            return Calendar::thisDay($asTs);
    }

    /**
    * Builds the Hours of the Day
    * @param array of Hour objects representing selected dates (optional)
    * @return boolean
    * @access public
    */
    function build($sDates = array())
    {
        require_once('Hour.php');
        for ( $i = 0; $i < 24; $i++ ) {
            $this->children[$i]=
                new Hour($this->year, $this->month, $this->day, $i);
            foreach ( $sDates as $sDate ) {
                if ( $this->year == $sDate->thisYear()
                        && $this->month == $sDate->thisMonth()
                            && $this->day == $sDate->thisDay()
                                && $i == $sDate->thisHour() )
                    $this->children[$i]->setSelected();
            }
        }
        return true;
    }

    /**
    * Defines Day object as first in a week
    * Only used by Month::buildWeekDays()
    * @param boolean state
    * @return void
    * @access private
     */
    function setFirst ($state = true)
    {
        $this->first = $state;
    }

    /**
    * Defines Day object as last in a week
    * Only used by Month::buildWeekDays()
    * @param boolean state
    * @return void
    * @access private
    */
    function setLast($state = true)
    {
        $this->last = $state;
    }

    /**
    * Returns true if Day object is first in a Week
    * Only relevant when Day is created by Month::buildWeekDays()
    * @return boolean
    * @access public
    */
    function isFirst() {
        return $this->first;
    }

    /**
    * Returns true if Day object is last in a Week
    * Only relevant when Day is created by Month::buildWeekDays()
    * @return boolean
    * @access public
    */
    function isLast()
    {
        return $this->last;
    }
}
?>