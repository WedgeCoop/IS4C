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
include('../config.php');

$page_title = "Fannie :: Member Tools";
$header = "Member Tools";

include($FANNIE_ROOT.'src/header.html');
?>
<ul>
<li><a href="search.php">View/Edit Members</a></li>
<li><a href="types.php">Manage Member Types</a></li>
<li><a href="new.php">Create New Members</a></li>
<li><a href="numbers/index.php">Print Member Stickers</a></li>
</ul>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>
