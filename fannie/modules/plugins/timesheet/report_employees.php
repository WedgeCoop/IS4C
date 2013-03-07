<?php
//	FULL TIME: Number of hours per week
$ft = 40;


$header = "Timeclock - Employees Report";
include('../../../src/header.php');
include('./includes/header.html');

echo "<form action='report_employees.php' method=GET>";

$currentQ = "SELECT periodID FROM is4c_log.payperiods WHERE now() BETWEEN periodStart AND periodEnd";
$currentR = mysql_query($currentQ);
list($ID) = mysql_fetch_row($currentR);

$query = "SELECT date_format(periodStart, '%M %D, %Y') as periodStart, date_format(periodEnd, '%M %D, %Y') as periodEnd, periodID FROM is4c_log.payperiods WHERE periodStart < now() ORDER BY periodID DESC";
$result = mysql_query($query);

echo '<p>Starting Pay Period: <select name="period">
    <option>Please select a starting pay period.</option>';

while ($row = mysql_fetch_array($result)) {
    echo "<option value=\"" . $row['periodID'] . "\"";
    if ($row['periodID'] == $ID) { echo ' SELECTED';}
    echo ">(" . $row['periodStart'] . " - " . $row['periodEnd'] . ")</option>";
}

echo "</select><br />";
echo '<p>Ending Pay Period: <select name="end">
    <option value=0>Please select an ending pay period.</option>';
