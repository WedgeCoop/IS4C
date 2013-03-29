<?php
	setlocale(LC_MONETARY, 'en_US');

	if ($_GET['delete']=='yes') {
		$link=mysql_connect('server', 'user', 'pass');
		$query='
delete from `shelfaudit`.`hbc_inventory` where `id`=\''.mysql_real_escape_string($_GET['id']).'\' limit 1;';
		$result=mysql_query($query);
		if ($result) {
			$sql_actions='Deleted record.';
		} else {
			$sql_actions='Unable to delete record, please try again. <!-- '.$query.' -->';
		}
	} else if ($_GET['clear']=='yes') {
		$link=mysql_connect('server', 'user', 'pass');
		$query='update `shelfaudit`.`hbc_inventory` set `clear`=1;';
		$result=mysql_query($query);
		if ($result) {
			$sql_actions='Cleared old scans.';
			header ("Location: index.php");
		} else {
			$sql_actions='Unable to clear old scans, try again. <!-- '.$query.' -->';
		}
	} else if ($_GET['change']=='yes') {
	}

	if (isset($_GET['view']) && $_GET['view']=='dept') {
		$order='`d`.`dept_no`,`sub`.`section`,`sub`.`datetime`';
	} else {
		$order='`sub`.`section`,`d`.`dept_no`,`sub`.`datetime`';
	}
	
	$link = mysql_connect('server', 'user', 'pass');

	if ($link) {
		$data = mysql_select_db('shelfaudit');
		if ($data) {
			$t=true;
			
			$q='START TRANSACTION';
			$r=mysql_query($q, $link);
			$t=&$r;
			
			$q='CREATE TEMPORARY TABLE `shelfaudit`.`tLastModified` (`upc` VARCHAR(13) NOT NULL, `modified` DATETIME NOT NULL, KEY `upc_modified` (`upc`,`modified`)) ENGINE = MYISAM';
			$r=mysql_query($q, $link);
			$t=&$r;
						
			$q='SELECT `upc`, `datetime` FROM `shelfaudit`.`hbc_inventory` WHERE CLEAR!=1';
			$r=mysql_query($q, $link);
			$t=&$r;
						
			$scans=array();
			
			while ($row=mysql_fetch_assoc($r)) {
				array_push($scans, array($row['upc'], $row['datetime']));
			}
			
			foreach ($scans as $scan) {
				$q='INSERT INTO `shelfaudit`.`tLastModified` SELECT \''.$scan[0].'\', MAX(`modified`) FROM `wedgepos`.`itemTableLog` WHERE `upc`=\''.$scan[0].'\'';
				$r=mysql_query($q, $link);
				$t=&$r;
			}
			
			$q='
SELECT
	`sub`.`id`,
	`sub`.`datetime`,
	`sub`.`upc`,
	`sub`.`quantity`,
	`sub`.`section`,
	`rejoin`.`item_desc`,
	`d`.`dept_name`,
	`d`.`dept_no`,

	CASE WHEN (`rejoin`.`promoActive`=1 AND DATE(`sub`.`datetime`)<=`rejoin`.`promoEnd` AND `sub`.`datetime`>`rejoin`.`promoStart`) THEN `rejoin`.`promoRetail`
		ELSE `retail`
	END AS \'retail\',
	
	CASE WHEN (`rejoin`.`promoActive`=1 AND DATE(`sub`.`datetime`)<=`rejoin`.`promoEnd` AND `sub`.`datetime`>`rejoin`.`promoStart` AND `rejoin`.`memberspecial`=1) THEN \'M\'
		WHEN (`rejoin`.`promoActive`=1 AND DATE(`sub`.`datetime`)<=`rejoin`.`promoEnd` AND `sub`.`datetime`>`rejoin`.`promoStart`) THEN \'S\'
		ELSE \'\'
	END AS \'retailstatus\'

	FROM (
		SELECT
			`hbc_inventory`.`id`,
			`hbc_inventory`.`datetime`,
			`tLastModified`.`upc`,
			`hbc_inventory`.`quantity`,
			`hbc_inventory`.`section`,
			`tLastModified`.`modified`
			FROM `shelfaudit`.`hbc_inventory` 
			INNER JOIN `shelfaudit`.`tLastModified` ON `hbc_inventory`.`upc`=`tLastModified`.`upc`
			WHERE `clear`!=1
			GROUP BY `hbc_inventory`.`datetime`,`hbc_inventory`.`upc`,`hbc_inventory`.`quantity`,`hbc_inventory`.`section`,`tLastModified`.`upc`,`tLastModified`.`modified`
	) sub
	JOIN `wedgepos`.`itemTableLog` rejoin
		ON `sub`.`upc`=`rejoin`.`upc` AND `sub`.`modified`=`rejoin`.`modified`
	JOIN `wedgepos`.`departments` d
		ON `rejoin`.`dept`=`d`.`dept_no`
ORDER BY '.$order.'';
				$r=mysql_query($q, $link);
				$t=&$r;
				
			if ($t) {
				$status = 'Good - Connected';
				$num_rows=mysql_num_rows($r);
				if ($num_rows>0) {
					$scans=array();
					for($i=0;$i<$num_rows;$i++) {
						$row=mysql_fetch_array($r);
						array_push($scans, $row);
					}
				} else {
					$status = 'Good - No scans';
				}
				$q='ROLLBACK';
				$r=mysql_query($q, $link);			
				
			} else {
				$q='ROLLBACK';
				$r=mysql_query($q, $link);			
				
				$status = 'Bad - IT problem';
			}
		} else {
			$status = 'Bad - IT problem';
		}
	} else {
		$status = 'Bad - IT problem';
	}

