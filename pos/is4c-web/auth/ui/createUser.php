<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

require('../login.php');
$path = guesspath();
$page_title = 'IS4C : Auth : Add User';
$header = 'IS4C : Auth : Add User';

include($path."src/header.html");

if (!validateUser('admin')){
  return;
}

if (isset($_POST['name'])){
  $name = $_POST['name'];
  $pass1 = $_POST['pass1'];
  $pass2 = $_POST['pass2'];
  if ($pass1 != $pass2){
    echo "Passwords don't match.<p />";
    echo "<a href=menu.php>Main menu</a>  |  <a href=createUser.php>Try again</a>?";
    return;
  }
  $success = createLogin($name,$pass1);
  if (!$success){
    echo "Unable to create user.  Another user probably already has username '$name'<p />";
    echo "<a href=menu.php>Main menu</a>  |  <a href=createUser.php>Try again</a>?";
    return;
  }
  echo "User '$name' created succesfully<p />";
  echo "<a href=menu.php>Main menu</a>";
}
else {
  echo "<form action=createUser.php method=post>";
  echo "<table cellspacing=4 cellpadding=4>";
  echo "<tr><td>Username:</td><td><input type=text name=name></td></tr>";
  echo "<tr><td>Password:</td><td><input type=password name=pass1></td></tr>";
  echo "<tr><td>Password, again:</td><td><input type=password name=pass2></td></tr>";
  echo "<tr><td><input type=submit value=Create></td><td><input type=reset value=Reset></td></tr>";
  echo "</table</form>";
}

include($path."src/footer.html");
?>
