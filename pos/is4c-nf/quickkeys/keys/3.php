<?php

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

include_once($IS4C_PATH."quickkeys/quickkey.php");

$my_keys = array(
	new quickkey("All\nEggs","QK7"),
	new quickkey("All\nMilk","QK8"),
	new quickkey("Deli\nCoffee","21190040099"),
	new quickkey("Baked\nGood","1017"),
	new quickkey("Single\nCookie","6000"),
	new quickkey("Dozen\nCookies","6001"),
	new quickkey("Blue Sky\nOrganic","9930"),
	new quickkey("Blue Sky\nSpritzer","9931"),
	new quickkey("Blue Sky\nRegular","9932"),
	new quickkey("Bottle","8366"),
	new quickkey("Coffee\nBag","5006"),
	new quickkey("Bag\nRefund","5005"),
	new quickkey("Growler\nReturn","1092"),
	new quickkey("Cards","8661"),
	new quickkey("Totes","1003")
);

?>