?>
<html>
	<head>
		<style>
body {
 width: 768px;
 margin: auto;
 font-family: Helvetica, sans, Arial, sans-serif;
 background-color: #F9F9F9;
}

#bdiv {
	width: 768px;
	margin: auto;
	text-align: center;
}

body p,
body div {
 border: 1px solid #CfCfCf;
 background-color: #EFEFEF;
 line-height: 1.5;
 margin: 0px;
}

body table {
 font-size: small;
 text-align: center;
 border-collapse: collapse;
 width: 100%;
}

body table caption {
 font-family: sans-mono, Helvetica, sans, Arial, sans-serif;
 margin-top: 1em;
}

body table th {
 border-bottom: 2px solid #090909;
}

table tr:hover {
 background-color:#CFCFCF;
}

.right {
 text-align: right;
}
.small {
 font-size: smaller;
}
#col_a {
 width: 150px;
}
#col_b {
 width: 100px;
}
#col_c {
 width: 270px;
}
#col_d {
 width: 40px;
}
#col_e {
 width: 60px;
}
#col_f {
 width: 20px;
}
#col_g {
 width: 80px;
}
#col_h {
 width: 48px;
}
		</style>
	</head>
	<body>
		<div id="bdiv">
			<p><a href="#" onclick="window.open('../','scan','width=320, height=200, location=no, menubar=no, status=no, toolbar=no, scrollbars=no, resizable=no');">Enter a new scan</a></p>
			<p><?php echo($sql_actions); ?></p>
			<p><?php echo($status); ?></p>
			<p><a href="?view=dept">view by wedge section</a> <a href="index.php">view by scanned section</a> <a href="../../hbc2/review">Switch to Group 2</a></p>
