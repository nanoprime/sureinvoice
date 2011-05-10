<?php
/**
* @package SPLIB
* @version $Id: Year.php,v 1.6 2003/09/23 19:39:15 harry Exp $
*/
/**
* Define include path and include required files
*/
require_once('Calendar.php');
/**
* Represents a Year and builds Months
* <code>
* $year = new Year(2003);
* $year->build();
* while ( $month = $year->fetch() ) {
*   echo ( $month->thisMonth().' ' );
* }
* </code>
* @package SPLIB
*/
class Year extends Calendar
{
    /**
    * Constructs Year
    * @param int year e.g. 2003
    * @access public
    */
    function Year($y)
    {
        Calendar::Calendar($y);
    }

    /**
    * Builds the Months of the Year
    * <code>
    * $year = new Year(2003);
    * $selectedMonths = array ( new Month(2003,8) );
    * $year->build( $selectedMonths );
    * while ( $month = $year->fetch() ) {
    *   if ( $month->isSelected() )
    *       echo ( '<b>'.$month->thisMonth().'</b> ' );
    *   else
    *       echo ( $month->thisMonth().' ' );
    * }
    * </code>
    * @param array of Month objects representing selected dates (optional)
    * @param string first day of week for Month objects e.g. Sunday
    * @return boolean
    * @access public
    **/
    function build($sDates = array(),$firstDay='Monday')
    {
        require_once(CALENDAR_PATH.'Month.php');
        for ( $i = 1; $i <= 12; $i++ ) {
            $this->children[$i]=new Month($this->year, $i, $firstDay);
            foreach ( $sDates as $sDate ) {
                if ( $this->year == $sDate->thisYear()
                        && $i == $sDate->thisMonth() )
                    $this->children[$i]->setSelected();
            }
        }
        return true;
    }
}
?>