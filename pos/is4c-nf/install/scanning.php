<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
include(realpath(dirname(__FILE__).'/../ini.php'));
include('InstallUtilities.php');
?>
<html>
<head>
<title>IT CORE Lane Installation: Scanning options</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">
<h2>IT CORE Lane Installation: Scanning Options</h2>

<div class="alert"><?php InstallUtilities::checkWritable('../ini.php', False, 'PHP'); ?></div>
<div class="alert"><?php InstallUtilities::checkWritable('../ini-local.php', True, 'PHP'); ?></div>

<form action=scanning.php method=post>
<table id="install" border=0 cellspacing=0 cellpadding=4>
<tr>
<td colspan="2">
    <hr />
    <b>Check Digits</b>
    <p>
    Most users omit check digits. If you have no particular preference, this
    option is more thoroughly tested. Mixing and matching is not recommended.
    It should work but uniformly omitting or including check digits for
    all barcodes will make some back end tasks easier.
    </p>
</td>
</tr>
<tr>
    <td style="width: 30%;">
        <b>UPCs</b>
    </td>
    <td>
    <select name="UPC_CHECK_DIGITS">
    <?php
    if (isset($_REQUEST['UPC_CHECK_DIGITS'])) $CORE_LOCAL->set('UpcIncludeCheckDigits', $_REQUEST['UPC_CHECK_DIGITS']);
    if ($CORE_LOCAL->get('UpcIncludeCheckDigits') === '') $CORE_LOCAL->set('UpcIncludeCheckDigits', 0);
    if ($CORE_LOCAL->get('UpcIncludeCheckDigits') == 0) {
        echo '<option value="0" selected>Omit Check Digits</option>';
        echo '<option value="1">Include Check Digits</option>';
    } else {
        echo '<option value="0">Omit Check Digits</option>';
        echo '<option value="1" selected>Include Check Digits</option>';
    }
    InstallUtilities::paramSave('UpcIncludeCheckDigits',$CORE_LOCAL->get('UpcIncludeCheckDigits'));
    ?>
    </select>
    </td>
</tr>
<tr>
    <td style="width: 30%;">
        <b>EANs</b>
    </td>
    <td>
    <select name="EAN_CHECK_DIGITS">
    <?php
    if (isset($_REQUEST['EAN_CHECK_DIGITS'])) $CORE_LOCAL->set('EanIncludeCheckDigits', $_REQUEST['EAN_CHECK_DIGITS']);
    if ($CORE_LOCAL->get('EanIncludeCheckDigits') === '') $CORE_LOCAL->set('EanIncludeCheckDigits', 0);
    if ($CORE_LOCAL->get('EanIncludeCheckDigits') == 0) {
        echo '<option value="0" selected>Omit Check Digits</option>';
        echo '<option value="1">Include Check Digits</option>';
    } else {
        echo '<option value="0">Omit Check Digits</option>';
        echo '<option value="1" selected>Include Check Digits</option>';
    }
    InstallUtilities::paramSave('EanIncludeCheckDigits',$CORE_LOCAL->get('EanIncludeCheckDigits'));
    ?>
    </select>
    </td>
</tr>
<tr>
    <td colspan="2"><hr /></td>
</tr>
<tr>
<td style="width:30%;">
<b>Special UPCs</b>:<br />
<p>Special handling modules for UPCs that aren't products (e.g., coupons)</p>
</td>
<td>
<select multiple size=10 name=SPECIAL_UPC_MODS[]>
<?php
if (isset($_REQUEST['SPECIAL_UPC_MODS'])) $CORE_LOCAL->set('SpecialUpcClasses',$_REQUEST['SPECIAL_UPC_MODS']);

$mods = AutoLoader::listModules('SpecialUPC');

foreach($mods as $m){
	$selected = "";
	foreach($CORE_LOCAL->get("SpecialUpcClasses") as $r){
		if ($r == $m){
			$selected = "selected";
			break;
		}
	}
	echo "<option $selected>$m</option>";
}

