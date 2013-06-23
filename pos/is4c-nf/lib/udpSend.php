<?php

function udpSend($msg,$port=9450){
	if (!function_exists("socket_create")) return;
	$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	$res = socket_sendto($sock, $msg, strlen($msg), 0, '127.0.0.1',$port);
	socket_close($sock);
}

?>
