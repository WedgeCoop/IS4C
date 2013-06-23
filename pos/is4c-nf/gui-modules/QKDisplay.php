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

ini_set('display_errors','1');

if (!class_exists("NoInputPage")) include_once($IS4C_PATH."gui-class-lib/NoInputPage.php");
if (!function_exists("tDataConnect")) include($IS4C_PATH."lib/connect.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class QKDisplay extends NoInputPage {

	var $offset;

	function head_content(){
		global $IS4C_PATH;
		?>
		<script type="text/javascript" >
		var prevKey = -1;
		var prevPrevKey = -1;
		var selectedId = 0;
		function keyCheck(e) {
			var jsKey;
			if(!e)e = window.event;
			if (e.keyCode) // IE
				jsKey = e.keyCode;
			else if(e.which) // Netscape/Firefox/Opera
				jsKey = e.which;
			// Options:
			// 1: Clear - go back to pos2 w/o selecting anything
			// 2: Select button corresponding to 1-9
			// 3: Page Up - go to previous page of buttons
			// 4: Page Down - go to next page of buttons
			// (Paging wraps)
			if ( (jsKey==108 || jsKey == 76) && 
			(prevKey == 99 || prevKey == 67) ){
				document.getElementById('doClear').value='1';
			}
			else if (jsKey >= 49 && jsKey <= 57){
				setSelected(jsKey-48);
			}
			else if (jsKey >= 97 && jsKey <= 105){
				setSelected(jsKey-96);
			}
			else if (jsKey == 33 || jsKey == 38){
				location = 
					'<?php echo $IS4C_PATH; ?>gui-modules/QKDisplay.php?offset=<?php echo ($this->offset - 1)?>';
			}
			else if (jsKey == 34 || jsKey == 40){
				location = 
					'<?php echo $IS4C_PATH; ?>gui-modules/QKDisplay.php?offset=<?php echo ($this->offset + 1)?>';
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
		}

		document.onkeyup = keyCheck;

		function setSelected(num){
			var row = Math.floor((num-1) / 3);
			var id = 0;
			if (row == 2) id = num - 7;
			else if (row == 1) id = num - 1;
			else if (row == 0) id = num + 5;
			if ($('#qkDiv'+id)){
				$('#qkDiv'+selectedId).css('border','0');
				$('#qkDiv'+id).css('border','solid 3px #004080');
				$('#qkButton'+id).focus();
				selectedId = id;
			}
		}
		</script> 
		<?php
	} // END head() FUNCTION

	function preprocess(){
		global $IS4C_LOCAL,$IS4C_PATH;

		$this->offset = isset($_REQUEST['offset'])?$_REQUEST['offset']:0;

		if (count($_POST) > 0){
			if ($_REQUEST["clear"] == 0){
				// submit process changes line break
				// depending on platform
				// apostrophes pick up slashes
				$choice = str_replace("\r","",$_REQUEST["quickkey_submit"]);
				$choice = stripslashes($choice);

				$value = $_REQUEST[md5($choice)];

				$output = $IS4C_LOCAL->get("qkInput").$value;
				$IS4C_LOCAL->set("msgrepeat",1);
				$IS4C_LOCAL->set("strRemembered",$output);
				$IS4C_LOCAL->set("currentid",$IS4C_LOCAL->get("qkCurrentId"));
			}
			if (substr(strtoupper($output),0,2) == "QK"){
				$IS4C_LOCAL->set("qkNumber",substr($output,2));
				return True;
			}
			else {
				header("Location: {$IS4C_PATH}gui-modules/pos2.php");
			}
			return False;
		}
		return True;
	} // END preprocess() FUNCTION

	function body_content(){
		global $IS4C_LOCAL,$IS4C_PATH;

		$this->add_onload_command("setSelected(7);");

		echo "<div class=\"baseHeight\" style=\"border: solid 1px black;\">";
		echo "<form action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";

		include($IS4C_PATH."quickkeys/keys/"
			.$IS4C_LOCAL->get("qkNumber").".php");

		$num_pages = ceil(count($my_keys)/9.0);
		$page = $this->offset % $num_pages;
		if ($page < 0) $page = $num_pages + $page;

		$count = 0;
		for($i=$page*9; $i < count($my_keys); $i++){
			$key = $my_keys[$i];
			if ($count % 3 == 0){
				if ($count != 0){
					if ($num_pages > 1 && $count == 3){
						echo "<div class=\"qkArrowBox\">";
						echo "<input type=submit value=Up class=qkArrow 
							onclick=\"location='{$IS4C_PATH}gui-modules/QKDisplay.php?offset=".($page-1)."'; return false;\" />";
						echo "</div>";
					}
					echo "</div>";
				}
				echo "<div class=\"qkRow\">";
			}
			echo "<div class=\"qkBox\"><div id=\"qkDiv$count\">";
			echo $key->display("qkButton$count");
			echo "</div></div>";
			$count++;
			if ($count > 8) break;
		}
		if ($num_pages > 1){
			echo "<div class=\"qkArrowBox\">";
			echo "<input type=submit value=Down class=qkArrow 
				onclick=\"location='{$IS4C_PATH}gui-modules/QKDisplay.php?offset=".($page+1)."'; return false;\" />";
			echo "</div>";

		}
		echo "</div>";
		echo "<input type=\"hidden\" value=\"0\" name=\"clear\" id=\"doClear\" />";	
		echo "</form>";
		echo "</div>";
		$IS4C_LOCAL->set("scan","noScan");
	} // END body_content() FUNCTION

}

new QKDisplay();

?>