$saveStr = "array(";
foreach($CORE_LOCAL->get("SpecialUpcClasses") as $r){
	$saveStr .= "'".$r."',";
}
$saveStr = rtrim($saveStr,",").")";
InstallUtilities::paramSave('SpecialUpcClasses', $CORE_LOCAL->get('SpecialUpcClasses'));
?>
</select></td></tr>
<tr><td>
<b>House Coupon Prefix</b></td><td>
<?php
if(isset($_REQUEST['HCPREFIX'])) $CORE_LOCAL->set('houseCouponPrefix',$_REQUEST['HCPREFIX'],True);
else if ($CORE_LOCAL->get('houseCouponPrefix') === '') $CORE_LOCAL->set('houseCouponPrefix', '00499999', true);
printf("<input type=text name=HCPREFIX value=\"%s\" />",$CORE_LOCAL->get('houseCouponPrefix'));
InstallUtilities::paramSave('houseCouponPrefix',$CORE_LOCAL->get('houseCouponPrefix'));
?>
<span class='noteTxt'>Set the barcode prefix for houseCoupons.  Should be 8 digits starting with 004. Default is 00499999.</span>
</td></tr>
<tr><td style="width: 30%;">
</td><td>
</td></tr>
<tr><td>
<b>Coupons &amp; Sales Tax</b>:</td><td>
<?php
if (isset($_REQUEST['COUPONTAX'])) $CORE_LOCAL->set('CouponsAreTaxable',$_REQUEST['COUPONTAX']);
echo '<select name="COUPONTAX">';
if ($CORE_LOCAL->get('CouponsAreTaxable') === '0'){
	echo '<option value="1">Tax pre-coupon total</option>';
	echo '<option value="0" selected>Tax post-coupon total</option>';
}
else {
	echo '<option value="1" selected>Tax pre-coupon total</option>';
	echo '<option value="0">Tax post-coupon total</option>';
	$CORE_LOCAL->set('CouponsAreTaxable', 1);
}
echo '</select>';
InstallUtilities::paramSave('CouponsAreTaxable',$CORE_LOCAL->get('CouponsAreTaxable'));
?>
<span class='noteTxt'>Apply sales tax based on item price before any coupons, or
apply sales tax to item price inclusive of coupons.</span>
</td></tr>
<tr><td>
<b>Donation Department</b></td><td>
<?php
if(isset($_REQUEST['DONATIONDEPT'])) $CORE_LOCAL->set('roundUpDept',$_REQUEST['DONATIONDEPT'], true);
else if ($CORE_LOCAL->get('roundUpDept') === '') {
    // try to find a sane default automatically
    $CORE_LOCAL->set('roundUpDept', 701);
    $db = Database::pDataConnect();
    $lookup = $db->query("SELECT dept_no FROM departments WHERE dept_name LIKE '%DONAT%'");
    if ($lookup && $db->num_rows($lookup) > 0) {
        $row = $db->fetch_row($lookup);
        $CORE_LOCAL->set('roundUpDept', $row['dept_no']);
    }
}
printf("<input type=text name=DONATIONDEPT value=\"%s\" />",$CORE_LOCAL->get('roundUpDept'));
InstallUtilities::paramSave('roundUpDept',$CORE_LOCAL->get('roundUpDept'));
?>
<span class='noteTxt'>Set the department number for lines entered via the "round up" donation function.</span>
</td></tr><tr>
<td colspan="2">
<hr />
<p>Discount type modules control how sale discounts are calculated.</p></td></tr>
<tr><td>
<b>Default Discounts</b>:</td><td>
<?php
foreach (DiscountType::$MAP as $id => $name) {
    echo '[' . $id . '] => ' . $name . '<br />';
}
?>
</td></tr>
<tr><td>
<b>Custom Discount Mapping</b>:</td><td>
<?php
if (isset($_REQUEST['DT_MODS'])) {
    $new_val = array();
    foreach ($_REQUEST['DT_MODS'] as $r) {
        if ($r !== '' && !in_array($r, DiscountType::$MAP)) {
            $new_val[] = $r;
        }
    }
    $CORE_LOCAL->set('DiscountTypeClasses', $new_val);
}
if (!is_array($CORE_LOCAL->get('DiscountTypeClasses'))) {
	$CORE_LOCAL->set('DiscountTypeClasses', array(), true);
}
$discounts = AutoLoader::listModules('DiscountType');
$dt_conf = $CORE_LOCAL->get("DiscountTypeClasses");
$dt_conf[] = ''; // add blank slot for adding another discounttype
$i = 64;
foreach ($dt_conf as $entry) {
	echo '[' . $i . '] => ';
	echo "<select name=DT_MODS[]>";
    echo '<option value="">[None]</option>';
	foreach($discounts as $d) {
        if (in_array($d, DiscountType::$MAP)) {
            continue;
        }
		echo "<option";
		if ($entry == $d)
			echo " selected";
		echo ">$d</option>";
	}
	echo "</select><br />";
    $i++;
}
$save = array();
foreach($CORE_LOCAL->get("DiscountTypeClasses") as $r){
    if ($r !== '' && !in_array($r, DiscountType::$MAP)) {
        $save[] = $r;
    }
}
InstallUtilities::paramSave('DiscountTypeClasses',$save);
?></td></tr>

