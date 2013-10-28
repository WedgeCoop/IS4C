<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class SavedOrCouldHave extends FooterBox {

	public $display_css = "font-weight:bold;font-size:150%;";
	public $display_css_class = 'coloredText';

	function header_content(){
		global $CORE_LOCAL;
		if ($CORE_LOCAL->get("isMember") == 1)
			return _("You Saved");
		else
			return _("Could Have Saved");
	}

	function display_content(){
		global $CORE_LOCAL;
		$saleTTL = (is_numeric($CORE_LOCAL->get("discounttotal"))) ? number_format($CORE_LOCAL->get("discounttotal"),2) : "0.00";
		$memSaleTTL = is_numeric($CORE_LOCAL->get("memSpecial")) ? number_format($CORE_LOCAL->get("memSpecial"),2) : "0.00";

		if ($CORE_LOCAL->get("isMember") == 1){
			return number_format($CORE_LOCAL->get("transDiscount") +
				$saleTTL + $memSaleTTL, 2);	
		}
		else
			return $memSaleTTL;
	}
}

?>
