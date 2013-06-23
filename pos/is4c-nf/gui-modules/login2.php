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

if (!class_exists("BasicPage")) include($IS4C_PATH."gui-class-lib/BasicPage.php");
if (!function_exists("authenticate")) include($IS4C_PATH."lib/authenticate.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class login2 extends BasicPage {

	var $box_color;
	var $msg;

	function preprocess(){
		global $IS4C_PATH;
		$this->box_color = '#004080';
		$this->msg = 'please enter your password';

		if (isset($_REQUEST['reginput'])){
			if (authenticate($_REQUEST['reginput'])){
				header("Location: {$IS4C_PATH}gui-modules/pos2.php");
				return False;
			}
			else {
				$this->box_color = '#800000';
				$this->msg = 'password invalid, please re-enter';
			}
		}

		return True;
	}

	function head_content(){
		?>
		<script type="text/javascript">
		function closeFrames() {
			window.top.close();
		}
		</script>
		<?php
	}

	function body_content(){
		global $IS4C_LOCAL, $IS4C_PATH;
		$this->add_onload_command("\$('#reginput').focus();\n
					   \$('#scalebox').css('display','none');\n
					   \$('body').css('background-image','none');\n");
		?>
		<div id="loginTopBar">
			<div class="name">I S 4 C</div>
			<div class="version">P H P &nbsp; D E V E L O P M E N T
			&nbsp; V E R S I O N &nbsp; 2 .0 .0 (beta)</div>
			<div class="welcome">W E L C O M E</div>
		</div>
		<div id="loginCenter">
		<div class="box" style="background:<?php echo $this->box_color; ?>;" >
				<b>log in</b>
				<form name="form" method="post" autocomplete="off" 
					action="<?php echo $_SERVER['PHP_SELF']; ?>">
				<input type="password" name="reginput" size="20" tabindex="0" 
					onblur="$('#reginput').focus();" id="reginput" >
				<p />
				<?php echo $this->msg ?>
				</form>
			</div>	
		</div>
		<div id="loginExit">
			EXIT
			<?php
			if ($IS4C_LOCAL->get("browserOnly") == 1) {
				echo "<a href=\"\" onclick=\"window.top.close();\" ";
			}
			else {
				echo "<a href='/bye.html' onclick=\"var cw=window.open('','Customer_Display'); cw.close()\" ";
			}
			echo "onmouseover=\"document.exit.src='{$IS4C_PATH}graphics/switchred2.gif';\" ";
			echo "onmouseout=\"document.exit.src='{$IS4C_PATH}graphics/switchblue2.gif';\">";
			?>
			<img name="exit" border="0" src="<?php echo $IS4C_PATH; ?>graphics/switchblue2.gif" /></a>
	
		</div>
		<form name="hidden">
		<input type="hidden" name="scan" value="noScan">
		</form>
		<?php
	} // END true_body() FUNCTION

}

new login2();

?>
