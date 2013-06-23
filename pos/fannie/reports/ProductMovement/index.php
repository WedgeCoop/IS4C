<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');

if (isset($_GET['date1'])){
	$date1 = $_GET['date1'];
	$date2 = $_GET['date2'];
	$upc = $_GET['upc'];
	if (is_numeric($upc))
		$upc = str_pad($upc,13,'0',STR_PAD_LEFT);

	$sort = "year(t.tdate),month(t.tdate),day(t.tdate)";
	if (isset($_GET['sort']))
		$sort = $_GET['sort'];
	$dir = "ASC";
	if (isset($_GET['dir'])){
		$dir = $_GET['dir'];
	}
	$otherdir = 'DESC';
	if ($dir == $otherdir)
		$otherdir = 'ASC';

	// because a series of datepart()s are being used to construct the date,
	// EACH one has to be sorted in the right direction
	$fixedsort = preg_replace("/\),/",") $dir, ",$sort);

	$dlog = select_dlog($date1,$date2);


	if (isset($_GET['excel'])){
	  header('Content-Type: application/ms-excel');
	  header('Content-Disposition: attachment; filename="movementReport'.$upc.'.xls"');
	}

	$query = "select month(t.tdate),day(t.tdate),year(t.tdate),
		  t.upc,p.description,
		       sum(case when t.trans_status in ('M') then t.itemqtty 
		           else t.quantity end) 
		  qty,
		  sum(t.total) from
		  $dlog as t left join products as p on t.upc = p.upc 
		  where t.upc = '$upc' AND
		  tdate BETWEEN '$date1 00:00:00' AND '$date2 23:59:59'
		  group by year(t.tdate),month(t.tdate),day(t.tdate),
		  t.upc,p.description
		  order by $fixedsort $dir";
	if (strtolower($upc) == "rrr"){
		if ($dlog == "dlog_90_view" || $dlog=="dlog_15")
			$dlog = "transarchive";
		else {
			list($y,$m,$d) = explode("-",$date1);
			$dlog = "trans_archive.dbo.transArchive".$y.$m;
		}

		$query = "select datepart(mm,datetime),datepart(dd,datetime),datepart(yy,datetime),
			upc,'RRR',sum(case when volSpecial is null then 0 else volSpecial end) as qty,
			sum(t.total) from
			$dlog as t
			where upc = '$upc'
			and datediff(dd,datetime,'$date1') <= 0
			and datediff(dd,datetime,'$date2') >= 0
			and emp_no <> 9999 and register_no <> 99
			and trans_status <> 'X'
			group by datepart(yy,datetime),datepart(mm,datetime),datepart(dd,datetime),upc
			order by datepart(mm,datetime),datepart(dd,datetime)";
	}
	//echo $query;
	$result = $dbc->query($query);

	// make headers sort links
	$today = date("F d, Y");	
	//Following lines creates a header for the report, listing sort option chosen, report date, date and department range.
	echo "Report summed by ";
	echo "date on ";
	echo "</br>";
	echo $today;
	echo "</br>";
	echo "From ";
	print $date1;
	echo " to ";
	print $date2;
	echo "</br>";

	if (!isset($_GET['excel'])){
		echo "<a href=movementProd.php?date1=$date1&date2=$date2&upc=$upc&sort=$sort&dir=$dir&excel=yes>Save</a> to Excel<br />";
	}

	echo "<table cellpadding=2 cellspacing=0 border=1>";
	echo "<tr>";
	if (!isset($_GET['excel'])){
		if ($sort == "datepart(yy,t.tdate),datepart(mm,t.tdate),datepart(dd,t.tdate)"){
			echo "<th><a href=movementProd.php?date1=".$date1."&date2=".$date2."&upc=".$upc."&sort=".$sort."&dir=".$otherdir.">Date</a></th>";
		}
		else {
			echo "<th><a href=movementProd.php?date1=".$date1."&date2=".$date2."&upc=".$upc."&sort=datepart(yy,t.tdate),datepart(mm,t.tdate),datepart(dd,t.tdate)&dir=ASC>Date</a></th>";
		}
		echo "<th>UPC</th><th>Description</th>";
		if ($sort == "sum(t.quantity)"){
			echo "<th><a href=movementProd.php?date1=".$date1."&date2=".$date2."&upc=".$upc."&sort=".$sort."&dir=".$otherdir.">Qty</a></th>";
		}
		else {
			echo "<th><a href=movementProd.php?date1=".$date1."&date2=".$date2."&upc=".$upc."&sort=sum(t.quantity)&dir=DESC>Qty</a></th>";
		}
		if ($sort == "sum(t.total)"){
			echo "<th><a href=movementProd.php?date1=".$date1."&date2=".$date2."&upc=".$upc."&sort=".$sort."&dir=".$otherdir.">Sales</a></th>";
		}
		else {
			echo "<th><a href=movementProd.php?date1=".$date1."&date2=".$date2."&upc=".$upc."&sort=sum(t.total)&dir=DESC>Sales</a></th>";
		}
	}
	else {
		echo "<th>Date</th><th>Qty</th><th>Sales</th>";
	}
	$sumQty = 0.0;
	$sumSales = 0.0;
	while ($row = $dbc->fetch_array($result)){
		echo "<tr>";
		echo "<td>".$row[0]."/".$row[1]."/".$row[2]."</td>";
		echo "<td>".$row[3]."</td>";
		echo "<td>".$row[4]."</td>";
		echo "<td>".$row[5]."</td>";
		echo "<td>".$row[6]."</td>";
		echo "</tr>";	
		$sumQty += $row[5];
		$sumSales += $row[6];
	}
	echo "<tr><th>Total</th><td colspan=2>&nbsp;</td>";
	echo "<td>$sumQty</td><td>$sumSales</td></tr>";
	echo "</table>";

	return;
}

