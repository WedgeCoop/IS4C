<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists("Parser")) include_once($IS4C_PATH."parser-class-lib/Parser.php");
if (!function_exists("paycard_reset")) include_once($IS4C_PATH."lib/paycardLib.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class paycardEntered extends Parser {
	var $swipestr;
	var $swipetype;
	var $manual;

	function check($str){
		if (substr($str,-1,1) == "?"){
			$this->swipestr = $str;
			$this->swipetype = PAYCARD_TYPE_UNKNOWN;
			$this->manual = False;
			return True;
		}
		elseif (is_numeric($str) && strlen($str) >= 16){
			$this->swipestr = $str;
			$this->swipetype = PAYCARD_TYPE_UNKNOWN;
			$this->manual = True;
			return True;
		}
		elseif (is_numeric(substr($str,2)) && strlen($str) >= 18){
			$this->swipestr = $str;
			$this->swipetype = PAYCARD_TYPE_UNKNOWN;
			$this->manual = True;
			return True;
		}
		return False;
	}

	function parse($str){
		$ret = array();
		$str = $this->swipestr;
		if( substr($str,0,2) == "PV") {
			$ret = $this->paycard_entered(PAYCARD_MODE_BALANCE, substr($str,2), $this->manual, $this->swipetype);
		} else if( substr($str,0,2) == "AV") {
			$ret = $this->paycard_entered(PAYCARD_MODE_ADDVALUE, substr($str,2), $this->manual, $this->swipetype);
		} else if( substr($str,0,2) == "AC") {
			$ret = $this->paycard_entered(PAYCARD_MODE_ACTIVATE, substr($str,2), $this->manual, $this->swipetype);
		} else {
			$ret = $this->paycard_entered(PAYCARD_MODE_AUTH, $str, $this->manual, $this->swipetype);
		}
		// if successful, paycard_entered() redirects to a confirmation page and exit()s; if we're still here, there was an error, so reset all data
		if ($ret['main_frame'] == false)
			paycard_reset();
		return $ret;
	}

	function paycard_entered($mode,$card,$manual,$type){
		global $IS4C_LOCAL,$IS4C_PATH;
		$ret = $this->default_json();
		// initialize
		$validate = true; // run Luhn's on PAN, check expiration date
		paycard_reset();
		$IS4C_LOCAL->set("paycard_mode",$mode);
		$IS4C_LOCAL->set("paycard_manual",($manual ? 1 : 0));

		// error checks based on transaction
		if( $mode == PAYCARD_MODE_AUTH) {
			if( $IS4C_LOCAL->get("ttlflag") != 1) { // must subtotal before running card
				$ret['output'] = paycard_msgBox($type,"No Total",
					"Transaction must be totaled before tendering or refunding","[clear] to cancel");
				return $ret;
			} else if( abs($IS4C_LOCAL->get("amtdue")) < 0.005) { // can't tender for more than due
				$ret['output'] = paycard_msgBox($type,"No Total",
					"Nothing to tender or refund","[clear] to cancel");
				return $ret;
			}
		}

		// check for pre-validation override
		if( strtoupper(substr($card,0,1)) == 'O') {
			$validate = false;
			$card = substr($card, 1);
		}
	
		// parse card data
		if( $IS4C_LOCAL->get("paycard_manual")) {
			// make sure it's numeric
			if( !ctype_digit($card) || strlen($card) < 18) { // shortest known card # is 14 digits, plus MMYY
				$ret['output'] = paycard_msgBox($type,"Manual Entry Unknown",
					"Please enter card data like:<br>CCCCCCCCCCCCCCCCMMYY","[clear] to cancel");
				return $ret;
			}
			// split up input (and check for the Concord test card)
			if ($type == PAYCARD_TYPE_UNKNOWN){
				$type = paycard_type($card);
			}
			if( $type == PAYCARD_TYPE_GIFT) {
				$IS4C_LOCAL->set("paycard_PAN",$card); // our gift cards have no expiration date or conf code
			} else {
				$IS4C_LOCAL->set("paycard_PAN",substr($card,0,-4));
				$IS4C_LOCAL->set("paycard_exp",substr($card,-4,4));
			}
		} else {
			// swiped magstripe (reference to ISO format at end of this file)
			$stripe = paycard_magstripe($card);
			if( !is_array($stripe)) {
				$ret['output'] = paycard_errBox($type,$IS4C_LOCAL->get("paycard_manual")."Card Data Invalid","Please swipe again or type in manually","[clear] to cancel");
				return $ret;
			}
			$IS4C_LOCAL->set("paycard_PAN",$stripe["pan"]);
			$IS4C_LOCAL->set("paycard_exp",$stripe["exp"]);
			$IS4C_LOCAL->set("paycard_name",$stripe["name"]);
			$IS4C_LOCAL->set("paycard_tr1",$stripe["tr1"]);
			$IS4C_LOCAL->set("paycard_tr2",$stripe["tr2"]);
			$IS4C_LOCAL->set("paycard_tr3",$stripe["tr3"]);
		} // manual/swiped

		// determine card issuer and type
		$IS4C_LOCAL->set("paycard_type",paycard_type($IS4C_LOCAL->get("paycard_PAN")));
		$IS4C_LOCAL->set("paycard_issuer",paycard_issuer($IS4C_LOCAL->get("paycard_PAN")));
	
		// if we knew the type coming in, make sure it agrees
		if( $type != PAYCARD_TYPE_UNKNOWN && $type != $IS4C_LOCAL->get("paycard_type")) {
			$ret['output'] = paycard_msgBox($type,"Type Mismatch",
				"Card number does not match card type","[clear] to cancel");
			return $ret;
		}

		foreach($IS4C_LOCAL->get("RegisteredPaycardClasses") as $rpc){
			if (!class_exists($rpc)) include_once($IS4C_PATH."cc-modules/$rpc.php");
			$myObj = new $rpc();
			if ($myObj->handlesType($IS4C_LOCAL->get("paycard_type")))
				return $myObj->entered($validate,$ret);
		}

		$ret['output'] = paycard_errBox(PAYCARD_TYPE_UNKNOWN,"Unknown Card Type ".$IS4C_LOCAL->get("paycard_type"),"","[clear] to cancel");
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>	
				<td>Card swipe or card number</td>
				<td>Try to charge amount to card</td>
			</tr>
			<tr>
				<td>PV<i>swipe</i> or PV<i>number</i></td>
				<td>Check balance of gift card</td>
			</tr>
			<tr>
				<td>AC<i>swipe</i> or AC<i>number</i></td>
				<td>Activate gift card</td>
			</tr>
			<tr>
				<td>AV<i>swipe</i> or AV<i>number</i></td>
				<td>Add value to gift card</td>
			</tr>
			</table>";
	}
}

?>
