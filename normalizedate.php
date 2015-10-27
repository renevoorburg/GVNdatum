<?php

/**
 * Just a simple frontend for the GVNdatum.php class.
 *
 * In the real world, the resolution (day, month or year) of start- and end-dates
 * should probably be synchronized.
 */


require_once __DIR__ . '/classes/GVNdatum.php';

function isLeapYear($year) {
    return ((($year % 4) == 0) && ((($year % 100) != 0) || (($year %400) == 0)));
}

function lastDay ($year, $month) {
    $days = array (
        '01' => '31',
        '02' => '28',
        '03' => '31',
        '04' => '30',
        '05' => '31',
        '06' => '30',
        '07' => '31',
        '08' => '31',
        '09' => '30',
        '10' => '31',
        '11' => '30',
        '12' => '31'
    );

    if (isLeapYear($year) && $month == 2) {
        return '29';
    } else {
        return $days[$month];
    }


}

function startDay(GVNdatum $date) {
    $year = $date->getStartYear();
    if ($year == 0 ) {
        return '';
    }
    $month = $date->getStartMonth() ? $date->getStartMonth() : '01' ;
    $day = $date->getStartDay() ? $date->getStartDay() : '01' ;
    return $year.'-'.$month.'-'.$day;
}

function endDay(GVNdatum $date) {
    $year = $date->getEndYear();
    if ($year == 0 ) {
        $year = $date->getStartYear();
        $month = $date->getStartMonth() ? $date->getStartMonth() : '12';
        $day = $date->getStartDay() ? $date->getStartDay() : lastDay($year, $month);
    } else {
        $month = $date->getEndMonth() ? $date->getEndMonth() : '12';
        $day = $date->getEndDay() ? $date->getEndDay(): lastDay($year, $month);
    }
    return $year.'-'.$month.'-'.$day;
}

// get input:
$dateStr = $_GET['date'];
$date = new GVNdatum($dateStr);


echo '<date>';

echo '<given>';
echo $dateStr;
echo '</given>';

$start = startDay($date);
$end = endDay($date);

echo '<start>';
echo $start;
echo '</start>';

echo '<end>';
echo $end;
echo '</end>';

echo '</date>';

echo "\n";

