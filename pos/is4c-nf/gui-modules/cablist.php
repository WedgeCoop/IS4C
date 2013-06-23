<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists("NoInputPage")) include_once($IS4C_PATH."gui-class-lib/NoInputPage.php");
if (!function_exists("tDataConnect")) include($IS4C_PATH."lib/connect.php");
if (!function_exists("receipt")) include($IS4C_PATH."lib/clientscripts.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class cablist extends NoInputPage {

	function head_content(){
		global $IS4C_PATH;
		?>
		<script type="text/javascript" >
		var prevKey = -1;
		var prevPrevKey = -1;
		function processkeypress(e) {
			var jsKey;
			if (e.keyCode) // IE
				jsKey = e.keyCode;
			else if(e.which) // Netscape/Firefox/Opera
				jsKey = e.which;
			if (jsKey==13) {
				if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
				(prevKey == 108 || prevKey == 76) ){ //CL<enter>
					$('#selectlist option:selected').val('');	
				}
				submitWrapper();
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
		}
		function submitWrapper(){
			var ref = $('#selectlist').val();
			if (ref != ""){
				$.ajax({
					url: '<?php echo $IS4C_PATH; ?>ajax-callbacks/ajax-cabreceipt.php',
					type: 'get',
					cache: false,
					data: 'input='+ref,
					success: function(){
						location='<?php echo $IS4C_PATH; ?>gui-modules/pos2.php';
					}
				});
			}
			else {
				location='<?php echo $IS4C_PATH; ?>gui-modules/pos2.php';
			}

			return false;
		}
		</script> 
		<?php
		$this->add_onload_command("\$('#selectlist').keypress(processkeypress);\n");
		$this->add_onload_command("\$('#selectlist').focus();\n");
	}
	
	function body_content(){
		global $IS4C_LOCAL;

		$db = pDataConnect();
		$query = "SELECT frontendsecurity FROM employees WHERE emp_no=".$IS4C_LOCAL->get("CashierNo");
		$result = $db->query($query);
		$fes = 0;
		if ($db->num_rows($result) > 0)
			$fes = array_pop($db->fetch_row($result));

		/* if front end security >= 25, pull all
		 * available receipts; other wise, just
		 * current cashier's receipt */

		$result = -1;
		if ($fes >= 25){
			$query = "select emp_no, register_no, trans_no, sum((case when trans_type = 'T' then -1 * total else 0 end)) as total "
			."from localtranstoday "
			." group by register_no, emp_no, trans_no
			having sum((case when trans_type='T' THEN -1*total ELSE 0 end)) >= 30
			order by register_no,emp_no,trans_no desc";
			$db = tDataConnect();
			if ($IS4C_LOCAL->get("standalone") == 0){
				$query = str_replace("localtranstoday","dtransactions",$query);
				$db = mDataConnect();
			}
			$result = $db->query($query);

		}
		else {
			$query = "select emp_no, register_no, trans_no, sum((case when trans_type = 'T' then -1 * total else 0 end)) as total "
			."from localtranstoday where register_no = ".$IS4C_LOCAL->get("laneno")." and emp_no = ".$IS4C_LOCAL->get("CashierNo")
			." group by register_no, emp_no, trans_no
			having sum((case when trans_type='T' THEN -1*total ELSE 0 end)) >= 30
			order by trans_no desc";

			$db = tDataConnect();
			$result = $db->query($query);
		}

		$num_rows = $db->num_rows($result);
		?>

		<div class="baseHeight">
		<div class="listbox">
		<form name="selectform" onsubmit="return submitWrapper();">
		<select name="selectlist" size="10" onblur="$('#selectlist').focus()"
			id="selectlist">

		<?php
		$selected = "selected";
		for ($i = 0; $i < $num_rows; $i++) {
			$row = $db->fetch_array($result);
			echo "<option value='".$row["emp_no"]."-".$row["register_no"]."-".$row["trans_no"]."'";
			echo $selected;
			echo ">lane ".substr(100 + $row["register_no"], -2)." Cashier ".$row["emp_no"]
				." #".$row["trans_no"]." -- $".$row["total"];
			$selected = "";
		}
		if ($num_rows == 0){
			echo "<option value=\"\">None found</option>";
		}
		$db->close();
		?>

		</select>
		</form>
		</div>
		<div class="listboxText centerOffset">
		use arrow keys to navigate<br />[enter] to reprint receipt<br />[clear] to cancel
		</div>
		<div class="clear"></div>
		</div>

		<?
		$IS4C_LOCAL->set("scan","noScan");
	} // END body_content() FUNCTION
}

new cablist();

?>