<tr><td colspan=2>
<hr />	<p>Price Methods dictate how item prices are calculated.
	There's some overlap with Discount Types, but <i>often</i>
	price methods deal with grouped items.</p></td></tr>
</td></tr>
<tr><td>
<b>Default Methods</b>:</td><td>
<?php
foreach (PriceMethod::$MAP as $id => $name) {
    echo '[' . $id . '] => ' . $name . '<br />';
}
?>
</td></tr>
<tr><td>
<b>Custom Method Mapping</b>:</td><td>
<?php
if (isset($_REQUEST['PM_MODS'])) {
    $new_val = array();
    foreach ($_REQUEST['PM_MODS'] as $r) {
        if ($r !== '' && !in_array($r, PriceMethod::$MAP)) {
            $new_val[] = $r;
        }
    }
    $CORE_LOCAL->set('PriceMethodClasses', $new_val);
}
if (!is_array($CORE_LOCAL->get('PriceMethodClasses'))){
	$CORE_LOCAL->set('PriceMethodClasses', array(), true);
}
$pms = AutoLoader::listModules('PriceMethod');
$pm_conf = $CORE_LOCAL->get("PriceMethodClasses");
$pm_conf[] = ''; // add blank slot for adding another method
$i = 100;
foreach ($pm_conf as $entry) {
	echo "[$i] => ";
	echo "<select name=PM_MODS[]>";
    echo '<option value="">[None]</option>';
	foreach($pms as $p) {
        if (in_array($p, PRiceMethod::$MAP)) {
            continue;
        }
		echo "<option";
		if ($entry == $p)
			echo " selected";
		echo ">$p</option>";
	}
	echo "</select><br />";
    $i++;
}
$save = array();
foreach($CORE_LOCAL->get("PriceMethodClasses") as $r){
    if ($r !== '' && !in_array($r, PriceMethod::$MAP)) {
        $save[] = $r;
    }
}
InstallUtilities::paramSave('PriceMethodClasses',$save);
?></td></tr>
<tr><td>
<b>Sale Items Are Discountable</b>:</td><td>
<?php
if (isset($_REQUEST['SALEDISC'])) $CORE_LOCAL->set('DiscountableSaleItems',$_REQUEST['SALEDISC']);
if ($CORE_LOCAL->get('DiscountableSaleItems') === '') $CORE_LOCAL->set('DiscountableSaleItems', 1);
echo '<select name="SALEDISC">';
if ($CORE_LOCAL->get('DiscountableSaleItems') == 0) {
	echo '<option value="1">Yes</option>';
	echo '<option value="0" selected>No</option>';
} else {
	echo '<option value="1" selected>Yes</option>';
	echo '<option value="0">No</option>';
}
echo '</select>';
InstallUtilities::paramSave('DiscountableSaleItems', $CORE_LOCAL->get('DiscountableSaleItems'));
?>
<span class='noteTxt'>
Items that are on sale are eligible for transaction-level discounts - e.g., members
save 5%.
</span>
</td></tr>
<tr><td colspan=2>
<hr />	<p>Special Department modules add extra steps to open rings in specific departments.
	Enter department number(s) that each module should apply to.</p>
