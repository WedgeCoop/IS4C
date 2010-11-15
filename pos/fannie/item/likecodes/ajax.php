<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

if (isset($_REQUEST['action'])){
	switch($_REQUEST['action']){
	case 'fetch':
		$res = $dbc->query("SELECT u.upc,p.description FROM
				upcLike AS u INNER JOIN products AS p
				ON u.upc=p.upc WHERE u.likeCode={$_REQUEST['lc']}
				ORDER BY p.description");
		$ret = "";
		while($row = $dbc->fetch_row($res)){
			$ret .= "<a style=\"font-size:90%;\" href={$FANNIE_URL}item/itemMaint.php?upc=$row[0]>";
			$ret .= $row[0]."</a> ".$row[1]."<br />";
		}
		echo $ret;
		break;
	}
}

?>
