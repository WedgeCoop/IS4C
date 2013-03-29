<?php
	if ($_GET['sflag'] != '') {
		$link = mysql_connect('server', 'user', 'pass');
		if ($link) {
			$data = mysql_select_db('shelfaudit');
			if ($data) {
				$query='SELECT MAX(`section`)+1 AS `new_section` FROM (SELECT `section` FROM `hbc_inventory` UNION SELECT 0 AS `section`) tmp_table;';
				$result = mysql_query($query);
				if ($result) { 
					$row = mysql_fetch_array($result);
					$query='ALTER TABLE `hbc_inventory` ALTER `section` SET DEFAULT '.$row['new_section'].';';
					$result = mysql_query($query);
					if ($result) { $status = 'good - section changed';
					} else { $status = 'bad - cannot update section'; }
				} else { $status = 'bad - cannot find old section'; }
			} else { $status = 'bad - IT problem'; }
		} else { $status = 'bad - cannot connect'; }
	} else if ($_GET['minput'] != '') {
		$link = mysql_connect('server', 'user', 'pass');
		if ($link) {
			$data = mysql_select_db('shelfaudit');
			if ($data) {
				if ($_GET['isbnflag']=='1') {
					$upc=str_pad(substr($_GET['minput'],0,12), 13, '0', STR_PAD_LEFT);
				} else {
					$upc=str_pad(substr($_GET['minput'],0,11), 13, '0', STR_PAD_LEFT);
				}
				
/* Short tag rules */
	if (strcmp('0000000',substr($upc,0,7))==0) {
		switch ($upc[12]) {
			case '0':
				$upc='00'.substr($upc,6,3).'00000'.substr($upc,10,3);
			break;
			case '1':
				$upc='00'.substr($upc,6,3).'10000'.substr($upc,10,3);
			break;
			case '2':
				$upc='00'.substr($upc,6,3).'20000'.substr($upc,10,3);
			break;
			case '3':
				$upc='00'.substr($upc,6,4).'00000'.substr($upc,10,2);
			break;
			case '4':
				$upc='00'.substr($upc,6,5).'00000'.substr($upc,11,1);
			break;
			default:
				$upc='00'.substr($upc,6,6).'0000'.substr($upc,12,1);
			break;
		}
	}

/*
 * Strip the z from qinput. Quick hack version
 */
				if (!ctype_digit($_GET['qinput'])) { $_GET['qinput']=substr($_GET['qinput'], 0, strlen($_GET['qinput'])-1); }
	
				if ($_GET['qinput'] != '' && ctype_digit($_GET['qinput'])) {
					$quantity=$_GET['qinput'];
					$query='INSERT INTO `shelfaudit`.`hbc_inventory` (`id`,`datetime`,`upc`,`clear`,`quantity`) VALUES (NULL, NOW(), \''.$upc.'\', \'0\', \''.$quantity.'\');';
				} else if ($_GET['qinput'] != '') {
					$split=strpos($_GET['qinput'],'s');
					$quantity=substr($_GET['qinput'],0,$split);
					$section=substr($_GET['qinput'],$split+1);
					$query='INSERT INTO `shelfaudit`.`hbc_inventory` (`id`,`datetime`,`upc`,`clear`,`quantity`,`section`) VALUES (NULL, NOW(), \''.$upc.'\', \'0\', \''.$quantity.'\',\''.$section.'\');';
				} else {
					$query='INSERT INTO `shelfaudit`.`hbc_inventory` (`id`,`datetime`,`upc`,`clear`,`quantity`) VALUES (NULL, NOW(), \''.$upc.'\', \'0\', \'1\');';
				}
				$result = mysql_query($query);
				if ($result) { $status = 'good - scan entered:'.$_GET['minput'].'';	
				}	else { $status = 'bad - strange scan:'.$query; }
			} else { $status = 'bad - IT problem'; }
		} else { $status = 'bad - cannot connect'; }
	} else { $status = 'waiting - no input'; }
?>
<html>
	<body onload="readinput();">
		<center>
			<form name="mForm" id="mid" action="index.php" method="get">
				<input name="minput" type="text" value=""/>
				<input name="isbnflag" type="hidden" value=""/>
				<input name="qinput" type="text" value="1"/>
				<input type="submit" value="enter"/>
			</form>
			<form name="sForm" id="sid" action="index.php" method="get">
				<input name="sflag" type="hidden" value="1"/>
				<input type="submit" value="new section"/>
			</form>
			<div>scan or type upc</div>
			<script type="text/javascript">
function waitforz() {
	if (document.forms[0]) {
		var qinputvalue = document.forms[0].qinput.value;

		if (qinputvalue.charAt(qinputvalue.length - 1) == 'z') {
			document.forms[0].submit();
		}	else {
			t=setTimeout("waitforz()",1000);
		}
	}
}
		
function readinput() {
	if (document.forms[0]) {
		var inputvalue = document.forms[0].minput.value;
				
		if (inputvalue.length == 12) {
			document.forms[0].qinput.value="";
			document.forms[0].qinput.focus();
			waitforz();
		} else if (inputvalue.length == 13) {
			document.forms[0].isbnflag.value="1";
			document.forms[0].qinput.value="";
			document.forms[0].qinput.focus();
			waitforz();
		} else {
			document.forms[0].minput.focus();
			t=setTimeout("readinput()",1000);
		}
	} else {
	}
}
			</script>
			<div>status: <?php echo($status); ?></div>
			<div><strong>Using Group 1</strong></div>
			<div style="font-size: x-small; padding-top: .5em;"><a href="../hbc2">Switch to Group 2</a></div>
		</center>
	</body>
</html>