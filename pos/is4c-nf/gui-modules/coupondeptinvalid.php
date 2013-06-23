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
if (!class_exists("MainFramePage")) include_once($_SESSION["INCLUDE_PATH"]."/gui-class-lib/MainFramePage.php");
if (!isset($IS4C_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

class coupondeptinvalid extends MainFramePage {
	function body_tag() {
		print "<body onload=\"document.form.dept.focus()\">";
	}

	function body_content(){
		global $IS4C_LOCAL;
		?>
		<div class="baseHeight">
		<div class="centeredDisplay errorColored">
		<span class="larger">department invalid</span>
		<form name='form' method='post' autocomplete='off' action='../coupondec.php'>
		<input Type='text' name='dept' size='6' tabindex='0' onBlur='document.form.dept.focus();'>
		<p />
		department key or [clear] to cancel
		<p />
		</div>
		</div>
		<?php
		$IS4C_LOCAL->set("beep","errorBeep");
		$IS4C_LOCAL->set("scan","noScan");
	} // END body_content() FUNCTION
}

new coupondeptinvalid();

?>
