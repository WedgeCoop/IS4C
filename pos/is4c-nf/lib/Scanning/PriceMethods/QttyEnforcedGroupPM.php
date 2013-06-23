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
/* Quantity-Enforced Group PriceMethod module
   
   This module provides grouped sales where the
   customer is required to buy a "complete set"
   before the group discount is applied

   In most locations, this is pricemethod 1 or 2
*/
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists('PriceMethod')) include($IS4C_PATH.'lib/Scanning/PriceMethod.php');
if (!function_exists('addItem')) include($IS4C_PATH.'lib/additem.php');
if (!function_exists('tDataConnect')) include($IS4C_PATH.'lib/connect.php');
if (!function_exists('truncate2')) include($IS4C_PATH.'lib/lib.php');

class QttyEnforcedGroupPM extends PriceMethod {

	function addItem($row,$quantity,$priceObj){
		if ($quantity == 0) return false;

		$pricing = $priceObj->priceInfo($row,$quantity);

		/* group definition: number of items
		   that make up a group, price for a
		   full set. Use "special" rows if the
		   item is on sale */
		$groupQty = $row['quantity'];
		$groupPrice = $row['groupprice'];
		if ($priceObj->isSale()){
			$groupQty = $row['specialquantity'];
			$groupPrice = $row['specialgroupprice'];	
		}

		/* calculate how many complete sets are
		   present in this scan and how many remain
		   after complete sets */
		$new_sets = floor($quantity / $groupQty);
		$remainder = $quantity % $groupQty;

		/* add complete sets */
		if ($new_sets > 0){
			/* discount for complete set */
			$discount = ($pricing['unitPrice']*$groupQty) - $groupPrice;
			$memDiscount = 0;
			if ($priceObj->isMemberSale() || $priceObj->isStaffSale()){
				$memDiscount = $discount;
				$discount = 0;
			}

			addItem($row['upc'],
				$row['description'],
				'I',
				'',
				'',
				$row['department'],
				$new_sets * $groupQty,
				$pricing['unitPrice'],
				truncate2($pricing['unitPrice'] * $quantity),
				$pricing['regPrice'],
				$row['scale'],
				$row['tax'],
				$row['foodstamp'],
				$discount,
				$memDiscount,
				$row['discount'],
				$row['discounttype'],
				$new_sets * $groupQty,
				($priceObj->isSale() ? $row['specialpricemethod'] : $row['pricemethod']),
				($priceObj->isSale() ? $row['specialquantity'] : $row['quantity']),
				($priceObj->isSale() ? $row['specialgroupprice'] : $row['groupprice']),
				$row['mixmatchcode'],
				$new_sets * $groupQty,
				0,
				(isset($row['cost']) ? $row['cost']*$new_sets*$groupQty : 0.00),
				(isset($row['numflag']) ? $row['numflag'] : 0),
				(isset($row['charflag']) ? $row['charflag'] : '')
			);

			$quantity = $quantity - ($new_sets * $groupQty);
			if ($quantity < 0) $quantity = 0;
		}

		/* if potential matches remain, check for sets */
		if ($remainder > 0){
			/* count items in the transaction
			   from the given group, minus
			   items that have already been used
			   in a grouping */
			$mixMatch  = $row["mixmatchcode"];
			$queryt = "select sum(ItemQtty - matched) as mmqtty, 
				mixMatch from localtemptrans 
				where trans_status <> 'R' AND 
				mixMatch = '".$mixMatch."' group by mixMatch";
			if (!$mixMatch || $mixMatch == '0') {
				$mixMatch = 0;
				$queryt = "select sum(ItemQtty - matched) as mmqtty from "
					."localtemptrans where trans_status<>'R' AND "
					."upc = '".$upc."' group by upc";
			}
			$dbt = tDataConnect();
			$resultt = $dbt->query($queryt);
			$num_rowst = $dbt->num_rows($resultt);

			$trans_qty = 0;
			if ($num_rowst > 0){
				$rowt = $dbt->fetch_array($resultt);
				$trans_qty = floor($rowt['mmqtty']);
			}

			/* remainder from current scan plus existing
			   unmatched items complete a new set, so
			   add one item with the group discount */
			if ($trans_qty + $remainder >= $groupQty){
				/* adjusted price for the "last" item in a set */
				$priceAdjust = $groupPrice - (($groupQty-1) * $pricing['unitPrice']);
				$discount = $pricing['unitPrice'] - $priceAdjust;
				$memDiscount = 0;
				if ($priceObj->isMemberSale() || $priceObj->isStaffSale()){
					$memDiscount = $discount;
					$discount = 0;
				}

				addItem($row['upc'],
					$row['description'],
					'I',
					'',
					'',
					$row['department'],
					1,
					$pricing['unitPrice'],
					$pricing['unitPrice'],
					$pricing['regPrice'],
					$row['scale'],
					$row['tax'],
					$row['foodstamp'],
					$discount,
					$memDiscount,
					$row['discount'],
					$row['discounttype'],
					1,
					($priceObj->isSale() ? $row['specialpricemethod'] : $row['pricemethod']),
					($priceObj->isSale() ? $row['specialquantity'] : $row['quantity']),
					($priceObj->isSale() ? $row['specialgroupprice'] : $row['groupprice']),
					$row['mixmatchcode'],
					$groupQty,
					0,
					(isset($row['cost']) ? $row['cost']*$new_sets*$groupQty : 0.00),
					(isset($row['numflag']) ? $row['numflag'] : 0),
					(isset($row['charflag']) ? $row['charflag'] : '')
				);

				$quantity -= 1;
				if ($quantity < 0) $quantity = 0;
			}
		}

		/* any remaining quantity added without
		   grouping discount */
		if ($quantity > 0){
			addItem($row['upc'],
				$row['description'],
				'I',
				' ',
				' ',
				$row['department'],
				$quantity,
				$pricing['unitPrice'],
				truncate2($pricing['unitPrice'] * $quantity),
				$pricing['regPrice'],
				$row['scale'],
				$row['tax'],
				$row['foodstamp'],
				0,		
				0,	
				$row['discount'],
				$row['discounttype'],
				$quantity,
				($priceObj->isSale() ? $row['specialpricemethod'] : $row['pricemethod']),
				($priceObj->isSale() ? $row['specialquantity'] : $row['quantity']),
				($priceObj->isSale() ? $row['specialgroupprice'] : $row['groupprice']),
				$row['mixmatchcode'],
				0,
				0,
				(isset($row['cost'])?$row['cost']*$quantity:0.00),
				(isset($row['numflag'])?$row['numflag']:0),
				(isset($row['charflag'])?$row['charflag']:'')
			);
		}
	}
}

?>
