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

if (!class_exists("BasicPage")) include_once($IS4C_PATH."gui-class-lib/BasicPage.php");
if(!function_exists("boxMsg")) include($IS4C_PATH."lib/drawscreen.php");
if (!function_exists("errorBeep")) include($IS4C_PATH."lib/lib.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class boxMsg2 extends BasicPage {

	function head_content(){
		global $IS4C_PATH;
		?>
		<script type="text/javascript">
		function submitWrapper(){
			var str = $('#reginput').val();
			$.ajax({
				url: '<?php echo $IS4C_PATH; ?>ajax-callbacks/ajax-decision.php',
				type: 'get',
				data: 'input='+str,
				dataType: 'json',
				cache: false,
				success: function(data){
					if (data.endorse){
						$.ajax({
							url: '<?php echo $IS4C_PATH; ?>ajax-callbacks/ajax-endorse.php',
							type: 'get',
							cache: false,
							success: function(){}
						});
					}
					location = data.dest_page;
				}
			});
			return false;
		}
		</script>
		<?php
	}
	
	function body_content(){
		global $IS4C_LOCAL;
		$this->input_header("onsubmit=\"return submitWrapper();\"");
		?>
		<div class="baseHeight">

		<?php
		echo boxMsg($IS4C_LOCAL->get("boxMsg"));
		echo "</div>";
		echo "<div id=\"footer\">";
		echo printfooter();
		echo "</div>";
		$IS4C_LOCAL->set("boxMsg",'');
		$IS4C_LOCAL->set("msgrepeat",2);
		if ($IS4C_LOCAL->get("warned") == 0)
		errorBeep();
	} // END body_content() FUNCTION
}

new boxMsg2();

?>
