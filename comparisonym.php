<?php
/**
 * /srv/http/metern/comparison.php
 *
 * @package default
 */


include 'styles/globalheader.php';
include 'config/config_main.php';

if (!empty($_POST['compareyears']) && is_numeric($_POST['compareyears'])) {
	$COMPAREYEARS = $_POST['compareyears'];
}
if (!empty($_POST['met_num'])) {
	$metnum = $_POST['met_num'];
} else {
	$metnum = 1;
}
include "config/config_met$metnum.php";

$dir   = 'data/meters/';
$stack = glob($dir . $metnum . ${'METNAME' . $metnum} . '*.csv');
sort($stack);
$xyears = count($stack);
$output = array();

for ($i = 0; $i < $xyears; $i++) {
	$option = substr($stack[$i], -8, 4);
	if (file_exists('data/meters/' . $metnum . ${'METNAME' . $metnum} . $option . '.csv')) {
		array_push($output, $stack[$i]);
	}
}
sort($output);
$xyears = count($output);
if ($COMPAREYEARS > $xyears) $COMPAREYEARS = $xyears;

echo "
<table width='95%' border=0 align=center cellpadding=8>
<tr><td>
<form method='POST' action='comparisonym.php'>";
echo "$lgCHOOSEMET: <select name='met_num' onchange='this.form.submit()'>";
for ($i = 1; $i <= $NUMMETER; $i++) {
	include "config/config_met$i.php";
	if (${'TYPE' . $i} != 'Sensor') {
		if ($metnum == $i) {
			echo "<option value='$i' SELECTED>";
		} else {
			echo "<option value='$i'>";
		}
		echo "${'METNAME'.$i}</option>";
	}
}
echo "</select>
<select name='compareyears' onchange='this.form.submit()'>";
for ($i = ($xyears); $i >= 0; $i--) {
	if ($COMPAREYEARS == $i) {
		echo "<option SELECTED>";
	} else {
		echo "<option>";
	}
	echo "$i</option>";
}
echo "</select>";
// echo "&nbsp;<input type='submit' value='   $lgOK   '>
echo "</form>
</td></tr>
</table>

<script type=\"text/javascript\">
$(document).ready(function() {
Highcharts.setOptions({
global: {useUTC: true},
lang: {
decimalPoint: '$DPOINT',
thousandsSep: '$THSEP',
months: ['";
	for ($i = 1; $i < 12; $i++) {
		echo "$lgMONTH[$i]','";
	}
	echo "$lgMONTH[12]'],
shortMonths: ['";
	for ($i = 1; $i < 12; $i++) {
		echo "$lgSMONTH[$i]','";
	}
	echo "$lgSMONTH[12]'],
weekdays: ['";
	for ($i = 1; $i < 7; $i++) {
		echo "$lgWEEKD[$i]','";
	}
	echo "$lgWEEKD[7]'],
loading: '$lgLOAD',
printChart: '$lgPRINT',
resetZoom: '$lgRESETZ'
}
});

var defaultTitle = ";
	echo "\"$lgCONSUTITLE: ${'METNAME'.$metnum}\"";
	echo ",prevPointTitle = null;

var Mychart, options = {
        chart: {
                type: 'column',
                backgroundColor: null,
				events: {
					},
            },
			title: {
				text: defaultTitle,
				style: {fontSize: '1em'}
			},
            subtitle: {text: '$COMPAREYEARS ${lgMONTH[13]} $lgMCOMPARISON'},
            xAxis: {
            categories: [
";
	for ($i = 1; $i <= 12; $i++) {
		if ($i > 1) {
			echo ",";
		}
		echo "'$lgSMONTH[$i]'";
	}
echo "]
               },
";
	echo "\t\tyAxis: [";
	echo "{
\t\tlabels: { formatter: function() { return this.value +'${'UNIT' . $metnum}';}},
\t\ttitle: { text: '${'UNIT' . $metnum}'}
\t\t}";
	echo "],
		plotOptions: {
			series: {
				borderWidth: 1,
			},
			column: {
		  		cursor: 'pointer',
				groupPadding: 0.12,
			}
		},
      tooltip: {
			formatter: function() {
				s= this.series.name + '<br><b>' + Highcharts.numberFormat(this.y,0) + ' kWh';
				s+= '</b>';
				return s;
			}
		 },
  exporting: {
  filename: 'meterN-chart',
  width: 1200
  },
  credits: {
  enabled: false
  },
    series: []
 };
Mychart= Highcharts.chart('container',options);
Mychart.showLoading();
$.getJSON('programs/programcomparisonym.php?compareyears=$COMPAREYEARS', { metnum: $metnum }, function(JSONResponse) {
  options.series = JSONResponse.series;
  Mychart= Highcharts.chart('container',options);
  Mychart.hideLoading();
});
});
</script>";

echo "
<table width='100%' border=0 align=center cellpadding=0>
<tr><td><div id='container' style='width: 95%; height: 450px'></div></td></tr>
</table>
";

include "styles/$STYLE/footer.php";
?>
