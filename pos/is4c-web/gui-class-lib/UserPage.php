<?php
/*******************************************************************************

    Copyright 2007,2010 Whole Foods Co-op

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

/* UserPage
 *
 * Adds a login check to BasicPage. Many gui-modules
 * Require a logged in user.
 */

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

if (!class_exists('BasicPage')) include($IS4C_PATH.'gui-class-lib/BasicPage.php');
if (!function_exists('checkLogin')) include($IS4C_PATH.'auth/login.php');

class UserPage extends BasicPage {

	function UserPage(){
		global $IS4C_PATH;
		if (!checkLogin()){
			header("Location: {$IS4C_PATH}gui-modules/loginPage.php");
		}
		else if ($this->preprocess()){
			ob_start();
			$this->print_page();
			while (ob_get_level() > 0)
				ob_end_flush();
		}
	}
}

?>
