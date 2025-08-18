<?php
/**
 * /srv/http/metern/programs/programreadings.php
 *
 * @package default
 */


define('checkaccess', TRUE);
include '../config/config_main.php';
date_default_timezone_set('UTC');
ini_set('serialize_precision', -1);
include '../languages/' . $LANG . '.php';
if (!empty($_GET['metnum']) && is_numeric($_GET['metnum'])) {
	$metnum = $_GET['metnum'];
} else {
	$metnum = 1;
}
if (!empty($_GET['numberofyears']) && is_numeric($_GET['numberofyears'])) {
	$numberofyears = $_GET['numberofyears'];
} else {
	$numberofyears = 5;
}
if ($numberofyears == 0) $numberofyears = 5;

$unitlist         = array();
$totmeteryearlist = array();
$metnumyearlist = array();
include "../config/config_met$metnum.php";
if (${'TYPE' . $metnum} != 'Sensor') {
	$metnumyearlist = glob('../data/meters/' . $metnum . ${'METNAME' . $metnum} . '*.csv'); // 1Elect*
	$yearscnt      = count($metnumyearlist);
	for ($i = 0; $i < $yearscnt; $i++) {
		$year = substr($metnumyearlist[$i], -8, 4);
		$year = (int) $year;
		if (!in_array($year, $totmeteryearlist)) {
			$totmeteryearlist[] = $year;
		}
	}
	if (!in_array(${'UNIT' . $metnum}, $unitlist)) {
		array_push($unitlist, ${'UNIT' . $metnum});
	}
}
$cntunit = count($unitlist);
sort($totmeteryearlist);
$yearscnt = count($totmeteryearlist);
// Getting values
include "../config/config_met$metnum.php";
// echo"\$metnum=$metnum \$metnum=$metnum ${'TYPE' . $metnum} $yearscnt\n";
if (${'TYPE' . $metnum} != 'Sensor') {
	$conso_day = array();
	for ($i = 0; $i < $yearscnt; $i++) {
		$year     = $totmeteryearlist[$i];
		$filename = '../data/meters/' . $metnum . ${'METNAME' . $metnum} . $year . '.csv';
		// echo"/$filename=$filename\n";

		if (file_exists($filename)) {
			$thefile    = file($filename);
			$contalines = count($thefile);

			for ($line_num = 0; $line_num < $contalines; $line_num++) {
				$array                                  = preg_split("/,/", $thefile[$line_num]);
				if (isset($array[1])){
					$month                                  = substr($array[0], 4, 2);
					$day                                    = substr($array[0], 6, 2);
					$month                                  = (int) ($month);
					$day                                    = (int) ($day);
					$conso_day[$year][$month][$day][$metnum] = (float) $array[1];
					if (${'TYPE' . $metnum} == 'Elect') {
						$conso_day[$year][$month][$day][$metnum] /= 1000;
					}
				}
			} // end of looping through the file
		}

		if ($year == date('Y')) { // Add today
			$output = glob('../data/csv/*.csv');
			rsort($output);
			if (isset($output[0])) {
				$file                                   = file($output[0]);
				$month                                  = (int) substr($output[0], -8, 2);
				$day                                    = (int) substr($output[0], -6, 2);
				$contalines                             = count($file);
				$prevarray                              = preg_split('/,/', $file[1]);
				$linearray                              = preg_split('/,/', $file[$contalines - 1]);
				$val_first                              = (float) $prevarray[$metnum];
				$conso_day[$year][$month][$day][$metnum] = (float) $linearray[$metnum];
				if (!empty($val_first) && !empty($conso_day[$year][$month][$day][$metnum])) {
					if ($val_first <= $conso_day[$year][$month][$day][$metnum]) {
						$conso_day[$year][$month][$day][$metnum] -= $val_first;
					} else { // counter pass over
						$conso_day[$year][$month][$day][$metnum] += ${'PASSO' . $metnum} - $val_first;
					}
				} else {
					$conso_day[$year][$month][$day][$metnum] = 0;
				}
				if (${'TYPE' . $metnum} == 'Elect') {
					$conso_day[$year][$month][$day][$metnum] /= 1000;
				}
			}
		} // end of today

		$conso_y[$year][$metnum] = 0;
		for ($h = 1; $h <= 12; $h++) { // Fill blanks dates and drilldowndays
			$conso_m[$year][$h][$metnum] = 0;
			$daythatm                   = cal_days_in_month(CAL_GREGORIAN, $h, $year);
			$day                        = 0;
			for ($j = 1; $j <= $daythatm; $j++) {
				$epochdate = strtotime($h . '/' . $j . '/' . $year) * 1000;
				if (!isset($conso_day[$year][$h][$j][$metnum])) {
					$conso_day[$year][$h][$j][$metnum] = 0;
				}
				$conso_m[$year][$h][$metnum] += $conso_day[$year][$h][$j][$metnum];
				$conso_y[$year][$metnum] += $conso_day[$year][$h][$j][$metnum];
				$day++;
			}
		}
	} // each years
}

$title = "${'METNAME'.$metnum}";
for ($i = $yearscnt-$numberofyears; $i < $yearscnt; $i++) { // years
	$year = $totmeteryearlist[$i];
	$aData=Array();
	for ($h = 1; $h <= 12; $h++) { //months
		$title = "${'METNAME'.$metnum} $year: ";
		if (${'TYPE' . $metnum} == 'Elect') {
			$title .= number_format($conso_y[$year][$metnum], 3, $DPOINT, $THSEP);
			$title .= ' kWh';
		} else {
			$title .= number_format($conso_y[$year][$metnum], ${'PRECI' . $metnum}, $DPOINT, $THSEP);
			$title .= " ${'UNIT'.$metnum}";
		}
		if (${'PRICE' . $metnum} > 0) {
			$money = number_format(($conso_y[$year][$metnum] * ${'PRICE' . $metnum}), 1, $DPOINT, $THSEP);
			$title .= " ($money"."$CURS)";
		}
		$epochdate = strtotime($h . '/1/' . $year) * 1000;
		$aData[] = $conso_m[$year][$h][$metnum];
	} // months
	$topseries[] =	array('name'=>$year,'data'=>$aData);
} // years

$jsonreturn = array(
	'series' => $topseries
);
header("Content-type: application/json");
echo json_encode($jsonreturn);
?>