$page_title = "Fannie : Product Movement";
$header = "Product Movement Report";
include($FANNIE_ROOT.'src/header.html');
?>
<script src="../../src/CalendarControl.js"
        type="text/javascript"></script>
</head>
<div id=main>	
<form method = "get" action="index.php">
	<table border="0" cellspacing="0" cellpadding="5">
		<!--<tr>
			<td bgcolor="#CCFF66"><a href="csvQuery.php"><font color="#CC0000">Click 
here to create Excel Report</font></a></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>-->
		<tr> 
			<td> <p><b>UPC</b></p>
			<p><b>Excel</b></p>
			</td>
			<td><p>
			<input type=text name=upc id=upc  />
			</p>
			<p>
			<input type=checkbox name=excel id=excel /> 
			</p>
			</td>

			 <td>
			<p><b>Date Start</b> </p>
		         <p><b>End</b></p>
		       </td>
		            <td>
		             <p>
		               <input type=text size=25 name=date1 onfocus="this.value='';showCalendarControl(this);">
		               </p>
		               <p>
		                <input type=text size=25 name=date2 onfocus="this.value='';showCalendarControl(this);">
		         </p>
		       </td>

		</tr>
		<!--<tr>
			<td> Select Dept/Buyer </td>
			<td colspan=3>
				<table width=100%><tr>
					<td><input type=radio name=buyer value=1>Bulk</td>
				       	<td><input type=radio name=buyer value=3>Cool</td>
				      	<td><input type=radio name=buyer value=4>Deli</td>
				      	<td><input type=radio name=buyer value=4>Grocery</td>
				      	<td><input type=radio name=buyer value=5>HBC</td></tr>
				      	<tr><td><input type=radio name=buyer value=6>Produce</td>
				      	<td><input type=radio name=buyer value=7>Marketing</td>
				      	<td><input type=radio name=buyer value=8>Meat</td>
				      	<td><input type=radio name=buyer value=9>Gen Merch</td>
				</tr></table>
			</td>
		</tr>-->
			<td> <input type=submit name=submit value="Submit"> </td>
			<td> <input type=reset name=reset value="Start Over"> </td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</table>
</form>
</div>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>