<?php
	if ($scans) {
		$clear = '
		<div><a href="index.php?clear=yes">Clear Old</a></div>';
		
		foreach($scans as $row) {
			if (isset($_GET['view']) && $_GET['view']=='dept') {
				$counter='d';
			} else {
				$counter='s';
			}
			
			if (!isset($counter_number)) {
				if ($counter=='d') { $counter_number=$row['dept_no']; }
				else { $counter_number=$row['section']; }
				
				$counter_total=$row['quantity']*$row['retail'];
				
				if ($counter=='d') { $caption=$row['dept_name'].' Department'; }
				else { $caption='Section #'.$row['section']; }
				
				$table = '
		<table>
			<caption>'.$caption.'</caption>
			<thead>
				<tr>
					<th>Date+Time</th>
					<th>UPC</th>
					<th>Description</th>
					<th>Qty</th>
					<th>Each</th>
					<th>Sale</th>
					<th>Total</th>
					<th>Delete</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td id="col_a" class="small">'.$row['datetime'].'</td>
					<td id="col_b">'.$row['upc'].'</td>
					<td id="col_c">'.$row['item_desc'].'</td>
					<td id="col_d" class="right">'.$row['quantity'].'</td>
					<td id="col_e" class="right">'.money_format('%.2n', $row['retail']).'</td>
					<td id="col_f">'.(($row['retailstatus'])?$row['retailstatus']:'&nbsp;').'</td>
					<td id="col_g" class="right">'.money_format('%!.2n', ($row['quantity']*$row['retail'])).'</td>
					<td id="col_h"><a href="index.php?delete=yes&id='.$row['id'].'"><img src="../../../images/cancel.png" border="0"/></a></td>
				</tr>';
			} else if ($counter_number!=$row['section'] && $counter_number!=$row['dept_no']) {
				if ($counter=='d') { $counter_number=$row['dept_no']; }
				else { $counter_number=$row['section']; }
				
				if ($counter=='d') { $caption=$row['dept_name'].' Department'; }
				else { $caption='Section #'.$row['section']; }
								
				$table .= '
			</tbody>
			<tfoot>
				<tr>
					<td colspan=6>&nbsp;</td>
					<td class="right">'.money_format('%.2n', $counter_total).'</td>
					<td>&nbsp;</td>
				</tr>
			</tfoot>
		</table>
		<table>
			<caption>'.$caption.'</caption>
			<thead>
				<tr>
					<th>Date+Time</th>
					<th>UPC</th>
					<th>Description</th>
					<th>Qty</th>
					<th>Each</th>
					<th>Sale</th>
					<th>Total</th>
					<th>Delete</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td id="col_a" class="small">'.$row['datetime'].'</td>
					<td id="col_b">'.$row['upc'].'</td>
					<td id="col_c">'.$row['item_desc'].'</td>
					<td id="col_d" class="right">'.$row['quantity'].'</td>
					<td id="col_e" class="right">'.money_format('%.2n', $row['retail']).'</td>
					<td id="col_f">'.(($row['retailstatus'])?$row['retailstatus']:'&nbsp;').'</td>
					<td id="col_g" class="right">'.money_format('%!.2n', ($row['quantity']*$row['retail'])).'</td>
					<td id="col_h"><a href="index.php?delete=yes&id='.$row['id'].'"><img src="../../../images/cancel.png" border="0"/></a></td>
				</tr>';
				
				$counter_total=$row['quantity']*$row['retail'];
			} else {
				$counter_total+=$row['quantity']*$row['retail'];
				
				$table .= '
				<tr>
					<td id="col_a" class="small">'.$row['datetime'].'</td>
					<td id="col_b">'.$row['upc'].'</td>
					<td id="col_c">'.$row['item_desc'].'</td>
					<td id="col_d" class="right">'.$row['quantity'].'</td>
					<td id="col_e" class="right">'.money_format('%.2n', $row['retail']).'</td>
					<td id="col_f">'.(($row['retailstatus'])?$row['retailstatus']:'&nbsp;').'</td>
					<td id="col_g" class="right">'.money_format('%!.2n', ($row['quantity']*$row['retail'])).'</td>
					<td id="col_h"><a href="index.php?delete=yes&id='.$row['id'].'"><img src="../../../images/cancel.png" border="0"/></a></td>
				</tr>';
			}
		}
	
		$table .= '
			</tbody>
			<tfoot>
				<tr>
					<td colspan=6>&nbsp;</td>
					<td class="right">'.money_format('%.2n', $counter_total).'</td>
					<td>&nbsp;</td>
				</tr>
			</tfoot>
		</table>
';
		print_r($clear);
		print_r($table);
	}
?>
		</div>
	</body>
</html>
