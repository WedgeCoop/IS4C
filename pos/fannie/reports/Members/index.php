<?php

include('../../config.php');

include($FANNIE_ROOT.'src/mysql_connect.php');

$where = "(c.cardno < 5000 or c.cardno > 5999)
	and (c.cardno < 9000 or c.cardno > 9100)
	and c.memtype <> 2";
$type = isset($_REQUEST['type'])?$_REQUEST['type']:'Regular';
switch($type){
case 'Regular':
	break;
case 'Business':
	$where = "c.memtype = 2";
	break;
case 'Staff Members':
	$where = "c.memtype = 3";
	break;
case 'Staff NonMembers':
	$where = "c.memtype = 9";
	break;
case '#5000s':
	$where = "c.cardno BETWEEN 5000 AND 5999";
	break;
}

if (isset($_REQUEST['excel'])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="member report.xls"');
}
else {
	echo "<form action=index.php method=get><select name=type>";
	$types = array('Regular','Business','#5000s','Staff Members','Staff NonMembers');
	foreach($types as $t){
		printf("<option %s>%s</option>",
			($t==$type?'selected':''),$t);
	}
	echo "</select>";
	echo "&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<input type=submit name=submit value=Submit />";
	echo "&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<input type=checkbox name=excel /> Excel";
	echo "</form><hr />";
}

$q = "SELECT c.cardno,
		month(m.start_date),day(m.start_date),year(m.start_date),
		month(m.end_date),day(m.end_date),year(m.end_date),
		c.firstname,c.lastname,
		CASE WHEN s.type = 'I' THEN 1 ELSE 0 END AS isInactive,
		CASE WHEN r.textStr IS NULL THEN s.reason ELSE r.textStr END as reason,
		CASE WHEN n.payments IS NULL THEN 0 ELSE n.payments END as equity
	FROM custdata AS c LEFT JOIN memDates AS m
	ON m.card_no = c.cardno 
	LEFT JOIN newBalanceStockToday_test AS n
	ON m.card_no=n.memnum LEFT JOIN suspensions AS s
	ON m.card_no=s.cardno LEFT JOIN reasoncodes AS r
	ON s.reasonCode & r.mask <> 0
	WHERE c.type <> 'TERM' AND $where
	AND c.personNum=1
	ORDER BY c.cardno";
$r = $dbc->query($q);
echo "<table cellspacing=0 cellpadding=4 border=1>
	<tr><th>#</th><th>First Name</th><th>Last Name</th>
	<th>Start</th><th>End</th><th>Equity</th>
	<th>Inactive</th></tr>";
$saveW = array(-1);
while($w = $dbc->fetch_row($r)){
	if ($w[0] != $saveW[0]){
		printRow($saveW);
		$saveW = $w;
	}
	else {
		$saveW['reason'] .= ", ".$w['reason'];
	}
}
printRow($saveW);
echo "</table>";

function printRow($arr){
	global $_REQUEST;
	$ph = isset($_REQUEST['excel'])?'':"&nbsp;";
	if (count($arr) <= 1) return;
	printf("<tr><td>%d</td><td>%s</td><td>%s</td>
		<td>%d/%d/%d</td><td>%d/%d/%d</td>
		<td>%.2f</td><td>%s</td></tr>",
		$arr[0],$arr['firstname'],$arr['lastname'],
		$arr[1],$arr[2],$arr[3],
		$arr[4],$arr[5],$arr[6],
		$arr['equity'],
		($arr['isInactive']==1?'INACTIVE - '.$arr['reason']:$ph)
	);
}

?>
