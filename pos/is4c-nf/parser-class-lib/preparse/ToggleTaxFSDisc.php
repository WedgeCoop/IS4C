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
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class ToggleTaxFSDisc extends Parser {
	var $tfd;
	var $remainder;

	var $TAX = 4;
	var $FS = 2;
	var $DISC = 1;

	// use bit-masks to determine the which toggles
	// should be enabled
	function check($str){
		$this->tfd = 0;
		if (substr($str,0,5) == "1TNFN" || substr($str,0,5) == "FN1TN"){
			$this->remainder = substr($str,5);
			$this->tfd = $this->tfd | $this->TAX;
			$this->tfd = $this->tfd | $this->FS;	
			return True;
		}
		elseif (substr($str,0,4) == "FNDN" || substr($str,0,4) == "DNFN"){
			$this->remainder = substr($str,4);
			$this->tfd = $this->tfd | $this->DISC;
			$this->tfd = $this->tfd | $this->FS;	
			return True;
		}
		elseif (substr($str,0,3) == "1TN"){
			$this->remainder = substr($str,3);
			$this->tfd = $this->tfd | $this->TAX;
			return True;

		}
		elseif (substr($str,0,2) == "FN" && substr($str,2,2) != "TL"){
			$this->remainder = substr($str,2);
			$this->tfd = $this->tfd | $this->FS;	
			return True;
		}
		elseif (substr($str,0,2) == "DN"){
			$this->remainder = substr($str,2);
			$this->tfd = $this->tfd | $this->DISC;	
			return True;
		}
		elseif (substr($str,0,2) == "ND"){
			$this->remainder = substr($str,2);
			$IS4C_LOCAL->set("nd",1);
			return True;
		}
		return False;	
	}

	function parse($str){
		global $IS4C_LOCAL;
		if ($this->tfd & $this->TAX)
			$IS4C_LOCAL->set("toggletax",1);
		if ($this->tfd & $this->FS)
			$IS4C_LOCAL->set("togglefoodstamp",1);
		if ($this->tfd & $this->DISC)
			$IS4C_LOCAL->set("toggleDiscountable",1);
		return $this->remainder;	
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>1TN<i>ringable</i></td>
				<td>Toggle tax setting for <i>ringable</i>
				which may be an item or group of same items
				using *</td>
			</tr>
			<tr>
				<td>FN<i>ringable</i></td>
				<td>Toggle foodstamp setting for <i>ringable</i>
				which may be an item or group of same items
				using *</td>
			</tr>
			<tr>
				<td>DN<i>ringable</i></td>
				<td>Toggle discount setting for <i>ringable</i>
				which may be an item or group of same items
				using *</td>
			</tr>
			<tr>
				<td>ND<i>ringable</i></td>
				<td>Force no discount for <i>ringable</i>
				which may be an item or group of same items
				using *</td>
			</tr>
			</table>";
	}
}

?>