$result = mysql_query($query);
while ($row = mysql_fetch_array($result)) {
    echo "<option value=\"" . $row['periodID'] . "\"";
    if ($row['periodID'] == $ID) { echo ' SELECTED';}
    echo ">(" . $row['periodStart'] . " - " . $row['periodEnd'] . ")</option>";
}
echo '</select><button value="run" name="run">Run</button></p></form>';
if ($_GET['run'] == 'run') {
	$periodID = $_GET['period'];
	$end = ($_GET['end']== 0) ? $periodID : $_GET['end'];
	
	$namesq = "SELECT e.emp_no, e.FirstName, e.LastName, e.pay_rate, JobTitle FROM employees e WHERE e.empActive = 1 ORDER BY e.LastName";
	$namesr = mysql_query($namesq);
	$areasq = "SELECT ShiftName, ShiftID FROM ".DB_LOGNAME.".shifts WHERE visible = 1 AND ShiftID <> 31 ORDER BY ShiftOrder";
	$areasr = mysql_query($areasq);
	


	$query1 = "SELECT date_format(periodStart, '%M %D, %Y') as periodStart, periodID as pid FROM is4c_log.payperiods WHERE periodID = $periodID";
	$result1 = mysql_query($query1);
	$periodStart = mysql_fetch_row($result1);

	$query2 = "SELECT date_format(periodEnd, '%M %D, %Y') as periodEnd, periodID as pid FROM is4c_log.payperiods WHERE periodID = $end";
	$result2 = mysql_query($query2);
	$periodEnd = mysql_fetch_row($result2);
	
	// $periodct = ($end !== $periodID) ? $end - $periodID : 1;
	for ($i = $periodStart[1]; $i <= $periodEnd[1]; $i++) {
		// echo $i;
		$periodct++;
		$p[] = $i;
	}
	echo "<br />";
	echo "<h3>" . $periodStart[0] . " &mdash; " . $periodEnd[0] . "</h3>\n";
	echo "Number of payperiods: " . $periodct . "\n";
	// 
	// END TITLE	
	echo "<br />";
	

	echo "<table border='1' cellpadding='5' cellspacing=0><thead>\n<tr><th>Name</th><th>Wage</th>";
	while ($areas = mysql_fetch_array($areasr)) {
		echo "<div id='vth'><th>" . substr($areas[0],0,6) . "</th></div>";	// -- TODO vertical align th, static col width
	}
	echo "</th><th>OT</th><th>PTO used</th><th>PTO new</th><th>Total</th></tr></thead>\n<tbody>\n";
	$PTOnew = array();
	while ($row = mysql_fetch_assoc($namesr)) {
		
		$totalq = "SELECT SUM(hours) FROM ".DB_LOGNAME.".timesheet WHERE periodID >= $periodID AND periodID <= $end AND emp_no = ".$row['emp_no'];
		$totalr = mysql_query($totalq);
		$total = mysql_fetch_row($totalr);
		$color = ($total[0] > (80 * $periodct)) ? "FF0000" : "000000";
		echo "<tr><td>".ucwords($row['FirstName'])." - " . ucwords(substr($row['FirstName'],0,1)) . ucwords(substr($row['LastName'],0,1)) . "</td><td align='right'>$" . $row['pay_rate'] . "</td>";
		$total0 = (!$total[0]) ? 0 : number_format($total[0],2);
		//
		//	LABOR DEPARTMENT TOTALS
		
		// $areasq = "SELECT ShiftName, ShiftID FROM ".DB_LOGNAME.".shifts WHERE visible = 1 ORDER BY ShiftOrder";
		$areasr = mysql_query($areasq);
		while ($areas = mysql_fetch_array($areasr)) {
			$emp_no = $row['emp_no'];
			$area = $areas[1];
			$depttotq = "SELECT SUM(t.hours) FROM is4c_log.timesheet t WHERE t.periodID >= $periodID AND t.periodID <= $end AND t.emp_no = $emp_no AND t.area = $area";
			// echo $depttotq;
			$depttotr = mysql_query($depttotq);
			$depttot = mysql_fetch_row($depttotr);
			$depttotal = (!$depttot[0]) ? 0 : number_format($depttot[0],2);
			echo "<td align='right'>" . $depttotal . "</td>";
		}
		//	END LABOR DEPT. TOTALS
		
		// 
		//	OVERTIME
		// 
		foreach ($p as $v) {
			$weekoneQ = "SELECT ROUND(SUM(hours), 2) FROM is4c_log.timesheet AS t
		        INNER JOIN is4c_log.payperiods AS p ON (p.periodID = t.periodID)
		        WHERE t.emp_no = " . $row['emp_no'] . "
		        AND t.periodID = $v
		        AND t.area <> 31
		        AND t.date >= DATE(p.periodStart)
		        AND t.date < DATE(date_add(p.periodStart, INTERVAL 7 day))";

		    $weektwoQ = "SELECT ROUND(SUM(hours), 2)
		        FROM is4c_log.timesheet AS t
		        INNER JOIN is4c_log.payperiods AS p
		        ON (p.periodID = t.periodID)
		        WHERE t.emp_no = " . $row['emp_no'] . "
		        AND t.periodID = $v
		        AND t.area <> 31
		        AND t.date >= DATE(date_add(p.periodStart, INTERVAL 7 day)) AND t.date <= DATE(p.periodEnd)";

		    $weekoneR = mysql_query($weekoneQ);
		    $weektwoR = mysql_query($weektwoQ);

		    list($weekone) = mysql_fetch_row($weekoneR);
		    if (is_null($weekone)) $weekone = 0;
		    list($weektwo) = mysql_fetch_row($weektwoR);
		    if (is_null($weektwo)) $weektwo = 0;

			if ($weekone > $ft) $otime1 = $weekone - $ft;
			if ($weektwo > $ft) $otime2 = $weektwo - $ft;
			$otime = $otime + $otime1 + $otime2;
		
		}
		$OT[] = $otime;
		echo "<td align='right'>" . $otime . "</td>";
		$otime = 0;
		$otime1 = 0;
		$otime2 = 0;
		// 	END OVERTIME

		//
		//	PTO USED
		$usedQ = "SELECT SUM(hours) FROM ".DB_LOGNAME.".timesheet WHERE periodID >= $periodID AND periodID <= $end AND emp_no = ".$row['emp_no']." AND area = 31";
		$usedR = mysql_query($usedQ);
		$ptoused = mysql_fetch_row($usedR);
		$PTOuse = (!$ptoused[0]) ? 0 : number_format($ptoused[0],2);
		echo "<td align='right'>$PTOuse</td>";
		
		//
		//	PTO CALC
		$nonPTOtotalq = "SELECT SUM(hours) FROM ".DB_LOGNAME.".timesheet WHERE periodID >= $periodID AND periodID <= $end AND area <> 31 AND emp_no = ".$row['emp_no'];
		$nonPTOtotalr = mysql_query($nonPTOtotalq);
		$nonPTOtotal = mysql_fetch_row($nonPTOtotalr);
		$ptoAcc = ($row['JobTitle'] == 'STAFF') ? $nonPTOtotal[0] * 0.075 : 0;
		echo "<td align='right'>" . number_format($ptoAcc,2) . "</td>";
		$PTOnew[] = $ptoAcc;
		
		//
		//	TOTAL		
		echo "<td align='right'><font style='color: $color; font-weight:bold;'>" . $total0 . "</font></td>";		
		
		echo "</tr>";
	}
	echo "<tr><td colspan=2><b>TOTALS</b></td>";

	$areasr = mysql_query($areasq);
	$TOT = array();
	while ($areas = mysql_fetch_array($areasr)) {
		$query = "SELECT ROUND(SUM(t.hours),2) FROM is4c_log.timesheet t 
			WHERE t.periodID BETWEEN $periodID AND $end
			AND t.area = " . $areas[1];
		// echo $query;
		$totsr = mysql_query($query);
		$tots = mysql_fetch_row($totsr);
		$tot = (!$tots[0] || $tots[0] == '') ? '0' : $tots[0];
		echo "<td align='right'><b>$tot</b></td>";
		$TOT[] = $tot;
	}

	$ptoq = "SELECT ROUND(SUM(t.hours),2) FROM is4c_log.timesheet t 
		WHERE t.periodID BETWEEN $periodID AND $end
		AND t.area = 31";
	$ptor = mysql_query($ptoq);
	$pto = mysql_fetch_row($ptor);


	$OTTOT = number_format(array_sum($OT),2);
	echo "<td><b>$OTTOT</b></td>";

	$PTOUSED = (!$pto[0] || $pto[0] == '') ? '0' : $pto[0];
	echo "<td><b>$PTOUSED</b></td>";
	
	$PTOTOT = number_format(array_sum($PTOnew),2);
	echo "<td><b>$PTOTOT</b></td>";
	
	$TOTAL = number_format(array_sum($TOT),2);
	echo "<td><b>$TOTAL</b></td>";
	
	echo"</tr>";
	echo "</tbody></table>\n";
}

include('../../../src/footer.php');
// echo "<script>$('#vth th').html($('#vth th').text().replace(/(.)/g,\"$1<br />\"));</script>";
?>
