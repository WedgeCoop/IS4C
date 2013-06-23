<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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

class Equity extends MemberModule {

	function ShowEditForm($memNum){
		global $FANNIE_URL;

		$dbc = $this->db();
		
		$infoQ = sprintf("SELECT payments
				FROM newBalanceStockToday_test
				WHERE memnum=%d",$memNum);
		$infoR = $dbc->query($infoQ);
		$equity = 0;
		if ($dbc->num_rows($infoR) > 0)
			$equity = array_pop($dbc->fetch_row($infoR));

		$ret = "<fieldset><legend>Equity</legend>";
		$ret .= "<table class=\"MemFormTable\" 
			border=\"0\">";

		$ret .= "<tr><th>Stock Purhcased</th>";
		$ret .= sprintf('<td>%.2f</td>',$equity);

		$ret .= "<td><a href=\"{$FANNIE_URL}reports/Equity/index.php?memNum=$memNum\">History</a></td></tr>";
		$ret .= "<tr><td><a href=\"{$FANNIE_URL}mem/corrections.php?type=equity_transfer&memIN=$memNum\">Transfer Equity</a></td>";
		$ret .= "<td><a href=\"{$FANNIE_URL}mem/corrections.php?type=equity_ar_swap&memIN=$memNum\">Convert Equity</a></td></tr>";


		$ret .= "</table></fieldset>";
		return $ret;
	}
}

?>
