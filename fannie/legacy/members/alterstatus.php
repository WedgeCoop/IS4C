<?php
include('../../config.php');

include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

include('memAddress.php');
include('header.html');

$memID="";
if(isset($_GET['memNum'])){
	$memID = $_GET['memNum'];
}else{
	$memID = $_POST['memNum'];
}
$memNum = $memID;

?>
<html>
<head>
</head>
<body 
	bgcolor="#66CC99" 
	leftmargin="0" topmargin="0" 
	marginwidth="0" marginheight="0" 
>

<table width="660" height="111" border="0" cellpadding="0" cellspacing="0" bgcolor="#66cc99">
  <tr>
    <td colspan="2"><h1><img src="../images/logoGrnBckSm.gif" width="50" height="47" /></h1></td>
    <!-- <td colspan="9" valign="middle"><font size="+3" face="Papyrus, Verdana, Arial, Helvetica, sans-serif">PI Killer</font></td>
  --> </tr>
  <tr>
    <td colspan="11" bgcolor="#006633"><a href="memGen.php?memID=<?php echo $memNum;?>"><img src="../images/general.gif" width="72" height="16" border="0" /></a><a href="memEquit.php?memID=<?php echo $memNum;?>"><img src="../images/equity.gif" width="72" height="16" border="0" /></a><a href="memAR.php?memID=<?php echo $memNum;?>"><img src="../images/AR.gif" width="72" height="16" border="0" /></a><a href="memControl.php?memID=<?php echo $memNum;?>"><img src="../images/control.gif" width="72" height="16" border="0" /></a><a href="memDetail.php?memID=<?php echo $memNum;?>"><img src="../images/detail.gif" width="72" height="16" border="0" /></a></td>
  </tr>
  <tr>
    <td colspan="9"><a href="mainMenu.php" target="_top" onclick="MM_nbGroup('down','group1','Members','../images/memDown.gif',1)" onmouseover="MM_nbGroup('over','Members','../images/memOver.gif','../images/memUp.gif',1)" onmouseout="MM_nbGroup('out')"><img src="../images/memDown.gif" alt="" name="Members" border="0" id="Members" onload="MM_nbGroup('init','group1','Members','../images/memUp.gif',1)" /></a><a href="javascript:;" target="_top" onclick="MM_nbGroup('down','group1','Reports','../images/repDown.gif',1)" onmouseover="MM_nbGroup('over','Reports','../images/repOver.gif','../images/repUp.gif',1)" onmouseout="MM_nbGroup('out')"><img src="../images/repUp.gif" alt="" name="Reports" width="81" height="62" border="0" id="Reports" onload="" /></a><a href="javascript:;" target="_top" onClick="MM_nbGroup('down','group1','Items','../images/itemsDown.gif',1)" onMouseOver="MM_nbGroup('over','Items','../images/itemsOver.gif','../images/itemsUp.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="Items" src="../images/itemsUp.gif" border="0" alt="Items" onLoad="" /></a><a href="javascript:;" target="_top" onClick="MM_nbGroup('down','group1','Reference','../images/refDown.gif',1)" onMouseOver="MM_nbGroup('over','Reference','../images/refOver.gif','../images/refUp.gif',1)" onMouseOut="MM_nbGroup('out')"><img name="Reference" src="../images/refUp.gif" border="0" alt="Reference" onLoad="" /></a></td>

</tr>
</table>

<?php 

$memNum = $memID;
if (!isset($_POST['submit']) && !isset($_GET['fixedaddress'])){
	echo "&nbsp;&nbsp;&nbsp;Reason for suspending membership $memNum<br />";
	echo "<form action=alterstatus.php method=post>";
	echo "<input type=hidden name=memNum value=$memID>";
	$curReasonCode = array_pop($sql->fetch_row($sql->query("SELECT reasonCode from suspensions WHERE cardno=$memNum")));
	$curType = array_pop($sql->fetch_row($sql->query("SELECT type FROM custdata WHERE cardno=$memNum AND personnum=1")));
	$stats = array('INACT'=>'Inactive','TERM'=>'Termed','INACT2'=>'Term pending');
	echo "<select name=status>";
	foreach ($stats as $k=>$v){
		echo "<option value=".$k;
		if ($k == $curType) echo " selected";
		echo ">".$v."</option>";
	}
	echo "</select>";
	$query = "select textStr,mask from reasoncodes";
	$result = $sql->query($query);
	echo "<table>";
	while($row = $sql->fetch_row($result)){
	  echo "<tr><td><input type=checkbox name=reasoncodes[] value=$row[1]";
	  if ($curReasonCode & ((int)$row[1])) echo " checked";
	  echo " /></td><td>$row[0]</td></tr>";
	}
	echo "</table>";
	echo "<input type=submit name=submit value=Update />";
	echo "</form>";
}
else if (validateUserQuiet('editmembers')){
	$memNum = $_POST["memNum"];
	$codes = array();
	if (isset($_POST["reasoncodes"]))
		$codes = $_POST["reasoncodes"];
	$status = $_POST["status"];

	$reasonCode = 0;
	foreach($codes as $c)
		$reasonCode = $reasonCode | ((int)$c);
	
	alterReason($memNum,$reasonCode,$status);

	addressList($memNum);

	// FIRE ALL UPDATE
	include('custUpdates.php');
	updateCustomerAllLanes($memNum);

}
else if (validateUserQuiet('editmembers_csc') && isset($_GET['fixedaddress'])){
	$curQ = "select reasoncode from suspensions where cardno=$memNum";
	$curR = $sql->query($curQ);
	$curCode = (int)(array_pop($sql->fetch_array($curR)));

	$newCode = $curCode & ~16;
	alterReason($memNum,$newCode);

	addressList($memNum);

	// FIRE ALL UPDATE
	include('custUpdates.php');
	updateCustomerAllLanes($memNum);
}

?>

<table>
<tr>
<td><a href="testEdit.php?memnum=<?php echo $memNum; ?> ">
Edit Info</a>
</td>
<td>
&nbsp;
</td>
</tr>
</table>
</body>
</html>