</td></tr>
<tr><td>
<?php
$sdepts = AutoLoader::listModules('SpecialDept');
$sconf = $CORE_LOCAL->get('SpecialDeptMap');
if (!is_array($sconf)) $sconf = array();
if (isset($_REQUEST['SDEPT_MAP_LIST'])){
	$sconf = array();
	for($i=0;$i<count($_REQUEST['SDEPT_MAP_NAME']);$i++){
		if (!isset($_REQUEST['SDEPT_MAP_LIST'][$i])) continue;
		if (empty($_REQUEST['SDEPT_MAP_LIST'][$i])) continue;

		$class = $_REQUEST['SDEPT_MAP_NAME'][$i];
		$obj = new $class();
		$ids = preg_split('/\D+/',$_REQUEST['SDEPT_MAP_LIST'][$i]);
		foreach($ids as $id)
			$sconf = $obj->register($id,$sconf);
	}
	$CORE_LOCAL->set('SpecialDeptMap',$sconf);
}
foreach($sdepts as $sd){
	$list = "";
	foreach($sconf as $id => $mods){
		if (in_array($sd,$mods))
			$list .= $id.', ';
	}
	$list = rtrim($list,', ');
	printf('<tr><td>%s</td><td>
		<input type="text" name="SDEPT_MAP_LIST[]" value="%s" />
		<input type="hidden" name="SDEPT_MAP_NAME[]" value="%s" />
		</td></tr>',
		$sd,$list,$sd);
}
$saveStr = 'array(';
foreach($sconf as $id => $mods){
	if (empty($mods)) continue;
	$saveStr .= $id.'=>array(';
	foreach($mods as $m)
		$saveStr .= '\''.$m.'\',';
	$saveStr = rtrim($saveStr,',').'),';
}
$saveStr = rtrim($saveStr,',').')';
InstallUtilities::confsave('SpecialDeptMap',$saveStr);
?>
</td></tr>
<tr><td colspan=2>
<hr />
</td></tr>
<tr>
    <td colspan=2>
    <b>Variable Weight Item Mapping</b> (UPC Prefix "2"):<br />
    Variable-weight items do not have identical barcodes because the
    price is encoded in the barcode. A translator is required to map
    these different barcodes back to one logical product.
    </td>
</tr>
<tr>
    <td>
    <b>Translator</b>:
    </td>
    <td>
    <select name="VW_MOD">
    <?php
    $mods = AutoLoader::listModules('VariableWeightReWrite');
    if (isset($_REQUEST['VW_MOD'])) 
        $CORE_LOCAL->set('VariableWeightReWriter', $_REQUEST['VW_MOD']);
    else if ($CORE_LOCAL->get('VariableWeightReWriter') === '') 
        $CORE_LOCAL->set('VariableWeightReWriter', 'ZeroedPriceReWrite');
    foreach($mods as $m) {
        printf('<option %s>%s</option>',
            $CORE_LOCAL->get('VariableWeightReWriter') == $m ? 'selected' : '',
            $m);
    }
    InstallUtilities::paramSave('VariableWeightReWriter',$CORE_LOCAL->get('VariableWeightReWriter'));
    ?>
    </select>
    </td>
</tr>
<tr><td colspan=2>
<hr />
</td></tr>
<tr><td>
<input type=submit name=scansubmit value="Save Changes" />
</td></tr></table>
</form>
</div> <!--	wrapper -->
</body>
</html>
