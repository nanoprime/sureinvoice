<?php
/**
* @package SPLIB
* @version $Id: Month.php,v 1.9 2003/09/23 19:39:13 harry Exp $
*/
/**
* Define include path and include required files
*/
require_once('DateMath.php');
require_once('Calendar.php');
/**
* Represents a Month and builds Days or Weeks<br />
* <b>Note:</b> this class provides two alternative methods
* to build(), Month::buildWeekDays() and Month::buildWeeks().<br />
* <b>Note:</b> requires the DateMath class be declared
* <code>
* $month = new Month(2003,8);
* $month->build();
* while ( $day = $month->fetch() ) {
*   echo ( $day->thisDay().' ' );
* }
* </code>
* @package SPLIB
*/
class Month extends Calendar
{
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
    * Days of the month built from days of the week
    * @access private
    * @var array
    */
    var $daysOfMonth = array();

    /**
    * Constructs Month
    * @param int year e.g. 2003
    * @param int month e.g. 5
    * @param string first day of the week e.g. Monday (optional)
    * @access public
    */
    function Month($y,$m,$firstDay='Monday')
    {
        Calendar::Calendar($y,$m);
        $this->firstDay=$firstDay;
        $this->weekDayNames = array(
            'Monday', 'Tuesday', 'Wednesday', 'Thursday',
            'Friday', 'Saturday', 'Sunday');
        $this->dateMath = new DateMath($this->year,$this->month,1,0,0,0);
        $this->setFirstDay();
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
    function setDaysOfMonth()
    {
        $this->daysOfMonth = $this->daysOfWeek;
        $daysInMonth = $this->dateMath->daysInMonth();
        $start=0;
        foreach ( $this->daysOfMonth as $dayOfWeek ) {
            if ( $this->dateMath->firstDayInMonth() == $dayOfWeek ) {
                break;
            }
            $start++;
        }
        $numWeeks = ceil(($daysInMonth + $start)/7);
        for ( $i=1; $i<$numWeeks; $i++ ) {
            $this->daysOfMonth = 
                array_merge($this->daysOfMonth,$this->daysOfWeek);
        }
    }

    /**
    * Builds Day objects for this Month. Creates as many Day objects
    * as there are days in the month
    * <code>
    * $month = new Month(2003,8);
    * $selectedDays = array ( new Day(2003,8,13) );
    * $month->build( $selectedDays );
    * while ( $day = $month->fetch() ) {
    *   if ( $day->isSelected() )
    *       echo ( '<b>'.$day->thisDay().'</b> ' );
    *   else
    *       echo ( $day->thisDay().' ' );
    * }
    * </code>
    * @param array of Day objects representing selected dates (optional)
    * @return boolean
    * @access public
    */
    function build($sDates=array()) {
        require_once('Day.php');
        $daysInMonth = $this->dateMath->daysInMonth();
        for ( $i=1;$i<=$daysInMonth;$i++) {
            $this->children[$i]=new Day($this->year, $this->month, $i);
            $dayOfWeekNum = $i % 7;
            foreach ( $sDates as $sDate ) {
                if ( $this->year == $sDate->thisYear()
                        && $this->month == $sDate->thisMonth()
                            && $i == $sDate->thisDay() )
                    $this->children[$i]->setSelected();
            }
        }
        return true;
    }

    /**
    * Builds Day objects for this Month grouped by week. This method
    * can be used to provide a "formatted" view of a Month, where some
    * Day objects are "empty" and week beginnings and ends can be
    * indentified
    * <code>
    * $month = new Month(2003,8);
    * $month->buildWeekDays();
    * while ( $day = $month->fetch() ) {
    *   if ( $day->isStart() )
    *       echo ( '<tr>' );
    *   if ( $day->isEmpty() )
    *       echo ( '<td>&nbsp;</td>' );
    *   else
    *       echo ( '<td>'.$day->thisDay().'</td>' );
    *   if ( $day->isEnd() )
    *       echo ( "</tr>\n" );
    * }
    * </code>
    * @see Day::isEmpty()
    * @see Day::isFirst()
    * @see Day::isLast()
    * @param array of Day objects representing selected dates (optional)
    * @return boolean
    * @access public
    */
    function buildWeekDays($sDates=array()) {
        require_once('Day.php');
        $this->setDaysOfMonth();
        $dayOfMonth = 1;
        $start = false;
        $end = false;
        $sizeDaysOfMonth = count($this->daysOfMonth);
        for ( $i = 1; $i <= $sizeDaysOfMonth; $i++ ) {
            $this->children[$i] = 
                new Day($this->year, $this->month, $dayOfMonth);
            if ( $i <= 7 ) {
                if ( $i == 1 )
                    $this->children[$i]->setFirst();
                else if ( $i == 7 )
                    $this->children[$i]->setLast();
                if ( $this->dateMath->firstDayInMonth() == $this->daysOfMonth[$i-1] )
                    $start = true;
                if ( $start )
                    $dayOfMonth++;
                else
                    $this->children[$i]->setEmpty();
            } else if ( $i > ($sizeDaysOfMonth - 7) ) {
                if ( $i == ($sizeDaysOfMonth - 6) )
                    $this->children[$i]->setFirst();
                else if ( $i == $sizeDaysOfMonth )
                    $this->children[$i]->setLast();
                if ( !$end && $dayOfMonth == $this->dateMath->daysInMonth() ) {
                    $end = true;
                    $dayOfMonth++;
                } else if ( $end ) {
                    $this->children[$i]->setEmpty();
                    $dayOfMonth = 1;
                } else {
                    $dayOfMonth++;
                }
            } else {
                if ( ($i-1) % 7 == 0)
                    $this->children[$i]->setFirst();
                else if ($i % 7 == 0)
                    $this->children[$i]->setLast();
                $dayOfMonth++;
            }
            foreach ( $sDates as $sDate ) {
                if ( $this->year == $sDate->thisYear()
                        && $this->month == $sDate->thisMonth()
                            && ($dayOfMonth-1) == $sDate->thisDay()
                                && !$this->children[$i]->isEmpty() )
                    $this->children[$i]->setSelected();
            }
        }
        return true;
    }
    /**
    * Builds Week objects for the Month. Note that Weeks will contain
    * "empty" days.
    * <code>
    * $month = new Month(2003,8);
    * $month->build( );
    * while ( $week = $month->fetch() ) {
    *   echo ( $week->thisWeek() );
    * }
    * </code>
    * @param array of Week objects representing selected dates (optional)
    * @return boolean
    * @access public
    */
    function buildWeeks($sDates=array()) {
        require_once('Week.php');
        $this->setDaysOfMonth();
        $numWeeks = count($this->daysOfMonth) / 7;
        for ( $i=1;$i<=$numWeeks;$i++ ) {
            $this->children[$i] = new Week(
                $this->year,$this->month,$i,$this->firstDay);
            foreach ( $sDates as $sDate ) {
                if ( $this->year == $sDate->thisYear()
                        && $this->month == $sDate->thisMonth()
                            && $i == $sDate->thisWeek() )
                    $this->children[$i]->setSelected();
            }
        }
        return true;
    }
}
?>