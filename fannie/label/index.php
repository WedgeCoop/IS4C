<?php
	require('../config.php');

	require_once($FANNIE_ROOT.'src/htmlparts.php');
	
	$html='<!DOCTYPE HTML>
<html>
	<head>';
	
	$html.=head();
	
	$html.='
		<title>IT CORE - Labels</title>
		</head>
	<body>';
	
	$html.=body();
	
	$html.='
		<div id="page_panel">
			<img src="Screenshot.png" alt="Screenshot of Wedge Label Maker"/>
		</div>';
	
	$html.=foot();
	
	$html.='
	</body>
</html>';
	
	print_r($html);
?>
