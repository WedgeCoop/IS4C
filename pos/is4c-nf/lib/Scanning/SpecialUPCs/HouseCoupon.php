<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

if (!class_exists("SpecialUPC")) include($IS4C_PATH."lib/Scanning/SpecialUPC.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

if (!function_exists("boxMsg")) include($IS4C_PATH."lib/drawscreen.php");
if (!function_exists("getsubtotals")) include($IS4C_PATH."lib/connect.php");
if (!function_exists("lastpage")) include($IS4C_PATH."lib/listitems.php");
if (!function_exists("addhousecoupon")) include($IS4C_PATH."lib/additem.php");

class HouseCoupon extends SpecialUPC {

	function is_special($upc){
		if (substr($upc,0,8) == "00499999")
			return true;

		return false;
	}

	function handle($upc,$json){
		global $IS4C_LOCAL;

		$coupID = ltrim(substr($upc,-5),"0");
		$leadDigits = substr($upc,3,5);

		/* make sure the coupon exists
		 * and isn't expired
		 */
		$db = pDataConnect();
		$infoQ = "select endDate,limit,discountType, department,
			discountValue,minType,minValue,memberOnly, 
			case when endDate is NULL then 0 else 
			datediff(dd,getdate(),endDate) end as expired
			from
			houseCoupons where coupID=".$coupID;
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$infoQ = str_replace("dd,getdate(),endDate","endDate,now()",$infoQ);
			$infoQ = str_replace("limit","`limit`",$infoQ);
		}
		$infoR = $db->query($infoQ);
		if ($db->num_rows($infoR) == 0){
			$json['output'] =  boxMsg("coupon not found");
			return $json;
		}
		$infoW = $db->fetch_row($infoR);
		if ($infoW["expired"] < 0){
			$expired = substr($infoW["endDate"],0,strrpos($infoW["endDate"]," "));
			$json['output'] =  boxMsg("coupon expired ".$expired);
			return $json;
		}

		/* check the number of times this coupon
		 * has been used in this transaction
		 * against the limit */
		$transDB = tDataConnect();
		$limitQ = "select case when sum(ItemQtty) is null
			then 0 else sum(ItemQtty) end
			from localtemptrans where
			upc = '".$upc."'";
		$limitR = $transDB->query($limitQ);
		$times_used = array_pop($transDB->fetch_row($limitR));
		if ($times_used >= $infoW["limit"]){
			$json['output'] =  boxMsg("coupon already applied");
			return $json;
		}

		/* check for member-only, tigher use tracking
		   available with member coupons */
		if ($infoW["memberOnly"] == 1 and 
		   ($IS4C_LOCAL->get("memberID") == "0" or $IS4C_LOCAL->get("isMember") != 1)
		   ){
			$json['output'] = boxMsg("Member only coupon<br>Apply member number first");
			return $json;
		}
		else if ($infoW["memberOnly"] == 1 && $IS4C_LOCAL->get("standalone")==0){
			$mDB = mDataConnect();
			$mR = $mDB->query("SELECT quantity FROM houseCouponThisMonth
				WHERE card_no=".$IS4C_LOCAL->get("memberID")." and
				upc='$upc'");
			if ($mDB->num_rows($mR) > 0){
				$uses = array_pop($mDB->fetch_row($mR));
				if ($infoW["limit"] >= $uses){
					$json['output'] = boxMsg("Coupon already used<br />on this membership");
					return $json;
				}
			}
		}

		/* verify the minimum purchase has been made */
		switch($infoW["minType"]){
		case "Q": // must purchase at least X
			$minQ = "select case when sum(ItemQtty) is null
				then 0 else sum(ItemQtty) end
			       	from localtemptrans
				as l left join opData.dbo.houseCouponItems 
				as h on l.upc = h.upc
				where h.coupID=".$coupID;
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$minQ = str_replace("dbo.","",$minQ);
			$minR = $transDB->query($minQ);
			$validQtty = array_pop($transDB->fetch_row($minR));
			if ($validQtty < $infoW["minValue"]){
				$json['output'] = boxMsg("coupon requirements not met");
				return $json;
			}
			break;
		case "Q+": // must purchase more than X
			$minQ = "select case when sum(ItemQtty) is null
				then 0 else sum(ItemQtty) end
			       	from localtemptrans
				as l left join opData.dbo.houseCouponItems 
				as h on l.upc = h.upc
				where h.coupID=".$coupID;
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$minQ = str_replace("dbo.","",$minQ);
			$minR = $transDB->query($minQ);
			$validQtty = array_pop($transDB->fetch_row($minR));
			if ($validQtty <= $infoW["minValue"]){
				$json['output'] = boxMsg("coupon requirements not met");
				return $json;
			}
			break;
		case 'D': // must at least purchase from department
			$minQ = "select case when sum(total) is null
				then 0 else sum(total) end
				from localtemptrans
				as l left join opData.dbo.houseCouponItems
				as h on l.department = h.upc
				where h.coupID=".$coupID;
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$minQ = str_replace("dbo.","",$minQ);
			$minR = $transDB->query($minQ);
			$validQtty = array_pop($transDB->fetch_row($minR));
			if ($validQtty < $infoW["minValue"]){
				$json['output'] = boxMsg("coupon requirements not met");
				return $json;
			}
			break;
		case 'D+': // must more than purchase from department 
			$minQ = "select case when sum(total) is null
				then 0 else sum(total) end
				from localtemptrans
				as l left join opData.dbo.houseCouponItems
				as h on l.department = h.upc
				where h.coupID=".$coupID;
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$minQ = str_replace("dbo.","",$minQ);
			$minR = $transDB->query($minQ);
			$validQtty = array_pop($transDB->fetch_row($minR));
			if ($validQtty <= $infoW["minValue"]){
				$json['output'] = boxMsg("coupon requirements not met");
				return $json;
			}
			break;
		case 'M': // must purchase at least X qualifying items
			  // and some quantity corresponding discount items
			$minQ = "select case when sum(ItemQtty) is null then 0 else
				sum(ItemQtty) end
				from localtemptrans
				as l left join opData.dbo.houseCouponItems
				as h on l.upc = h.upc
				where h.coupID=$coupID
				and h.type = 'QUALIFIER'";
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$minQ = str_replace("dbo.","",$minQ);
			$minR = $transDB->query($minQ);
			$validQtty = array_pop($transDB->fetch_row($minR));

			$min2Q = "select case when sum(ItemQtty) is null then 0 else
				sum(ItemQtty) end
				from localtemptrans
				as l left join opData.dbo.houseCouponItems
				as h on l.upc = h.upc
				where h.coupID=$coupID
				and h.type = 'DISCOUNT'";
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$min2Q = str_replace("dbo.","",$min2Q);
			$min2R = $transDB->query($min2Q);
			$validQtty2 = array_pop($transDB->fetch_row($min2R));

			if ($validQtty < $infoW["minValue"] || $validQtty2 <= 0){
				$json['output'] = boxMsg("coupon requirements not met");
				return $json;
			}
			break;
		case '$': // must purchase at least $ total items
			$minQ = "SELECT sum(total) FROM localtemptrans
				WHERE trans_type IN ('I','D','M')";
			$minR = $transDB->query($minQ);
			$validAmt = array_pop($transDB->fetch_row($minR));
			if ($validAmt < $infoW["minValue"]){
				$json['output'] = boxMsg("coupon requirements not met");
				return $json;
			}
			break;
		case '$+': // must purchase more than $ total items
			$minQ = "SELECT sum(total) FROM localtemptrans
				WHERE trans_type IN ('I','D','M')";
			$minR = $transDB->query($minQ);
			$validAmt = array_pop($transDB->fetch_row($minR));
			if ($validAmt <= $infoW["minValue"]){
				$json['output'] = boxMsg("coupon requirements not met");
				return $json;
			}
			break;
		case '': // no minimum
		case ' ':
			break;
		default:
			$json['output'] = boxMsg("unknown minimum type ".$infoW["minType"]);
			return $json;
		}

		/* if we got this far, the coupon
		 * should be valid
		 */
		$value = 0;
		switch($infoW["discountType"]){
		case "Q": // quantity discount
			// discount = coupon's discountValue
			// times the cheapeast coupon item
			$valQ = "select unitPrice, department from localtemptrans
				as l left join opData.dbo.houseCouponItems
				as h on l.upc = h.upc
				where h.coupID=".$coupID." 
				and h.type in ('BOTH','DISCOUNT')
				and l.total >0
				order by unitPrice asc";
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$valQ = str_replace("dbo.","",$valQ);
			$valR = $transDB->query($valQ);
			$valW = $transDB->fetch_row($valR);
			$value = $valW[0]*$infoW["discountValue"];
			break;
		case "P": // discount price
			// query to get the item's department and current value
			// current value minus the discount price is how much to
			// take off
			$value = $infoW["discountValue"];
			$deptQ = "select department,(total/quantity) as value from localtemptrans
				as l left join opdata.dbo.houseCouponItems
				as h on l.upc = h.upc
				where h.coupID=".$coupID."
				and h.type in ('BOTH','DISCOUNT')
				and l.total >0
				order by unitPrice asc";
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$deptQ = str_replace("dbo.","",$deptQ);
			$deptR = $transDB->query($deptQ);
			$row = $transDB->fetch_row($deptR);
			$value = $row[1] - $value;
			break;
		case "FD": // flat discount for departments
			// simply take off the requested amount
			// scales with quantity for by-weight items
			$value = $infoW["discountValue"];
			$valQ = "select department,quantity from localtemptrans
				as l left join opdata.dbo.houseCouponItems
				as h on l.department = h.upc
				where h.coupID=".$coupID."
				and h.type in ('BOTH','DISCOUNT')
				and l.total > 0
				order by unitPrice asc";
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$valQ = str_replace("dbo.","",$valQ);
			$valR = $transDB->query($valQ);
			$row = $transDB->fetch_row($valR);
			$value = $row[1] * $value;
			break;
		case "FI": // flat discount for items
			// simply take off the requested amount
			// scales with quantity for by-weight items
			$value = $infoW["discountValue"];
			$valQ = "select l.upc,quantity from localtemptrans
				as l left join opdata.dbo.houseCouponItems
				as h on l.upc = h.upc
				where h.coupID=".$coupID."
				and h.type in ('BOTH','DISCOUNT')
				and l.total > 0
				order by unitPrice asc";
			if ($IS4C_LOCAL->get("DBMS") == "mysql")
				$valQ = str_replace("dbo.","",$valQ);
			$valR = $transDB->query($valQ);
			$row = $transDB->fetch_row($valR);
			$value = $row[1] * $value;
			break;
		case "F": // completely flat; no scaling for weight
			$value = $infoW["discountValue"];
			break;
		case "%": // percent discount on all items
			getsubtotals();
			$value = $infoW["discountValue"]*$IS4C_LOCAL->get("discountableTotal");
			break;
		}

		$dept = $infoW["department"];
		
		addhousecoupon($upc,$dept,-1*$value);
		$json['output'] = lastpage();
		return $json;
	}

}

?>
