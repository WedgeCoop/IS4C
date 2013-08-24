<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');
include($FANNIE_ROOT.'classlib2.0/FannieReportPage.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');

class CustomerPurchasesReport extends FannieReportPage {

	function preprocess(){
		/**
		  Set the page header and title, enable caching
		*/
		$this->title = "Fannie : What Did I Buy?";
		$this->header = "What Did I Buy? Report";
		$this->report_cache = 'none';

		if (isset($_REQUEST['date1'])){
			/**
			  Form submission occurred

			  Change content function, turn off the menus,
			  set up headers
			*/
			$this->content_function = "report_content";
			$this->has_menus(False);
			$this->report_headers = array('Date','UPC','Description','Dept','Cat','Qty','$');
		
			/**
			  Check if a non-html format has been requested
			*/
			if (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'xls')
				$this->report_format = 'xls';
			elseif (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'csv')
				$this->report_format = 'csv';
		}
		else 
			$this->add_script("../../src/CalendarControl.js");

		return True;
	}

	function fetch_report_data(){
		global $dbc, $FANNIE_ARCHIVE_DB;
		$date1 = FormLib::get_form_value('date1',date('Y-m-d'));
		$date2 = FormLib::get_form_value('date2',date('Y-m-d'));
		$card_no = FormLib::get_form_value('card_no','0');

		$dlog = select_dlog($date1,$date2);

		$query = "select month(t.tdate),day(t.tdate),year(t.tdate),
			  t.upc,p.description,
			  t.department,d.dept_name,m.super_name,
			  sum(t.quantity) as qty,
			  sum(t.total) as ttl from
			  $dlog as t left join products as p on t.upc = p.upc 
			  left join departments AS d ON t.department=d.dept_no
			  left join MasterSuperDepts AS m ON t.department=m.dept_ID
			  where t.card_no = ? AND
			  trans_type IN ('I','D') AND
			  tdate BETWEEN ? AND ?
			  group by year(t.tdate),month(t.tdate),day(t.tdate),
			  t.upc,p.description
			  order by year(t.tdate),month(t.tdate),day(t.tdate)";
		$args = array($card_no,$date1.' 00:00:00',$date2.' 23:59:59');
	
		$prep = $dbc->prepare_statement($query);
		$result = $dbc->exec_statement($prep,$args);

		/**
		  Simple report
		
		  Issue a query, build array of results
		*/
		$ret = array();
		while ($row = $dbc->fetch_array($result)){
			$record = array();
			$record[] = $row[0]."/".$row[1]."/".$row[2];
			$record[] = $row['upc'];
			$record[] = $row['description'];
			$record[] = $row['department'].' '.$row['dept_name'];
			$record[] = $row['super_name'];
			$record[] = $row['qty'];
			$record[] = $row['ttl'];
			$ret[] = $record;
		}
		return $ret;
	}

	function report_description_content(){
		$ret = array();
		$ret[] = "Movement from ".FormLib::get_form_value('date1','')." to ".FormLib::get_form_value('date2','');
		$ret[] = "For owner #".FormLib::get_form_value('card_no');
		return $ret;
	}
	
	/**
	  Sum the quantity and total columns
	*/
	function calculate_footers($data){
		$sumQty = 0.0;
		$sumSales = 0.0;
		foreach($data as $row){
			$sumQty += $row[5];
			$sumSales += $row[6];
		}
		return array('Total',null,null,null,null,$sumQty,$sumSales);
	}

	function form_content(){
?>
<div id=main>	
<form method = "get" action="CustomerPurchasesReport.php">
	<table border="0" cellspacing="0" cellpadding="5">
		<tr> 
			<th>Owner#</th>
			<td>
			<input type=text name=card_no size=14 id=card_no  />
			</td>
			<td>
			<input type="checkbox" name="excel" id="excel" value="xls" />
			<label for="excel">Excel</label>
			</td>	
		</tr>
		<tr>
			<th>Date Start</th>
			<td>	
		               <input type=text size=14 id=date1 name=date1 onfocus="this.value='';showCalendarControl(this);">
			</td>
			<td rowspan="3">
			<?php echo FormLib::date_range_picker(); ?>
			</td>
		</tr>
		<tr>
			<th>End</th>
			<td>
		                <input type=text size=14 id=date2 name=date2 onfocus="this.value='';showCalendarControl(this);">
		       </td>

		</tr>
		<tr>
			<td> <input type=submit name=submit value="Submit"> </td>
			<td> <input type=reset name=reset value="Start Over"> </td>
		</tr>
	</table>
</form>
</div>
<?php
	}
}

$obj = new CustomerPurchasesReport();
$obj->draw_page();
?>
