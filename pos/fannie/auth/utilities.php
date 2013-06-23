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
utility functions
*/

/*
connect to the database
having this as a separate function makes changing
the database easier
*/
function dbconnect(){
	global $FANNIE_ROOT;
	$path = guesspath();
	if (!class_exists("SQLManager")){
		include($path."config.php");
		include($path."src/SQLManager.php");
	}
	include($path."src/mysql_connect.php");
	return $dbc;
}

function guesspath(){
	$path = "";
	$found = False;
	$uri = $_SERVER["REQUEST_URI"];
	$tmp = explode("?",$uri);
	if (count($tmp) > 1) $uri = $tmp[0];
	foreach(explode("/",$uri) as $x){
		if (strpos($x,".php") === False
			&& strlen($x) != 0){
			$path .= "../";
		}
		if (!$found && stripos($x,"fannie") !== False){
			$found = True;
			$path = "";
		}
		
	}
	return $path;
}

function init_check(){
	$path = guesspath();
	return file_exists($path."auth/init.php");
}

/*
checking whether a string is alphanumeric is
a good idea to prevent sql injection
*/
function isAlphanumeric($str){
  if (preg_match("/^\\w*$/",$str) == 0){
    return false;
  }
  return true;
}

function getUID($name){
  if (!auth_enabled()) return '0000';

  $sql = dbconnect();
  $fetchQ = "select uid from users where name='$name'";
  $fetchR = $sql->query($fetchQ);
  if ($sql->num_rows($fetchR) == 0){
    return false;
  }
  $uid = $sql->fetch_array($fetchR);
  $uid = $uid[0];
  return $uid;
}

function getGID($group){
  if (!isAlphaNumeric($group))
    return false;
  $sql = dbconnect();

  $gidQ = "select top 1 gid from userGroups where name='$group'";
  $gidR = $sql->query($gidQ);

  if ($sql->num_rows($gidR) == 0)
    return false;

  $row = $sql->fetch_array($gidR);
  return $row[0];
}

function genSessID(){
  $session_id = '';
  srand(time());
  for ($i = 0; $i < 50; $i++){
    $digit = (rand() % 35) + 48;
    if ($digit > 57){
      $digit+=7;
    }
    $session_id .= chr($digit);
  }
  return $session_id;
}

function doLogin($name){
	$session_id = genSessID();	

	$sql = dbconnect();
	$sessionQ = "update users set session_id = '$session_id' where name='$name'";
	$sessionR = $sql->query($sessionQ);

	$session_data = array("name"=>$name,"session_id"=>$session_id);
	$cookie_data = serialize($session_data);

	setcookie('session_data',base64_encode($cookie_data),time()+(60*40),'/');
}

function syncUserShadow($name){
	$localdata = posix_getpwnam($name);

	$currentUID = getUID($name);
	$posixUID = str_pad($localdata['uid'],4,"0",STR_PAD_LEFT);
	$realname = str_replace("'","''",$localdata['gecos']);
	$sql = dbconnect();	

	if (!$currentUID){
		$addQ = sprintf("INSERT INTO Users 
			(name,password,salt,uid,session_id,real_name)
			VALUES ('%s','','','%s','','%s')",
			$name,$posixUID,$realname);
		$sql->query($addQ);
	}
	else {
		$upQ1 = sprintf("UPDATE Users SET real_name='%s'
				WHERE name='%s'",
				$realname,$name);
		$sql->query($upQ1);
	}
}

function syncUserLDAP($name,$uid,$fullname){
	$currentUID = getUID($name);
	$sql = dbconnect();

	if (!$currentUID){
		$addQ = sprintf("INSERT INTO Users 
			(name,password,salt,uid,session_id,real_name)
			VALUES ('%s','','','%s','','%s')",
			$name,$uid,$fullname);
		$sql->query($addQ);
	}
	else {
		$upQ1 = sprintf("UPDATE Users SET real_name='%s'
				WHERE name='%s'",
				$fullname,$name);
		$sql->query($upQ1);
	}
}

function auth_enabled(){
	$path = guesspath();
	include($path."config.php");
	return $FANNIE_AUTH_ENABLED;
}

function table_check(){
	$sql = dbconnect();
	if (!$sql->table_exists('Users')){
		$sql->query("CREATE TABLE Users (
			name varchar(50),
			password varchar(50),
			salt varchar(10),
			uid varchar(4),
			session_id varchar(50),
			real_name varchar(75),
			PRIMARY KEY (name)
			)");
	}
	if (!$sql->table_exists('userPrivs')){
		$sql->query("CREATE TABLE userPrivs (
			uid varchar(4),
			auth_class varchar(50),
			sub_start varchar(50),
			sub_end varchar(50)
			)");
	}
	if (!$sql->table_exists('userGroups')){
		$sql->query("CREATE TABLE userGroups (
			gid int,
			name varchar(50),
			username varchar(50)
			)");
	}
	if (!$sql->table_exists('userGroupPrivs')){
		$sql->query("CREATE TABLE userGroupPrivs (
			gid int,
			auth varchar(50),
			sub_start varchar(50),
			sub_end varchar(50)
			)");
	}
}

?>
