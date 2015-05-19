<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class PriceMovementReport extends FannieReportPage 
{

    protected $title = "Fannie : Price Movement Report";
    protected $header = "Price Movement Report";

    protected $report_headers = array('UPC', 'Desc', 'Dept#', 'Dept', 'Price', 'Qty', 'Sales');
    protected $required_fields = array('date1', 'date2');

    public $description = '[Movement by Price] lists item sales with a separate line for each price point. If an item was sold at more than one price in the given date range, sales from each price are listed separately.';
    public $report_set = 'Movement Reports';
    public $themed = true;

    public function report_description_content()
    {
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
        $buyer = FormLib::get('buyer', '');
    
        $ret = array();
        if ($buyer === '') {
            $ret[] = 'Department '.$deptStart.' to '.$deptEnd;
        } else if ($buyer == -1) {
            $ret[] = 'All Super Departments';
        } else {
            $ret[] = 'Super Department '.$buyer;
        }

        return $ret;
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $date1 = FormLib::get('date1', date('Y-m-d'));
        $date2 = FormLib::get('date2', date('Y-m-d'));
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
    
        $buyer = FormLib::get('buyer', '');

        // args/parameters differ with super
        // vs regular department
        $args = array($date1.' 00:00:00', $date2.' 23:59:59');
        $where = ' 1=1 ';
        if ($buyer !== '') {
            if ($buyer != -1) {
                $where = ' s.superID=? ';
                $args[] = $buyer;
            }
        } else {
            $where = ' t.department BETWEEN ? AND ? ';
            $args[] = $deptStart;
            $args[] = $deptEnd;
        }

        $dlog = DTransactionsModel::selectDlog($date1, $date2);

        $query = "
            SELECT d.upc,
                p.description,"
                . DTrans::sumQuantity('d') . " AS qty,
                CASE WHEN memDiscount <> 0 AND memType <> 0 THEN unitPrice - memDiscount ELSE unitPrice END as price,
                d.department, 
                t.dept_name, 
                SUM(total) AS total
            FROM $dlog AS d "
                . DTrans::joinProducts('d', 'p', 'inner')
                . DTrans::joinDepartments('d', 't');
        // join only needed with specific buyer
        if ($buyer !== '' && $buyer > -1) {
            $query .= 'LEFT JOIN superdepts AS s ON d.department=s.dept_ID ';
        }
        $query .= "
            WHERE tdate BETWEEN ? AND ?
                AND $where
            GROUP BY d.upc,p.description,price,d.department,t.dept_name
            ORDER BY d.upc";

        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($query, $args);

        $data = array();
        while($row = $dbc->fetch_row($result)) {
            $record = array(
                $row['upc'],
                $row['description'],
                $row['department'],
                $row['dept_name'],
                sprintf('%.2f', $row['price']),
                sprintf('%.2f', $row['qty']),
                sprintf('%.2f', $row['total']),
            );

            $data[] = $record;
        }

        // bold items that sold at multiple prices
        for ($i=0; $i<count($data); $i++) {
            if (!isset($data[$i+1])) {
                continue;
            }

            if ($data[$i][0] == $data[$i+1][0]) {
                $data[$i]['meta'] = FannieReportPage::META_BOLD;
                $data[$i+1]['meta'] = FannieReportPage::META_BOLD;
            }
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        if (count($data) == 0) {
            return array();
        }

        $sum_qty = 0.0;
        $sum_ttl = 0.0;
        foreach($data as $row) {
            $sum_qty += $row[5];
            $sum_ttl += $row[6];
        }

        return array('Totals', null, null, null, null, sprintf('%.2f',$sum_qty), sprintf('%.2f',$sum_ttl));
    }

    public function form_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $deptsQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
        $deptsR = $dbc->exec_statement($deptsQ);
        $deptsList = "";

        $deptSubQ = $dbc->prepare_statement("SELECT superID,super_name FROM superDeptNames
                WHERE superID <> 0 
                ORDER BY superID");
        $deptSubR = $dbc->exec_statement($deptSubQ);

        $deptSubList = "";
        while($deptSubW = $dbc->fetch_array($deptSubR)) {
            $deptSubList .=" <option value=$deptSubW[0]>$deptSubW[1]</option>";
        }
        while ($deptsW = $dbc->fetch_array($deptsR)) {
            $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";
        }

        ob_start();
        ?>
<div class="well">Selecting a Buyer/Dept overrides Department Start/Department End, but not Date Start/End.
        To run reports for a specific department(s) leave Buyer/Dept or set it to 'blank'
</div>
<form method="get" action="PriceMovementReport.php" class="form-horizontal">
<div class="row">
    <div class="col-sm-5">
        <div class="form-group">
            <label class="control-label col-sm-4">Select Buyer/Dept</label>
            <div class="col-sm-8">
            <select id=buyer name=buyer class="form-control">>
               <option value=0 >
               <?php echo $deptSubList; ?>
               <option value=-2 >All Retail</option>
               <option value=-1 >All</option>
           </select>
           </div>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-4">Department Start</label>
            <div class="col-sm-6">
            <select id=deptStartSel onchange="$('#deptStart').val(this.value);" class="form-control col-sm-6">
                <?php echo $deptsList ?>
            </select>
            </div>
            <div class="col-sm-2">
            <input type=number name=deptStart id=deptStart size=5 value=1 class="form-control col-sm-2" />
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-4">Department End</label>
            <div class="col-sm-6">
                <select id=deptEndSel onchange="$('#deptEnd').val(this.value);" class="form-control">
                    <?php echo $deptsList ?>
                </select>
            </div>
            <div class="col-sm-2">
                <input type=number name=deptEnd id=deptEnd size=5 value=1 class="form-control" />
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-sm-4">Save to Excel
                <input type=checkbox name=excel id=excel value=1>
            </label>
            <label class="col-sm-4 control-label">Store</label>
            <div class="col-sm-4">
                <?php $ret=FormLib::storePicker();echo $ret['html']; ?>
            </div>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <label class="col-sm-4 control-label">Start Date</label>
            <div class="col-sm-8">
                <input type=text id=date1 name=date1 class="form-control date-field" required />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label">End Date</label>
            <div class="col-sm-8">
                <input type=text id=date2 name=date2 class="form-control date-field" required />
            </div>
        </div>
        <div class="form-group">
            <?php echo FormLib::date_range_picker(); ?>                            
        </div>
    </div>
</div>
    <p>
        <button type=submit name=submit value="Submit" class="btn btn-default">Submit</button>
        <button type=reset name=reset class="btn btn-default">Start Over</button>
    </p>
</form>
        <?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>This report lists a row for each price point
            each item was sold for in the given date range. Sales
            totals are for all sales at a particular price during
            the date range.</p>';
    }
}

FannieDispatch::conditionalExec();

?>
