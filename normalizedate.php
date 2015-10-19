<?php

/**
 * Just a simple frontend for the GVNdatum.php class.
 *
 * In the real world, the resolution (day, month or year) of start- and end-dates
 * should probably be synchronized.
 */


require_once __DIR__ . '/classes/GVNdatum.php';

// get input:
$dateStr = $_GET['date'];

$date = new GVNdatum($dateStr);


echo '<date>';

echo '<given>';
echo $dateStr;
echo '</given>';

echo '<start>';
echo $date->getStartDate();
echo '</start>';

echo '<end>';
echo $date->getEndDate();
echo '</end>';

echo '</date>';

echo "\n";

