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
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class suspendedlist extends NoInputPage {
	var $temp_result;
	var $temp_num_rows;
	var $temp_db;

	function head_content(){
		?>
		<script type="text/javascript" >
		var prevKey = -1;
		var prevPrevKey = -1;
		function processkeypress(e) {
			var jsKey;
			if(!e)e = window.event;
			else if(e.which) // Netscape/Firefox/Opera
				jsKey = e.which;
			if (jsKey==13) {
				if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
				(prevKey == 108 || prevKey == 76) ){ //CL<enter>
					$('#selectlist :selected').val('');
				}
				$('#selectform').submit();
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
		}
		</script> 
		<?php
	} // END head() FUNCTION

	function preprocess(){
		global $IS4C_LOCAL,$IS4C_PATH;

		/* form submitted */
		if (isset($_REQUEST['selectlist'])){
			if (!empty($_REQUEST['selectlist'])){
				$tmp = explode("::",$_REQUEST['selectlist']);
				$this->doResume($tmp[0],$tmp[1],$tmp[2]);
			}
			header("Location: {$IS4C_PATH}gui-modules/pos2.php");
			return False;
		}

		$query_local = "select register_no, emp_no, trans_no, sum(total) as total from suspendedtoday "
			."group by register_no, emp_no, trans_no";

		$db_a = tDataConnect();
		$result = "";
		if ($IS4C_LOCAL->get("standalone") == 1) $result = $db_a->query($query_local);
		else {
			$db_a->close();
			$db_a = mDataConnect();
			$result = $db_a->query($query_local);
		}

		$num_rows = $db_a->num_rows($result);
		
		// original code seems to do this either way
		$IS4C_LOCAL->set("scan","noScan");

		/* if there are suspended transactions available, 
		 * store the result and row count as class variables
		 * so they can be retrieved in body_content()
		 *
		 * otherwise notify that there are no suspended
		 * transactions
		 */
		if ($num_rows > 0){
			$this->temp_result = $result;
			$this->temp_num_rows = $num_rows;
			$this->temp_db = $db_a;
			return True;
		}
		else {
			$db_a->close();
			$IS4C_LOCAL->set("boxMsg","no suspended transaction");
			header("Location: {$IS4C_PATH}gui-modules/pos2.php");	
			return False;
		}
		return True;
	} // END preprocess() FUNCTION

	function body_content(){
		global $IS4C_LOCAL;
		$num_rows = $this->temp_num_rows;
		$result = $this->temp_result;
		$db = $this->temp_db;

		echo "<div class=\"baseHeight\">"
			."<div class=\"listbox\">"
			."<form id=\"selectform\" method=\"post\" action=\"{$_SERVER['PHP_SELF']}\">\n"
			."<select name=\"selectlist\" size=\"10\" onblur=\"\$('#selectlist').focus();\"
				id=\"selectlist\">";

		$selected = "selected";
		for ($i = 0; $i < $num_rows; $i++) {
			$row = $db->fetch_array($result);
			echo "<option value='".$row["register_no"]."::".$row["emp_no"]."::".$row["trans_no"]."' ".$selected
				."> lane ".substr(100 + $row["register_no"], -2)." Cashier ".substr(100 + $row["emp_no"], -2)
				." #".$row["trans_no"]." -- $".$row["total"]."\n";
			$selected = "";
		}

		echo "</select>\n</form>\n</div>\n"
			."<div class=\"listboxText centerOffset\">"
			."use arrow keys to navigate<br />[clear] to cancel</div>\n"
			."<div class=\"clear\"></div>";
		echo "</div>";
		$IS4C_LOCAL->set("scan","noScan");
		$this->add_onload_command("\$('#selectlist').focus();");
		$this->add_onload_command("\$('#selectlist').keypress(processkeypress);");
	} // END body_content() FUNCTION

	function doResume($reg,$emp,$trans){
		global $IS4C_LOCAL;

		$query_del = "delete from suspended where register_no = ".$reg." and emp_no = "
			.$emp." and trans_no = ".$trans;

		$db_a = tDataConnect();
		$query_a = "select trans_id from localtemptrans";
		$result_a = $db_a->query($query_a);
		$num_rows_a = $db_a->num_rows($result_a);

		// use SQLManager's transfer method when not in stand alone mode
		// to eliminate the cross server query - andy 8/31/07
		if ($num_rows_a == 0) {
			if ($IS4C_LOCAL->get("standalone") == 0){
				$db_a->add_connection($IS4C_LOCAL->get("mServer"),$IS4C_LOCAL->get("mDBMS"),
					$IS4C_LOCAL->get("mDatabase"),$IS4C_LOCAL->get("mUser"),$IS4C_LOCAL->get("mPass"));
				$cols = getMatchingColumns($db_a,"localtemptrans","suspendedtoday");
				$remoteQ = "select {$cols} from suspendedtoday where register_no = $reg "
					." and emp_no = ".$emp." and trans_no = ".$trans." order by trans_id";
				$success = $db_a->transfer($IS4C_LOCAL->get("mDatabase"),$remoteQ,
					$IS4C_LOCAL->get("tDatabase"),"insert into localtemptrans ({$cols})");
				if ($success)
					$db_a->query($query_del,$IS4C_LOCAL->get("mDatabase"));
				$db_a->close($IS4C_LOCAL->get("mDatabase"));
			}
			else {	
				$localQ = "select * from suspendedtoday where register_no = $reg "
					." and emp_no = ".$emp." and trans_no = ".$trans." order by trans_id";
				$success = $db_a->query("insert into localtemptrans ".$localQ);
				if ($success)
					$db_a->query($query_del);
			}
		}

		$query_update = "update localtemptrans set register_no = ".$IS4C_LOCAL->get("laneno").", emp_no = ".$IS4C_LOCAL->get("CashierNo")
			.", trans_no = ".$IS4C_LOCAL->get("transno");

		$db_a->query($query_update);
		getsubtotals();
		$IS4C_LOCAL->set("unlock",1);
		if ($IS4C_LOCAL->get("chargeTotal") != 0) 
			$IS4C_LOCAL->set("chargetender",1);
		$IS4C_LOCAL->set("msg",0);
	}
}

new suspendedlist();

?>
