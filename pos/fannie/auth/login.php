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

/*
this file contains user authentication-related functions
all functions return true on success, false on failure
unless otherwise noted
*/

require_once('groups.php');

/*
a user is logged in using cookies
when a user logs in, a cookie name 'session_data' is created containing
two pieces of data: the user's name and a session_id which is
a 50 character random string of digits and capital letters
to access this data, unserialize the cookie's value and use
keys 'name' and 'session_id' to access the array
because this function sets a cookie, nothing before this function
call can produce output
*/
function login($name,$password){
  if (!isAlphanumeric($name) or !isAlphanumeric($password)){
    return false;
  }

  $sql = dbconnect();
  $gatherQ = "select password,salt from users where name='$name'";
  $gatherR = $sql->query($gatherQ);
  if ($sql->num_rows($gatherR) == 0){
    return false;
  }
  
  $gatherRow = $sql->fetch_array($gatherR);
  $crypt_pass = $gatherRow[0];
  $salt = $gatherRow[1];
  if (crypt($password,$salt) != $crypt_pass){
    return false;
  }

  doLogin($name);

  return true;
}

/* 
	Revised login for use with UNIX system
	
	shadowread searches the shadow password file
	and returns the user's password hash
*/

function shadow_login($name,$passwd){
	if (!isAlphanumeric($name))
		return false;

	$output = array();
	$return_value = -1;
	exec("../shadowread/shadowread \"$name\"",$output,$return_value);
	if ($return_value != 0)
		return false;

	$pwhash = $output[0];
	if (crypt($passwd,$pwhash) == $pwhash){
		syncUserShadow($name);
		doLogin($name);
		return true;
	}	
	return false;
}

/* login using an ldap server 
 * 
 * Constants need to be defined:
 * $LDAP_HOST => hostname or url of ldap server
 * $LDAP_PORT => ldap port on server
 * $LDAP_BASE_DN => DN to search for users
 * $LDAP_SEARCH_FIELD => entry containing the username
 *
 * Optional constants for importing LDAP users
 * into SQL automatically:
 * $LDAP_UID_FIELD => entry containing the user ID number
 * $LDAP_FULLNAME_FIELDS => entry or entries containing
			    the user's full name
 *
 * Tested against openldap 2.3.27
 */
function ldap_login($name,$passwd){
	$LDAP_HOST = "locke.wfco-op.store";
	$LDAP_PORT = 389;
	$LDAP_BASE_DN = "ou=People,dc=wfco-op,dc=store";
	$LDAP_SEARCH_FIELD = "uid";

	$LDAP_UID_FIELD = "uidnumber";
	$LDAP_FULLNAME_FIELDS = array("cn");

	$conn = ldap_connect($LDAP_HOST,$LDAP_PORT);
	if (!$conn) return false;

	$search_result = ldap_search($conn,$LDAP_BASE_DN,
				     $LDAP_SEARCH_FIELD."=".$name);
	if (!$search_result) return false;

	$ldap_info = ldap_get_entries($conn,$search_result);
	if (!$ldap_info) return false;

	$user_dn = $ldap_info[0]["dn"];
	$uid = $ldap_info[0][$LDAP_UID_FIELD][0];
	$fullname = "";
	foreach($LDAP_FULLNAME_FIELDS as $f)
		$fullname .= $ldap_info[0][$f][0]." ";
	$fullname = rtrim($fullname);

	if (ldap_bind($conn,$user_dn,$passwd)){
		syncUserLDAP($name,$uid,$fullname);	
		doLogin($name);
		return true;
	}	
	return false;
}

/*
sets a cookie.  nothing before this function call can have output
*/
function logout(){
  $name = checkLogin();
  if (!$name){
    return true;
  }
  setcookie('session_data','',time()+(60*40),'/');
  return true;
}

/*
logins are stored in a table called users
information in the table includes an alphanumeric
user name, an alphanumeric password (stored in crypted form),
the salt used to crypt the password (time of user creation),
and a unique user-id number between 0001 and 9999
a session id is also stored in this table, but that is created
when the user actually logs in
*/
function createLogin($name,$password){
  if (!isAlphanumeric($name) or !isAlphanumeric($password)){
    //echo 'failed alphanumeric';
    return false;
  }

  if (init_check())
    table_check();

  if (!validateUser('admin')){
    return false;
  }
  
  $sql = dbconnect();
  $checkQ = "select * from users where name='$name'";
  $checkR = $sql->query($checkQ);
  if ($sql->num_rows($checkR) != 0){
    return false;
  }
  
  $salt = time();
  $crypt_pass = crypt($password,$salt);
  
  // generate unique user-id between 0001 and 9999
  // implicit assumption:  there are less than 10,000
  // users currently in the database
  $uid = '';
  srand($salt);
  while ($uid == ''){
    $newid = (rand() % 9998) + 1;
    $newid = str_pad($newid,4,'0',STR_PAD_LEFT);
    $verifyQ = "select * from users where uid='$newid'";
    $verifyR = $sql->query($verifyQ);
    if ($sql->num_rows($verifyR) == 0){
      $uid = $newid;
    }
  }

  $addQ = "insert into users (name,uid,password,salt) values ('$name','$uid','$crypt_pass','$salt')";
  $addR = $sql->query($addQ);

  return true;
}

function deleteLogin($name){
  if (!isAlphanumeric($name)){
    return false;
  }
  
  if (!validateUser('admin')){
    return false;
  }

  $sql=dbconnect();
  $uid = getUID($name);
  $delQ = "delete from userPrivs where uid=$uid";
  $delR = $sql->query($delQ);

  $deleteQ = "delete from users where name='$name'";
  $deleteR = $sql->query($deleteQ);

  $groupQ = "DELETE FROM userGroups WHERE name='$name'";
  $groupR = $sql->query($groupQ);

  return true;
}

/* 
this function returns the name of the logged in
user on success, false on failure
*/
function checkLogin(){
  if (!auth_enabled()) return 'null';

  if (init_check())
    return 'init';

  if (!isset($_COOKIE['session_data'])){
    return false;
  }

  $cookie_data = base64_decode($_COOKIE['session_data']);
  $session_data = unserialize($cookie_data);

  $name = $session_data['name'];
  $session_id = $session_data['session_id'];

  if (!isAlphanumeric($name) or !isAlphanumeric($session_id)){
    return false;
  }

  $sql = dbconnect();
  $checkQ = "select * from users where name='$name' and session_id='$session_id'";
  $checkR = $sql->query($checkQ);

  if ($sql->num_rows($checkR) == 0){
    return false;
  }

  return $name;
}

function showUsers(){
  if (!validateUser('admin')){
    return false;
  }
  echo "Displaying current users";
  echo "<table cellspacing=2 cellpadding=2 border=1>";
  echo "<tr><th>Name</th><th>User ID</th></tr>";
  $sql = dbconnect();
  $usersQ = "select name,uid from users order by name";
  $usersR = $sql->query($usersQ);
  while ($row = $sql->fetch_array($usersR)){
    echo "<tr>";
    echo "<td>$row[0]</td>";
    echo "<td>$row[1]</td>";
    echo "</tr>";
  }
  echo "</table>";
}

/* 
this function uses login to verify the user's presented
name and password (thus creating a new session) rather
than using checkLogin to verify the correct user is
logged in.  This is nonstandard usage.  Normally checkLogin
should be used to determine who (if anyone) is logged in
(this way users don't have to constantly provide passwords)
However, since the current password is provided, checking
it is slightly more secure than checking a cookie
*/
function changePassword($name,$oldpassword,$newpassword){
  $sql = dbconnect();
  if (!login($name,$oldpassword)){
    return false;
  }

  // the login functions checks its parameters for being
  // alphanumeric, so only the newpassword needs to be checked
  if (!isAlphanumeric($newpassword)){
    return false;
  }

  $salt = time();
  $crypt_pass = crypt($newpassword,$salt);

  $updateQ = "update users set password='$crypt_pass',salt='$salt' where name='$name'";
  $updateR = $sql->query($updateQ);
  
  return true;
}

function changeAnyPassword($name,$newpassword){
  if (!validateUser('admin')){
    return false;
  }

  if (!isAlphanumeric($newpassword) || !isAlphaNumeric($name)){
    return false;
  }

  $salt = time();
  $crypt_pass = crypt($newpassword,$salt);

  $updateQ = "update users set password='$crypt_pass',salt='$salt' where name='$name'";
  $updateR = $sql->query($updateQ);

  return true;
}

/*
this function is here to reduce user validation checks to
a single function call.  since this task happens ALL the time,
it just makes code cleaner.  It returns the current user on
success just because that information might be useful  
*/
function validateUser($auth,$sub='all'){
     if (!auth_enabled()) return 'null';

     if (init_check())
	return 'init';

     $current_user = checkLogin();
     if (!$current_user){
       echo "You must be logged in to use this function";
       return false;
     }

     $groupPriv = checkGroupAuth($current_user,$auth,$sub);
     if ($groupPriv){
       return $current_user;
     }

     $priv = checkAuth($current_user,$auth,$sub);
     if (!$priv){
       echo "Your account doesn't have permission to use this function";
       return false;
     }
     return $current_user;
}

function validateUserQuiet($auth,$sub='all'){
     if (!auth_enabled()) return 'null';

     if (init_check())
	return 'init';

     $current_user = checkLogin();
     if (!$current_user){
       return false;
     }

     $groupPriv = checkGroupAuth($current_user,$auth,$sub);
     if ($groupPriv){
       return $current_user;
     }

     $priv = checkAuth($current_user,$auth,$sub);
     if (!$priv){
       return false;
     }
     return $current_user;
}

// re-sets expires timer on the cookie if the
// user is currently logged in
// must be called prior to any output
function refreshSession(){
  if (!isset($_COOKIE['session_data']))
    return false;
  setcookie('session_data',$_COOKIE['session_data'],time()+(60*40),'/');
  return true;
}

function pose($username){
	if (!isset($_COOKIE['session_data']))
		return false;
	if (!isAlphanumeric($username))
		return false;

	$cookie_data = base64_decode($_COOKIE['session_data']);
	$session_data = unserialize($cookie_data);

	$session_id = $session_data['session_id'];

	$sql = dbconnect();
	$sessionQ = "update users set session_id = '$session_id' where name='$username'";
	$sessionR = $sql->query($sessionQ);

	$session_data = array("name"=>$username,"session_id"=>$session_id);
	$cookie_data = serialize($session_data);

	setcookie('session_data',base64_encode($cookie_data),time()+(60*40),'/');

	return true;
}

?>
